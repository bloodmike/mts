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
        if ($price < 0) {
            return \Response\jsonAddError($response, \Error\INCORRECT_ORDER_PRICE);
        }
        
        $response['order'] = \Action\createOrder($price);
        
    } catch (Exception $Exception) {
        trigger_error($Exception->getMessage(), E_USER_ERROR);
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }
}

echo json_encode(addOrder());
