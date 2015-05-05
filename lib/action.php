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
    $userHostId = \User\loadById($currentUserId);
    
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
 *                      ts - unix-время создания заказа (для быстрого его поиска по дайджесту)
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
            . 'WHERE user_id=' . $userId . ' AND order_id=' . $orderId . ' AND status=0 '
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
    
    $orderUpdateResult = mysqli_query($link, 
            'UPDATE users_orders '
            . 'SET status=1, status_user_id=' . $executeUserId . ' '
            . 'WHERE user_id=' . $userId . ' AND order_id=' . $orderId . ' AND status=0 '
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
        'ts'            => $orderData['ts']
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
 *                      ts - unix-время создания заказа (для быстрого его поиска по дайджесту)
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
            . 'WHERE user_id=' . $userId . ' AND order_id=' . $orderId . ' AND status=0 '
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
    
    $orderUpdateResult = mysqli_query($orderLink, 
            'UPDATE users_orders '
            . 'SET status=1, status_user_id=' . $executeUserId . ' '
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
        if (!updateUserBalance($orderId, $userId, $balanceDelta)) {
            \DoLog\appendUpdateUserBalance($userId, $balanceDelta);
        }
    }
    
    return [
        'balanceDelta'  => $balanceDelta,
        'ts'            => $orderData['ts']
    ];
}

/**
 * @param int $hostId ID хоста
 * @param int $userId ID пользователя
 * @param double $balanceDelta 
 * 
 * @return bool удалось ли обновить баланс пользователя
 */
function updateUserBalance($hostId, $userId, $balanceDelta) {
    $return = true;
    try {
        $link = \Database\getConnectionOrFall($hostId);
        $result = mysqli_query($link, 'UPDATE users SET balance= balance + ' . $balanceDelta . ' WHERE id=' . $userId . ' LIMIT 1');
        if ($result === false) {
            throw new Exception('При обновлении баланса на хосте [' . $hostId . '] возникал ошибка: ' . mysqli_error($link));
        } elseif (mysqli_affected_rows($link) == 0) {
            throw new Exception('Не удалось обновить баланс пользователя [' . $userId . '] на хосте [ ' . $hostId . ']: пользователь не найден');
        }
    } catch (Exception $Exception) {
        $return = false;
    }
    
    return $return;
}
