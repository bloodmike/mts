<?php
/** 
 * Функции для работы с дайджестом заказов - списком заказов всех невыполненных заказов пользователей.
 * 
 * @author mkoshkin
 */
namespace Digest;

use Exception;

/**
 * Ограничение сверху на количество записей в дайджесте, которые следует считать при проверке наличия новых записей
 */
const NEW_ORDERS_COUNT_LIMIT = 100;

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
            'SELECT host_id FROM orders_digest_shards '
            . "WHERE from_ts <= " . $ts . " AND " . $ts . " < to_ts AND from_hash <= '" . $hash . "' AND '" . $hash . "' < to_hash "
            . "LIMIT 1");
    
    if ($hostId === null) {
        throw new Exception('Не удалось найти хост для заказа [' . $userId . ', ' . $orderId . ', ' . $ts . ']');
    }
    
    return $hostId;
}

/**
 * @param \mysqli $link подключение к базе данных с информацией о шардах дайджеста
 * @param int $ts unix-время, по которому определяется период, часть которого нужно искать в дайджесте
 * 
 * @return int[] ID хостов, на которых расположены части дайджеста, включающие указанный период времени
 */
function findHostIdsForTime($link, $ts) {
    return \Database\fetchSingle(
            $link, 
            'SELECT host_id '
            . 'FROM orders_digest_shards '
            . 'WHERE from_ts <= ' . $ts . ' AND ' . $ts . ' < to_ts');
}

/**
 * @param \mysqli $link подключение к базе данных с информацией о шардах дайджеста
 * @param int $ts unix-время, по которому определяется период, часть которого нужно искать в дайджесте
 * 
 * @return array данные хостов, на которых расположены части дайджеста, включающие указанный период времени:
 *                  [host_id, from_ts, to_ts]
 */
function findHostsInfoForTime($link, $ts) {
    return \Database\fetchAll(
            $link, 
            'SELECT host_id, from_ts, to_ts '
            . 'FROM orders_digest_shards '
            . 'WHERE from_ts <= ' . $ts . ' AND ' . $ts . ' < to_ts');
}

/**
 * Загружает заказы из дайджеста
 * 
 * @global int $rootDatabaseHostId ID хоста, к которому нужно обращаться по умолчанию
 * 
 * @param int $limit количество загружаемых записей
 * @param int $maxTs ограничение по времени добавления сверху
 * @param int $ignoreUserId ID заказчика заказа, который требуется пропустить
 * @param int $ignoreOrderId ID заказа, который требуется пропустить
 * 
 * @return array данные заказов
 * 
 * @throws Exception при ошибках в работе с базой
 */
function loadOrders($limit, $maxTs, $ignoreUserId, $ignoreOrderId) {
    global $rootDatabaseHostId;
    
    $link = \Database\getConnectionOrFall($rootDatabaseHostId);
    $orders = [];
    
	$where = '';
	if ($ignoreUserId > 0 && $ignoreOrderId > 0) {
		// условие для исключения последнего в списке заказа от предыдущего запроса
        $where = '(ts = ' . $maxTs . ' AND user_id = ' . $ignoreUserId . ' AND order_id < ' . $ignoreOrderId . ') OR '
                        . '(ts = ' . $maxTs . ' AND user_id < ' . $ignoreUserId . ') OR ts < ' . $maxTs;
	} else {
		$where = 'ts <= ' . $maxTs;
	}
	
    do {
        $hostIds = findHostIdsForTime($link, $maxTs);
        
        if (count($hostIds) == 0) {
            break;
        }
        
        $shardOrders = [];
        
        foreach ($hostIds as $hostId) {
            $digestLink = \Database\getConnectionOrFall($hostId);
            $shardOrders[$hostId] = \Database\fetchAll(
                    $digestLink, 
                    'SELECT ts, user_id, order_id, price '
                    . 'FROM orders_digest '
                    . 'WHERE ' . $where . ' '
                    . 'ORDER BY ts DESC, user_id DESC, order_id DESC '
                    . 'LIMIT ' . ($limit - count($orders)));
        }
		
        $foundOrders = mergeSortOrdersDesc($shardOrders, ($limit - count($orders)));
		$lastOrder = end($foundOrders);
		if ($lastOrder !== false) {
			$maxTs = $lastOrder['ts'] - 1; // TODO: не должно быть -1
		} else {
			break;
		}
		
		$where = 'ts <= ' . $maxTs;
        $orders = array_merge($orders, $foundOrders);
    } while (count($orders) < $limit);
    
    return $orders;
}

/**
 * Загружает заказы из дайджеста
 * 
 * @global int $rootDatabaseHostId ID хоста, к которому нужно обращаться по умолчанию
 * 
 * @param int $limit количество загружаемых записей
 * @param int $minTs ограничение по времени добавления сверху
 * @param int $ignoreUserId ID заказчика заказа, который требуется пропустить
 * @param int $ignoreOrderId ID заказа, который требуется пропустить
 * 
 * @return array данные заказов
 * 
 * @throws Exception при ошибках в работе с базой
 */
function loadNewOrders($limit, $minTs, $ignoreUserId, $ignoreOrderId) {
    global $rootDatabaseHostId;
    
    $link = \Database\getConnectionOrFall($rootDatabaseHostId);
    $orders = [];
    
	$where = '';
	if ($ignoreUserId > 0 && $ignoreOrderId > 0) {
		// условие для исключения последнего в списке заказа от предыдущего запроса
        $where = '(ts = ' . $minTs . ' AND user_id = ' . $ignoreUserId . ' AND order_id > ' . $ignoreOrderId . ') OR '
                        . '(ts = ' . $minTs . ' AND user_id > ' . $ignoreUserId . ') OR ts > ' . $minTs;
	} else {
		$where = 'ts >= ' . $minTs;
	}
	
    do {
        $hostsInfo = findHostsInfoForTime($link, $minTs);
        
        if (count($hostsInfo) == 0) {
            break;
        }
        
        $shardOrders = [];
		$minTs = null;
		
        foreach ($hostsInfo as $hostInfo) {
			$hostId = $hostInfo['host_id'];
            $digestLink = \Database\getConnectionOrFall($hostId);
            $shardOrders[$hostId] = \Database\fetchAll(
                    $digestLink, 
                    'SELECT ts, user_id, order_id, price '
                    . 'FROM orders_digest '
                    . 'WHERE ' . $where . ' '
                    . 'ORDER BY ts ASC, user_id ASC, order_id ASC '
                    . 'LIMIT ' . ($limit - count($orders)));
			
			if ($minTs === null || $minTs > $hostInfo['to_ts']) {
				$minTs = $hostInfo['to_ts'];
			}
        }
		
        $foundOrders = mergeSortOrdersAsc($shardOrders, ($limit - count($orders)));
		$lastOrder = end($foundOrders);
		if ($lastOrder === false) {
			break;
		}
		
		$where = 'ts >= ' . $minTs;
        $orders = array_merge($orders, $foundOrders);
    } while (count($orders) < $limit);
    
    return $orders;
}

/**
 * @param array $shardOrders сгруппированные по ID хостов заказы
 * @param int $limit предельное количество записей в списке, который нужно получить
 * 
 * @return array отсортированный список, состоящий из переданных заказов и ограниченный по длине
 */
function mergeSortOrdersDesc($shardOrders, $limit) {
    $result = [];
    
    if (count($shardOrders) == 1) {
        return array_slice(reset($shardOrders), 0, $limit);
    }
    
    do {
        $allEmpty = true;
        $maxOrderTsKey = null;
        
        foreach ($shardOrders as $key => &$orders) {
            if (current($orders) !== false &&
                    ($maxOrderTsKey === null || 
                    current($shardOrders[$maxOrderTsKey])['ts'] < current($orders)['ts'])) {
                
                $maxOrderTsKey = $key;
                $allEmpty = false;
            }
        }
        
        if ($maxOrderTsKey !== null) {
            $result[] = current($shardOrders[$maxOrderTsKey]);
            next($shardOrders[$maxOrderTsKey]);
        }
    } while (!$allEmpty && count($result) < $limit);
    
    return $result;
}

/**
 * @param array $shardOrders сгруппированные по ID хостов заказы
 * @param int $limit предельное количество записей в списке, который нужно получить
 * 
 * @return array отсортированный по возрастанию список, состоящий из переданных заказов и ограниченный по длине
 */
function mergeSortOrdersAsc($shardOrders, $limit) {
    $result = [];
    
    if (count($shardOrders) == 1) {
        return array_slice(reset($shardOrders), 0, $limit);
    }
    
    do {
        $allEmpty = true;
        $minOrderTsKey = null;
        
        foreach ($shardOrders as $key => &$orders) {
            if (current($orders) !== false &&
                    ($minOrderTsKey === null || 
                    current($shardOrders[$minOrderTsKey])['ts'] > current($orders)['ts'])) {
                
                $minOrderTsKey = $key;
                $allEmpty = false;
            }
        }
        
        if ($minOrderTsKey !== null) {
            $result[] = current($shardOrders[$minOrderTsKey]);
            next($shardOrders[$minOrderTsKey]);
        }
    } while (!$allEmpty && count($result) < $limit);
    
    return $result;
}

/**
 * @param int $minTs минимальное unix-время заказа (включительно)
 * @param int $firstUserId ID заказчика первого заказа, который нужно пропустить (0 - заказы пропускать не нужно)
 * @param int $firstOrderId ID первого заказа, который нужно пропустить (0 - заказы пропускать не нужно)
 * @param int $ignoredCount количество записей, которые следует проигнорировать
 * 
 * @return int количество заказов в ленте, которые находятся выше указанного времени/заказа, за вычетом указанного количества игнорируемых записей;
 *              воизбежание перегрузок при передаче старого значения minTs верхний предел этой величины - 100.
 */
function loadNewOrdersCount($minTs, $firstUserId, $firstOrderId, $ignoredCount) {
    global $rootDatabaseHostId;
    $link = \Database\getConnectionOrFall($rootDatabaseHostId);
    
    $newCount = 0;
    
    do {
        $hostsInfo = findHostsInfoForTime($link, $minTs);
        if (count($hostsInfo) == 0) {
            break;
        }
        
        if ($firstOrderId > 0 && $firstUserId > 0) {
            $where = '(ts=' . $minTs . ' AND user_id=' . $firstUserId . ' AND order_id > ' . $firstOrderId . ') OR '
                    . '(ts=' . $minTs . ' AND user_id > ' . $firstUserId . ') OR '
                    . 'ts > ' . $minTs;
            $firstOrderId = 0;
            $firstUserId = 0;
        } else {
            $where = 'ts >= ' . $minTs;
        }
        
        foreach ($hostsInfo as $digestHostInfo) {
            $link = \Database\getConnectionOrFall($digestHostInfo['host_id']);
            $newCount += (int) \Database\fetchOne(
                    $link, 
                    'SELECT COUNT(*) FROM orders_digest WHERE ' . $where);
            
            $minTs = max($minTs, $digestHostInfo['to_ts']);
            
            if ($newCount >= NEW_ORDERS_COUNT_LIMIT + $ignoredCount) {
                // если найдено более 100 новых записей - останавливаем проверку
                break;
            }
        }
        
    } while ($newCount < NEW_ORDERS_COUNT_LIMIT + $ignoredCount);
    
    
    return max(0, $newCount - $ignoredCount);
}
