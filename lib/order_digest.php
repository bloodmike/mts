<?php
/** 
 * Функции для работы с дайджестом заказов - списком заказов всех невыполненных заказов пользователей.
 * 
 * @author mkoshkin
 */
namespace Digest;

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