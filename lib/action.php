<?php
/**
 * Функции, реализующие пользовательские действия.
 * Вызов этих функций происходит после авторизации пользователя.
 * 
 * @author mkoshkin
 */
namespace Action;

use Exception;

/**
 * Процент комиссии, взымаемый системой
 */
const COMMISSION = 0.3;

/**
 * Создать заказ от лица пользователя на указанную сумму.
 * 
 * @param double    $price сумма заказа
 * 
 * @return array массив с данными добавленного заказа:
 *                  order_id, ts, price
 * 
 * @throws Exception при ошибках подключения к базе
 */
function createOrder($price) {
    $userId = \Auth\getCurrentUserId();
    $host = \Order\loadHostForNewOrder($userId);
    if ($host === null) {
        return null;
    }
    
    $hostId = $host['host_id'];
    $hostLink = \Database\getConnectionOrFall($hostId);
    
    // заводим заказ в основной таблице
    
    if (!mysqli_begin_transaction($hostLink)) {
        throw new Exception('Невозможно начать транзакцию на хосте [' . $hostId . ']');
    }
    
    $orderId = \Order\loadNextOrderId($hostLink, $userId, $host['from_order_id']);
    
    $time = time();
    $result = mysqli_query(
            $hostLink, 
            'INSERT INTO users_orders'
            . 'SET user_id=' . $userId . ', '
            . 'order_id=' . $orderId . ', '
            . 'ts=' . $time . ', '
            . 'status=0, '
            . 'price=' . $price); // TODO: сделать!
    
    if ($result === false) {
        throw new Exception('Не удалось добавить заказ [' . $userId . ', ' . $orderId . ']: ' . mysqli_error($hostLink));
    }
    
    // добавляем заказ в дайджест, чтобы его смогли увидеть все пользователи
    if (!addOrderToDigest($userId, $orderId, $time, $price)){
        mysqli_rollback($hostLink);
        return null;
    }
    
    if (!mysqli_commit($hostLink)) {
        if (!removeOrderFromDigest($userId, $orderId, $time)) {
            // худший случай - в дайджесте остается след заказа, которого нет
            \DoLog\appendRemoveFromDigest($userId, $orderId, $time);
        }
        throw new Exception('Не удалось завершить транзакцию [' . $userId . ', ' . $orderId . ']');
    }
    
    return $orderId;
}

/**
 * Добавляет заказ в дайджест
 * 
 * @param int $userId ID заказчика
 * @param int $orderId ID заказа
 * @param int $ts unix-время добавления заказа
 * @param double $price стоимость заказа
 * 
 * @return bool удалось ли добавить заказ в дайджест
 */
function addOrderToDigest($userId, $orderId, $ts, $price) {
    $return = true;
    try {
        $hostId = \Digest\findHostId($userId, $orderId, $ts);

        $link = \Database\getConnectionOrFall($hostId);

        $result = mysqli_query(
                $link, 
                'INSERT IGNORE INTO orders_digest(ts, user_id, order_id, price) '
                . 'VALUES(' . $ts . ', ' . $userId . ', ' . $orderId . ', ' . $price . ')');

        if ($result === false) {
            throw new Exception('При добавлении заказа в дайджест на хосте [' . $hostId . '] возникла ошибка: ' . mysqli_error($link));
        }
    } catch (Exception $Exception) {
        $return = false;
    }
    
    return $return;
}

/**
 * Удаляет заказ из дайджеста
 * 
 * @param int $userId ID заказчика
 * @param int $orderId ID заказа
 * @param int $ts unix-время добавления заказа
 * 
 * @return bool удалось ли убрать заказ из дайджеста:
 *              true - если в процессе удаления не было ошибок,
 *              false - если в процессе удаления возникли ошибки и до выполнения удаления дело не дошло
 */
function removeOrderFromDigest($userId, $orderId, $ts) {
    $return = true;
    try {
        
        $hostId = \Digest\findHostId($userId, $orderId, $ts);
        $link = \Database\getConnectionOrFall($hostId);
        
        $result = mysqli_query(
                $link, 
                'DELETE FROM orders_digest '
                . 'WHERE user_id=' . $userId . ' AND order_id=' . $orderId . ' AND ts=' . $ts . ' '
                . 'LIMIT 1');
        
        if ($result === false) {
            throw new Exception('При удалении заказа из дайджеста на хосте [' . $hostId . '] возникла ошибка: ' . mysqli_error($link));
        }
    } catch (Exception $Exception) {
        error_log($Exception->getMessage());
        $return = false;
    }
    
    return $return;
}

/**
 * Выполнить заказ.
 * 
 * @param int $userId ID пользователя-заказчика
 * @param int $orderId ID заказа
 * 
 * @return double полученная исполнителем прибыль (-1 если не удалось выполнить заказ)
 * 
 * @throws Exception при ошибках в работе с базой
 */
function executeOrder($userId, $orderId) {
    // Найти хост с заказом пользователя
    $orderHostId = \Order\loadHostId($userId, $orderId);
    
    // Получить хост текущего пользователя
    $currentUserId = \Auth\getCurrentUserId();
    $userHostId = \User\findHostId($currentUserId);
    
    if ($orderHostId == $userHostId) {
        $executeResult = executeOrderSingleHost($orderHostId, $currentUserId, $userId, $orderId);
    } else {
        $executeResult = executeOrderMultiHost($userHostId, $orderHostId, $currentUserId, $userId, $orderId);
    }
    
    if ($executeResult === null) {
        // не удалось выполнить заказ - останавливаем задачу
        return -1;
    }
    
    if (!removeOrderFromDigest($userId, $orderId, $executeResult['ts'])) {
        \DoLog\appendRemoveFromDigest($userId, $orderId, $executeResult['ts']);
    }
    
    if (!addOrderToFinished($userHostId, $currentUserId, $executeResult['finishTs'], $executeResult['balanceDelta'], $userId, $orderId)) {
        \DoLog\appendAddToFinished($currentUserId, $executeResult['finishTs'], $executeResult['balanceDelta'], $userId, $orderId);
    }
    
    return $executeResult['balanceDelta'];
}

/**
 * Оформить выполнение заказа в ситуации, 
 * когда исполнитель и заказ находятся на одном хосте.
 * 
 * @param int $hostId ID хоста
 * @param int $executeUserId ID исполнителя
 * @param int $userId ID владельца заказа
 * @param int $orderId ID заказа
 * 
 * @return array|null данные выполненного заказа (null - если заказ не найден или уже выполнен):
 *                      balanceDelta - изменение баланса исполнителя,
 *                      ts - unix-время создания заказа (для быстрого его поиска по дайджесту),
 *                      finishTs - unix-время выполнения заказа (для добавления в ленту выполненных заказов)
 * 
 * @throws Exception при ошибках в работе с базой
 */
function executeOrderSingleHost($hostId, $executeUserId, $userId, $orderId) {
    $link = Database\getConnectionOrFall($hostId);
    if (!mysqli_begin_transaction($link)) {
        throw new Exception('Невозможно начать транзакцию на хосте [' . $hostId . ']: ' . mysqli_error($link));
    }
    
    $orderResult = mysqli_query(
            $link, 
            'SELECT ts, price '
            . 'FROM users_orders '
            . 'WHERE user_id=' . $userId . ' AND order_id=' . $orderId . ' AND finished=0 '
            . 'LIMIT 1');
    
    if ($orderResult === false) {
        mysqli_rollback($link);
        throw new Exception('Ошибка выполнения запроса на хосте [' . $hostId . ']: ' . mysqli_error($link));
    } elseif (mysqli_num_rows($orderResult) == 0) {
        mysqli_rollback($link);
        return null;
    }
    
    $orderData = mysqli_fetch_assoc($orderResult);
    mysqli_free_result($orderData);
    
    $finishTs = time();
    
    $orderUpdateResult = mysqli_query($link, 
            'UPDATE users_orders '
            . 'SET finished=1, finished_user_id=' . $executeUserId . ', finished_ts=' . $finishTs . ' '
            . 'WHERE user_id=' . $userId . ' AND order_id=' . $orderId . ' AND finished=0 '
            . 'LIMIT 1');
    
    if ($orderUpdateResult === false) {
        mysqli_rollback($link);
        throw new Exception('Ошибка выполнения запроса на хосте [' . $hostId . ']: ' . mysqli_error($link));
    } elseif (mysqli_affected_rows($link) == 0) {
        mysqli_rollback($link);
        return null;
    }
    
    $balanceDelta = number_format($orderData['price'] * COMMISSION, 2, '.', '');
    
    if ($balanceDelta != '0.00') {
        $userUpdateResult = mysqli_query($link, 'UPDATE users SET balance=balance + ' . $balanceDelta . ' WHERE id=' . $executeUserId . ' LIMIT 1');
        if ($userUpdateResult === false) {
            mysqli_rollback($link);
            throw new Exception('Ошибка выполнения запроса на хосте [' . $hostId . ']: ' . mysqli_error($link));
        } elseif (mysqli_affected_rows($link) == 0) {
            // пользователя нет на хосте - не удалось обновить его баланс
            mysqli_rollback($link);
            return null;
        }
    }
    
    if (!mysqli_commit($link)) {
        mysqli_rollback($link);
        throw new Exception('Не удалось завершить транзакцию на хосте [' . $hostId . ']: ' . mysqli_error($link));
    }
    
    return [
        'balanceDelta'  => $balanceDelta,
        'ts'            => $orderData['ts'],
        'finishTs'      => $finishTs
    ];
}

/**
 * Оформить выполнение заказа в ситуации, 
 * когда исполнитель и заказ находятся на разных хостах.
 * 
 * @param int $executeUserHostId ID хоста с пользователем
 * @param int $orderHostId ID хоста с заказом
 * @param int $executeUserId ID исполнителя
 * @param int $userId ID владельца заказа
 * @param int $orderId ID заказа
 * 
 * @return array|null данные выполненного заказа (null - если заказ не найден или уже выполнен):
 *                      balanceDelta - изменение баланса исполнителя,
 *                      ts - unix-время создания заказа (для быстрого его поиска по дайджесту),
 *                      finishTs - unix-время выполнения заказа (для добавления в ленту выполненных заказов)
 * 
 * @throws Exception при ошибках в работе с базой
 */
function executeOrderMultiHost($executeUserHostId, $orderHostId, $executeUserId, $userId, $orderId) {
    $orderLink = Database\getConnectionOrFall($orderHostId);
    if (!mysqli_begin_transaction($orderLink)) {
        throw new Exception('Невозможно начать транзакцию на хосте [' . $orderHostId . ']: ' . mysqli_error($orderLink));
    }
    
    $orderResult = mysqli_query(
            $orderLink, 
            'SELECT ts, price '
            . 'FROM users_orders '
            . 'WHERE user_id=' . $userId . ' AND order_id=' . $orderId . ' AND finished=0 '
            . 'LIMIT 1');
    
    if ($orderResult === false) {
        mysqli_rollback($orderLink);
        throw new Exception('Ошибка выполнения запроса на хосте [' . $orderHostId . ']: ' . mysqli_error($orderLink));
    } elseif (mysqli_num_rows($orderResult) == 0) {
        mysqli_rollback($orderLink);
        return null;
    }
    
    $orderData = mysqli_fetch_assoc($orderResult);
    mysqli_free_result($orderData);
    
    $finishTs = time();
    
    $orderUpdateResult = mysqli_query($orderLink, 
            'UPDATE users_orders '
            . 'SET finished=1, finished_user_id=' . $executeUserId . ', finished_ts=' . $finishTs . ' '
            . 'WHERE user_id=' . $userId . ' AND order_id=' . $orderId . ' AND status=0 '
            . 'LIMIT 1');
    
    if ($orderUpdateResult === false) {
        mysqli_rollback($orderLink);
        throw new Exception('Ошибка выполнения запроса на хосте [' . $orderHostId . ']: ' . mysqli_error($orderLink));
    } elseif (mysqli_affected_rows($orderLink) == 0) {
        mysqli_rollback($orderLink);
        return null;
    }
    
    if (!mysqli_commit($orderLink)) {
        mysqli_rollback($orderLink);
        throw new Exception('Не удалось завершить транзакцию на хосте [' . $orderHostId . ']: ' . mysqli_error($orderLink));
    }
    
    
    $balanceDelta = number_format($orderData['price'] * COMMISSION, 2, '.', '');
    if ($balanceDelta != '0.00') {
        // если нужно обновить баланс пользователя
        if (!updateUserBalance($executeUserHostId, $executeUserId, $balanceDelta)) {
            \DoLog\appendUpdateUserBalance($executeUserId, $balanceDelta);
        }
    }
    
    return [
        'balanceDelta'  => $balanceDelta,
        'ts'            => $orderData['ts'],
        'finishTs'      => $finishTs
    ];
}

/**
 * @param int $userHostId ID хоста с данными пользователя
 * @param int $userId ID пользователя
 * @param double $balanceDelta величина, на которую следует изменить баланс пользователя
 * 
 * @return bool удалось ли обновить баланс пользователя
 */
function updateUserBalance($userHostId, $userId, $balanceDelta) {
    $return = true;
    try {
		$userHostLink = \Database\getConnectionOrFall($userHostId);
        $result = mysqli_query($userHostLink, 'UPDATE users SET balance= balance + ' . $balanceDelta . ' WHERE id=' . $userId . ' LIMIT 1');
        if ($result === false) {
            throw new Exception('При обновлении баланса на хосте [' . $userHostId . '] возникал ошибка: ' . mysqli_error($userHostLink));
        } elseif (mysqli_affected_rows($userHostLink) == 0) {
            throw new Exception('Не удалось обновить баланс пользователя [' . $userId . '] на хосте [ ' . $userHostId . ']: пользователь не найден');
        }
    } catch (Exception $Exception) {
		error_log($Exception->getMessage());
        $return = false;
    }
    
    return $return;
}

/**
 * Проверяет, авторизован ли пользователь.
 * Если нет - возвращает редирект пользователю на страницу авторизации.
 */
function checkUserAuthorized() {
    try {
        $authorized = (\Auth\authorize() !== null);
    } catch (Exception $Exception) {
        error_log($Exception->getMessage());
        $authorized = false;
    }
    
    if (!$authorized) {
        \Response\redirect('/login.php');
    }
}

/**
 * Добавить в заказ в список завершенных заказов пользователя.
 * 
 * @param int $finishUserHostId ID хоста, на котором лежат данные исполнителя
 * @param int $finishUserId ID исполнителя
 * @param int $finishTs unix-время выполнения заказа
 * @param string $balanceDelta полученная исполнителем сумма денег
 * @param int $userId ID заказчика
 * @param int $orderId ID заказа
 * 
 * @return bool удалось ли добавить запись
 */
function addOrderToFinished($finishUserHostId, $finishUserId, $finishTs, $balanceDelta, $userId, $orderId) {
    $return = true;
    try {
        $link = \Database\getConnectionOrFall($finishUserHostId);
        $hostId = (int) \Database\fetchOne(
                $link, 
                'SELECT host_id '
                . 'FROM finished_orders_shards '
                . 'WHERE finished_user_id=' . $finishUserId . ' AND from_finished_ts <= ' . $finishTs . ' '
                . 'ORDER BY from_finished_ts ASC '
                . 'LIMIT 1');
        
        if ($hostId <= 0) {
            throw new Exception(
                    'Не удалось найти хост для добавления записи о выполнении заказа '
                    . '[' . $finishUserId . ', ' . $finishTs . ', ' . $userId . ', ' . $orderId . ']');
        }
        
        $finishedLink = \Database\getConnectionOrFall($hostId);
        $result = mysqli_query(
                $finishedLink, 
                'INSERT INGORE INTO finished_orders(finished_user_id, finished_ts, income, user_id, order_id) '
                . 'VALUES(' . $finishUserId . ', ' . $finishTs . ', ' . $balanceDelta . ', ' . $userId . ', ' . $orderId . ')');
        
        if ($result === false) {
            throw new Exception('При выполнении запроса на хосте [' . $hostId . '] возникла ошибка: ' . mysqli_error($finishedLink));
        }
        
    } catch (Exception $Exception) {
        error_log($Exception->getMessage());
        $return = false;
    }
    
    return $return;
}
