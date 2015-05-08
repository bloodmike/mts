<?php
/** 
 * Функции для работы с дайджестом заказов - списком заказов всех невыполненных заказов пользователей.
 * 
 * @author mkoshkin
 */
namespace Digest;

use Exception;

/**
 * @param int $userId ID заказчика
 * @param int $orderId ID заказа
 * 
 * @return string хэш ключа, по которому заказы распределяются в дайджесте
 */
function getHash($userId, $orderId) {
    return md5($userId . '_' . $orderId);
}

/**
 * @global int $rootDatabaseHostId ID хоста, к которому нужно обращаться по умолчанию
 * 
 * @param int $userId ID заказчика
 * @param int $orderId ID заказа
 * @param int $ts unix-время добавления заказа
 * 
 * @return int ID хоста, на котором в дайджесте должен находиться указанный заказ
 * 
 * @throws Exception если не удаётся найти хост или при работе с базой возникла ошибка
 */
function findHostId($userId, $orderId, $ts) {
    global $rootDatabaseHostId;
    $hash = getHash($userId, $orderId);
    
    $link = \Database\getConnectionOrFall($rootDatabaseHostId);
    
    $hostId = \Database\fetchOne(
            $link, 
            'SELECT host_id FROM orders_digest '
            . "WHERE from_ts <= " . $ts . " AND " . $ts . " < to_ts AND from_hash <= '" . $hash . "' AND '" . $hash . "' < to_hash "
            . "LIMIT 1");
    
    if ($hostId === null) {
        throw new Exception('Не удалось найти хост для заказа [' . $userId . ', ' . $orderId . ', ' . $ts . ']');
    }
    
    return $hostId;
}

/**
 * Загружает заказы из дайджеста
 * 
 * @global int $rootDatabaseHostId ID хоста, к которому нужно обращаться по умолчанию
 * 
 * @param int $limit количество загружаемых записей
 * @param int $maxTs ограничение по времени добавления сверху
 * 
 * @return array данные заказов
 * 
 * @throws Exception при ошибках в работе с базой
 */
function loadOrders($limit, $maxTs) {
    global $rootDatabaseHostId;
    
    $link = \Database\getConnectionOrFall($rootDatabaseHostId);
    $orders = [];
        
    do {
        $hostIds = \Database\fetchSingle(
                $link, 
                'SELECT host_id, from_ts '
                . 'FROM orders_digest_shards '
                . 'WHERE from_ts <= ' . $maxTs . ' AND ' . $maxTs . ' < to_ts');
		
        if (count($hostIds) == 0) {
            break;
        }
        
        $shardOrders = [];
        
        foreach ($hostIds as $hostId) {
            $digestLink = \Database\getConnectionOrFall($hostId);
            $shardOrders[$hostId] = \Database\fetchAll(
                    $digestLink, 
                    'SELECT * '
                    . 'FROM orders_digest '
                    . 'WHERE ts <= ' . $maxTs . ' '
                    . 'ORDER BY ts DESC, user_id DESC, order_id DESC '
                    . 'LIMIT ' . ($limit - count($orders)));
        }
		
        $foundOrders = mergeSortOrders($shardOrders, ($limit - count($orders)));
        $orders = array_merge($orders, $foundOrders);
    } while (count($orders) < $limit);
    
    return $orders;
}

/**
 * @todo протестировать
 * 
 * @param array $shardOrders сгруппированные по ID хостов заказы
 * @param int $limit предельное количество записей в списке, который нужно получить
 * 
 * @return array отсортированный список, состоящий из переданных заказов и ограниченный по длине
 */
function mergeSortOrders($shardOrders, $limit) {
    $result = [];
    
    if (count($shardOrders) == 1) {
        return array_slice($shardOrders, 0, $limit);
    }
    
    do {
        $allEmpty = true;
        $maxOrderTsKey = null;
        
        foreach ($shardOrders as $key => &$orders) {
            if (current($orders) !== false &&
                    ($maxOrderTsKey === null || 
                    current($shardOrders[$maxOrderTsKey])['ts'] < current($orders)['ts'])) {
                
                $minOrderTsKey = $key;
                $allEmpty = false;
            }
        }
        
        if ($minOrderTsKey !== null) {
            $result[] = current($shardOrders[$maxOrderTsKey]);
            next($shardOrders[$maxOrderTsKey]);
        }
    } while (!$allEmpty && count($result) < $limit);
    
    return $result;
}
