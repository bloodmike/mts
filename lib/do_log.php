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
 * Добавление заказа в дайджест
 */
const ADD_ORDER_TO_DIGEST = 2;

/**
 * Обновление баланса пользователя
 */
const UPDATE_USER_BALANCE = 3;

/**
 * Добавить заказ в список успешно выполненных
 */
const ADD_ORDER_TO_FINISHED = 4;

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
 * @param int $userId ID заказчика
 * @param int $orderId ID заказа
 * @param int $ts unix-время создания заказа
 * 
 * @return bool удалось ли добавить запись в лог
 */
function appendRemoveFromDigest($userId, $orderId, $ts) {
    return writeRecord(REMOVE_ORDER_FROM_DIGEST, [$userId, $orderId, $ts]);
}

/**
 * Добавить в лог операций внесение заказа в дайджест
 * 
 * @param int $userId ID заказчика
 * @param int $orderId ID заказа
 * @param int $ts unix-время создания заказа
 * 
 * @return bool удалось ли внести запись в лог
 */
function appendAddToDigest($userId, $orderId, $ts) {
    return writeRecord(ADD_ORDER_TO_DIGEST, [$userId, $orderId, $ts]);
}

/**
 * Добавить в лог операций обновление баланса пользователя
 * 
 * @param int $userId ID пользователя
 * @param int $balanceDelta сумма, которую необходимо прибавить к балансу
 * 
 * @return bool удалось ли добавить запись в лог
 */
function appendUpdateUserBalance($userId, $balanceDelta) {
    return writeRecord(UPDATE_USER_BALANCE, [$userId, $balanceDelta]);
}

/**
 * Добавить в лог операций внесение заказа в список завершенных заказов пользователя
 * 
 * @param int $finishUserId ID исполнителя
 * @param int $finishTs unix-время выполнения заказа
 * @param string $balanceDelta прибыль исполнителя
 * @param int $userId ID заказчика
 * @param int $orderId ID заказа
 * 
 * @return bool удалось ли внести запись в лог
 */
function appendAddToFinished($finishUserId, $finishTs, $balanceDelta, $userId, $orderId) {
    return writeRecord(ADD_ORDER_TO_FINISHED, [$finishUserId, $finishTs, $balanceDelta, $userId, $orderId]);
}
