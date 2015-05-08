<?php
/**
 * Функции для работы с заказами
 * 
 * @author mkoshkin
 */
namespace Order;

use Exception;
use mysqli;

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
 * @return array информация о хосте, на котором будет размещен заказ (null - если хост не найден):
 *				host_id: ID хоста,
 *				from_order_id: минимальный ID хоста на хосте
 * 
 * @throws Exception при проблемах с запросами
 */
function loadHostInfoForNewOrder($userId) {
    $userHostLink = \User\getHostConnection($userId);
    if ($userHostLink === null) {
        // не найден хост пользователя
        return null;
    }
    
    return fetchHostInfoForNewOrder($userHostLink, $userId);
}

/**
 * @param \mysqli $link подключение к хосту с данными пользователя
 * @param int $userId ID пользователя
 * 
 * @return array|null данные о хосте, куда добавляются новые запросы пользователя:
 *						host_id , from_order_id
 */
function fetchHostInfoForNewOrder(\mysqli $link, $userId) {
    return \Database\fetchRow(
            $link, 
            'SELECT host_id, from_order_id FROM users_orders_shards WHERE user_id=' . $userId . ' ORDER BY from_order_id DESC LIMIT 1');
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
    
    $userHostLink = \User\getHostConnection($userId);
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
 * @param \mysqli $link подключение к хосту с данными пользователя
 * @param int $userId ID пользователя
 * @param int $orderId ID заказа
 * 
 * @return array|null данные хоста, где может находиться указанный заказ пользователя: host_id, from_order_id;
 *              null - если заказа нет пользователя
 */
function fetchHostInfo(\mysqli $link, $userId, $orderId) {
    return \Database\fetchRow(
            $link, 
            'SELECT host_id, from_order_id '
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
    if ($maxOrderId !== null && $maxOrderId <= 1) {
        // ID заказов начинаются с единицы, максимум = 1 - значит, более ранних заказов нет
        return [];
    }
    
    $userHostLink = \User\getHostConnection($userId);
    if ($userHostLink === null) {
        return [];
    }
    
    $orders = [];
    
    do {
        // получает хост, с которого нужно забирать записи
        if ($maxOrderId === null) {
            $hostInfo = fetchHostInfoForNewOrder($userHostLink, $userId);
        } else {
            $hostInfo = fetchHostInfo($userHostLink, $userId, $maxOrderId - 1);
        }
        
		if ($hostInfo === null) {
			return [];
		}
		
        $link = Database\getConnection($hostInfo['host_id']);
        if ($link === false) {
            throw new Exception('Не удалось подключиться к хосту [' . $hostInfo['host_id'] . ']');
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
            // в шарде нет записей - нужно переключиться на следующую шарду
			$maxOrderId = $hostInfo['from_order_id'];
        }
        
    } while (count($orders)  < $limit || $maxOrderId <= 1);
    
    return $orders;
}

/**
 * @todo протестировать
 * 
 * @param int $userId ID исполнителя
 * @param int $maxFinishTs ограничение по времени выполнения сверху (не включительно)
 * @param int $limit ограничение длины списка
 * 
 * @return array список выполненных пользователем заказов указанной длины
 * 
 * @throws Exception 
 */
function loadFinishedListForUser($userId, $maxFinishTs, $limit = 50) {
    $userHostId = \User\findHostId($userId);
    $userHostLink = \Database\getConnectionOrFall($userHostId);
    
    $orders = [];
    
    do {
        $finishedHostInfo = \Database\fetchRow(
                $userHostLink, 
                'SELECT host_id, from_finished_ts '
                . 'FROM finished_orders_shards '
                . 'WHERE finished_user_id=' . $userId . ' AND from_finished_ts < ' . $maxFinishTs . ' '
                . 'ORDER BY from_finished_ts ASC '
                . 'LIMIT 1');
        
        if ($finishedHostInfo === null) {
            break;
        }
        
        $finishedLink = \Database\getConnectionOrFall($finishedHostInfo['host_id']);
        $ordersPack = \Database\fetchAll(
                $finishedLink,
                'SELECT finished_ts, user_id, order_id, income '
                . 'FROM finished_orders '
                . 'WHERE finished_user_id=' . $userId . ' AND finished_ts < ' . $maxFinishTs . ' '
                . 'ORDER BY finished_ts DESC '
                . 'LIMIT ' . ($limit - count($orders)));
        
        if (count($ordersPack) > 0) {
            $orders = array_merge($orders, $ordersPack);
            $maxFinishTs = end($ordersPack)['finished_ts'];
        } else {
            $maxFinishTs = $finishedHostInfo['from_finished_ts'];
        }
    } while (count($orders) < $limit);
}
