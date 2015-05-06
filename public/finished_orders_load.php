<?php
/** 
 * Загрузка выполненных пользователем заказов
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

/**
 * @return array результат загрузки выполненных пользователем заказов
 */
function loadFinishedOrders() {
    
}

echo json_encode(loadFinishedOrders());
