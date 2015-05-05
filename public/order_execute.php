<?php
/** 
 * Обработать заявку
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

/**
 * @return array результат выполнения заказа
 */
function executeOrder() {
    $response = [];

    try {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
        
        if ($userId <= 0 || $orderId <= 0) {
            $response['error'] = \Error\ORDER_NOT_FOUND;
            return $response;
        }
        
        $authorizedUser = \Auth\authorize();
		
        if ($authorizedUser === null) {
            $response['error'] = \Error\USER_NOT_AUTHORIZED;
			return $response;
        }
        
		if ($userId == $authorizedUser['id']) {
			$response['error'] = \Error\CANNT_EXECUTE_OWN_ORDER;
			return $response;
		}
		
		$balanceDelta = \Action\executeOrder($userId, $orderId);
		if ($balanceDelta == -1) {
			$response['error'] = \Error\ORDER_ALREADY_EXECUTED;
		} else {
			$response['balanceDelta'] = $balanceDelta;
		}
    } catch (Exception $Exception) {
		trigger_error($Exception->getMessage(), E_ERROR);
        $response['error'] = \Error\PROCESSING_ERROR;
    }
    
    return $response;
}

echo json_encode(executeOrder(), JSON_UNESCAPED_UNICODE);
