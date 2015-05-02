<?php
/* 
 * Функции логгирования отложенных операций.
 * Используются в ситуации, когда необходимо выполнить какое-то действие,
 * которое в настоящий момент не удаётся выполнить из-за проблем с компонентами.
 * 
 * Выполнение отложенных операций должно быть реализовано таким образом, 
 * чтобы при попытке несколько раз выполнить операцию не нарушалась целостность данных.
 * 
 * @author mkoshkin
 */

namespace DoLog;

/**
 * Удаление заказа из дайджета
 */
const REMOVE_ORDER_FROM_DIGEST = 1;

/**
 * Вносит запись о действии в лог.
 * 
 * @param int $actionCode код действия
 * @param array $data параметры действия
 * 
 * @return bool удалось ли внести запись
 */
function writeRecord($actionCode, $data) {
    $filename = '../logs/' . date('Ymd') . '.log';
    
    $t = 0;
    do {
        if ($t > 0) {
            usleep(mt_rand(1000, 5000));
        }
        
        if ($h = fopen($filename, "a")) {
            break;
        }
        $t++;
        if ($t > 4) {
            return false;
        }
    } while ($h === false);
    
    $result = fwrite($h, $actionCode . '|' . implode('|', $data) . PHP_EOL);
    fclose($h);
    return $result;
}

/**
 * Добавить в лог операций удаление заказа из дайджеста
 * 
 * @param int $userId
 * @param int $orderId
 * @param int $ts
 * 
 * @return bool удалось ли добавить запись в лог
 */
function appendRemoveFromDigest($userId, $orderId, $ts) {
    return writeRecord(REMOVE_ORDER_FROM_DIGEST, [$userId, $orderId, $ts]);
}
