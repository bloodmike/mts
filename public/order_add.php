<?php
/** 
 * Добавление заказа.
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

ignore_user_abort(true);
set_time_limit(60);

/**
 * @return array результат обработки запроса на добавление заказа
 */
function addOrder() {
    $response = [];
    try {
        if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') != 'POST') {
            throw new Exception('Неправильный метод запроса');
        }
        
        if (\Auth\authorize() === null) {
            return \Response\jsonAddError($response, \Error\USER_NOT_AUTHORIZED);
        }
        
        $price = (float)filter_input(INPUT_POST, 'price');
        if ($price < 0 || $price > 1000000) {
            return \Response\jsonAddError($response, \Error\INCORRECT_ORDER_PRICE);
        }
        
        $orderData = \Action\createOrder($price);
		if ($orderData === null) {
			\Response\jsonAddError($response, \Error\UNABLE_TO_ADD_ORDER);
		} else {
			$response['order'] = $orderData;
		}
        
    } catch (Exception $Exception) {
        error_log($Exception->getFile() . '[' . $Exception->getLine() . ']: ' .  $Exception->getMessage());
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }
	
	return $response;
}

echo json_encode(addOrder());
