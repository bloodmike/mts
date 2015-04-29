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
 * @return int ID созданного заказа (0 - если не удалось добавить заказ)
 */
function createOrder($price) {
    
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
