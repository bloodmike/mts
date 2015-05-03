<?php
/** 
 * Обработать заявку
 */

require_once('../lib/autoload.php');

/**
 * @return array 
 */
function executeOrder() {
    $response = [];

    try {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
        
        if ($userId <= 0 || $orderId <= 0) {
            $response['error'] = 'Заказ не найден';
            return $response;
        }
        
        $authorizedUser = \User\authorize();
        if ($authorizedUser === null) {
            $response['error'] = 'Вы не авторизованы';
        }
        
        
    } catch (Exception $Exception) {
        $response['error'] = 'При выполнении операции возникла ошибка. Повторите запрос позднее';
    }
    
    return $response;
}

echo json_encode(executeOrder());
