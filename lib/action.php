<?php
/**
 * Функции, реализующие пользовательские действия.
 * Вызов этих функций происходит после авторизации пользователя.
 * 
 * @author mkoshkin
 */
namespace Action;

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
 * Добавляет заказ в ленту заказов
 * 
 * @todo сделать
 * 
 * @param int $userId
 * @param int $orderId
 * @param int $ts
 * @param double $price
 * 
 * @return bool
 */
function addOrderToDigest($userId, $orderId, $ts, $price) {
    return false;
}

/**
 * Удаляет заказ из ленты заказов
 * 
 * @todo сделать
 * 
 * @param int $userId
 * @param int $orderId
 * @param int $ts
 * 
 * @return bool
 */
function removeOrderFromDigest($userId, $orderId, $ts) {
    return false;
}

/**
 * Выполнить заказ.
 * 
 * @param int $userId ID пользователя-заказчика
 * @param int $orderId ID заказа
 * 
 * @return double полученная исполнителем прибыль (-1 если не удалось выполнить заказ)
 */
function executeOrder($userId, $orderId) {
    
}
