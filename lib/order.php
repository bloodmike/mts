<?php
/**
 * Функции для работы с заказами
 * 
 * @author mkoshkin
 */
namespace Order;

/**
 * @param \mysqli $link подключение к шарде с заказами пользователя
 * @param int $userId ID пользователя
 * @param int $minOrderId минимальный ID заказа в шарде
 * 
 * @return int ID следующего заказа
 */
function loadNextOrderId(\mysqli $link, $userId, $minOrderId) {
    $result = mysqli_query($link, 'SELECT order_id FROM users_orders WHERE user_id=' . $userId . ' ORDER BY order_id DESC LIMIT 1');
    if ($result === false) {
        throw new Exception('Ошибка выполнения запроса: ' . mysqli_error($link));
    } elseif ($result->num_rows == 0) {
        $orderId = $minOrderId;
    } else {
        $row = $result->fetch_row();
        $orderId = (int) $row[0] + 1;
    }
    
    return $orderId;
}

/**
 * @param int $userId ID пользователя
 * 
 * @return int ID хоста, на котором следует размещать новый заказ от пользователя 
 *              0 - не найден хост для размещения заказа
 * 
 * @throws Exception при проблемах с запросами
 */
function loadHostForNewOrder($userId) {
    $userHostLink = User\getHostConnection($userId);
    if ($userHostLink === null) {
        // не найден хост пользователя
        return 0;
    }
    
    $queryResult = mysqli_query($userHostLink, 'SELECT host_id FROM users_orders_shards WHERE user_id=' . $userId . ' ORDER BY from_order_id DESC LIMIT 1');
    if ($queryResult === false) {
        throw new Exception('При выполнении запроса возникла ошибка: ' . mysqli_error($userHostLink));
    } elseif ($queryResult->num_rows == 0) {
        return 0;
    }
    
    $row = mysqli_fetch_row($userHostLink);
    return $row[0];
}

/**
 * @param int $userId ID пользователя-владельца заказа
 * @param int $orderId ID заказа
 * 
 * @return int ID хоста, на котором нужно искать заказ (0 - если пользователя/заказа нет)
 * 
 * @throws Exception при ошибках в работе с базой
 */
function loadHostId($userId, $orderId) {
    if ($orderId <= 0) {
        return 0;
    }
    
    $userHostLink = User\getHostConnection($userId);
    if ($userHostLink === null) {
        // нет подключения - нет юзера - нет заказа
        return 0;
    }
        
    $queryResult = mysqli_query(
            $userHostLink, 
            'SELECT host_id FROM users_orders_shards WHERE user_id=' . $userId . ' AND order_id <= ' . $orderId . ' ORDER BY order_id DESC LIMIT 1');
    
    if ($queryResult === false) {
        throw new Exception('При запросе возникла ошибка: ' . mysqli_error($userHostLink));
    } elseif ($queryResult->num_rows == 0) {
        return 0;
    }
    
    $row = mysqli_fetch_row($queryResult);
    return (int) $row[0];
}
