<?php
/**
 * Функции для работы с заказами
 * 
 * @author mkoshkin
 */
namespace Order;

/**
 * Загружать все заказы пользователя
 */
const LOAD_USER_ORDERS_ALL = 0;

/**
 * Загружать невыполненные заказы пользователя
 */
const LOAD_USER_ORDERS_ACTIVE = 1;

/**
 * Загружать выполненные заказы пользователя
 */
const LOAD_USER_ORDERS_FINISHED = 2;

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
function loadHostIdForNewOrder($userId) {
    $userHostLink = User\getHostConnection($userId);
    if ($userHostLink === null) {
        // не найден хост пользователя
        return 0;
    }
    
    return fetchHostIdForNewOrder($userHostLink, $userId);
}

/**
 * @param \mysqli $link подключение к хосту с данными пользователя
 * @param int $userId ID пользователя
 * 
 * @return int ID хоста, куда добавляются заказы пользователя
 */
function fetchHostIdForNewOrder(\mysqli $link, $userId) {
    return (int) \Database\fetchOne(
            $link, 
            'SELECT host_id FROM users_orders_shards WHERE user_id=' . $userId . ' ORDER BY from_order_id DESC LIMIT 1');
}

/**
 * @param \mysqli $link подключение к хосту с данными пользователя
 * @param int $userId ID пользователя
 * 
 * @return int ID хоста, куда добавляются заказы пользователя
 */
/*function fetchHostInfoForNewOrder(\mysqli $link, $userId) {
    return (int) \Database\fetchOne(
            $link, 
            'SELECT host_id FROM users_orders_shards WHERE user_id=' . $userId . ' ORDER BY from_order_id DESC LIMIT 1');
}*/

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

    return fetchHostId($userHostLink, $userId, $orderId);
}

/**
 * @param \mysqli $link подключение к хосту с данными пользователя
 * @param int $userId ID пользователя
 * @param int $orderId ID заказа
 * 
 * @return int ID хоста, где может находиться указанный заказ пользователя;
 *              0 - если заказа нет пользователя
 */
function fetchHostId(\mysqli $link, $userId, $orderId) {
    return (int) \Database\fetchOne(
            $link, 
            'SELECT host_id '
            . 'FROM users_orders_shards '
            . 'WHERE user_id=' . $userId . ' AND order_id <= ' . $orderId . ' '
            . 'ORDER BY order_id DESC LIMIT 1');
}

/**
 * @param int $userId ID заказчика
 * @param int $status статус загружаемых записей (см. Order\LOAD_USER_ORDERS_...)
 * @param int|null $maxOrderId ограничение по ID заказа сверху (null - нет ограничений)
 * @param int $limit количество загружаемых записей (должно быть > 0)
 * 
 * @return array массив с заказами
 * 
 * @throws Exception при ошибках в работе с базой
 */
function loadListForUser($userId, $status = LOAD_USER_ORDERS_ALL, $maxOrderId = null, $limit = 50) {
    if ($maxOrderId <= 1) {
        // ID заказов начинаются с единицы, максимум = 1 - значит, более ранних заказов нет
        return [];
    }
    
    $userHostLink = User\getHostConnection($userId);
    if ($userHostLink === null) {
        return [];
    }
    
    $orders = [];
    
    do {
        // получает хост, с которого нужно забирать записи
        if ($maxOrderId === null) {
            $hostId = fetchHostIdForNewOrder($userHostLink, $userId);
        } else {
            $hostId = fetchHostId($userHostLink, $userId, $maxOrderId - 1);
        }
        
        $link = Database\getConnection($hostId);
        if ($link === false) {
            throw new Exception('Не удалось подключиться к хосту [' . $hostId . ']');
        }
        
        // собираем условия запроса
        $where = 'user_id=' . $userId;
        if ($status == LOAD_USER_ORDERS_ACTIVE) {
            $where .= ' AND finished=0';
        } elseif ($status == LOAD_USER_ORDERS_FINISHED) {
            $where .= ' AND finished=1';
        }
        
        if ($maxOrderId !== null) {
            $where .= ' AND order_id < ' . $maxOrderId;
        }
        
        // получаем строки из базы
        $rows = Database\fetchAll(
                $link, 
                'SELECT order_id, ts, price, finished_user_id '
                . 'FROM users_orders '
                . 'WHERE ' . $where . ' '
                . 'ORDER BY order_id DESC '
                . 'LIMIT ' . ($limit - count($orders)));
        
        
        if (count($rows) > 0) {
            $orders = array_merge($orders, $rows);
            $lastRow = end($rows);
            $maxOrderId = $lastRow['order_id'];
        } else {
            // TODO: в шарде нет записей - нужно переключиться на следующую шарду
        }
        
    } while (count($orders)  < $limit || $maxOrderId == 1);
    
    return $orders;
}