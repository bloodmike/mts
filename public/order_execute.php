<?php
/** 
 * Обработать заявку
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

ignore_user_abort(true);
set_time_limit(60);

/**
 * @return array результат выполнения заказа
 */
function executeOrder() {
    $response = [];

    try {
        if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') != 'POST') {
            throw new Exception('Неправильный метод запроса');
        }
        
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
		error_log($Exception->getMessage());
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }
    
    return $response;
}

echo json_encode(executeOrder(), JSON_UNESCAPED_UNICODE);
