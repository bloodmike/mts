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
            return \Response\jsonAddError($response, \Error\ORDER_NOT_FOUND);
        }
        
        $authorizedUser = \Auth\authorize();
		
        if ($authorizedUser === null) {
            return \Response\jsonAddError($response, \Error\USER_NOT_AUTHORIZED);
        }
        
		if ($userId == $authorizedUser['id']) {
            return \Response\jsonAddError($response, \Error\CANNT_EXECUTE_OWN_ORDER);
		}
		
		$balanceDelta = \Action\executeOrder($userId, $orderId);
		if ($balanceDelta == -1) {
            \Response\jsonAddError($response, \Error\ORDER_ALREADY_EXECUTED);
		} else {
			$response['balanceDelta'] = $balanceDelta;
		}
    } catch (Exception $Exception) {
		trigger_error($Exception->getMessage(), E_USER_ERROR);
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }
    
    return $response;
}

echo json_encode(executeOrder(), JSON_UNESCAPED_UNICODE);
