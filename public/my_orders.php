<?php
/**
 * Заказы пользователя.
 * В зависимости от параметра type можно получить выполненные/невыполненные или все заказы вместе.
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

/**
 * @return array результат загрузки заказов пользователя
 */
function loadOrders() {
	$response = [];
	try {
		if (\Auth\authorize() === null) {
            return \Response\jsonAddError($response, \Error\USER_NOT_AUTHORIZED);
		}
		
		$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_NUMBER_INT);
		if ($status != \Order\LOAD_USER_ORDERS_ACTIVE && $status != \Order\LOAD_USER_ORDERS_FINISHED) {
			$status = \Order\LOAD_USER_ORDERS_ALL;
		}
		
		$maxOrderId = filter_input(INPUT_GET, 'max_order_id', FILTER_SANITIZE_NUMBER_INT);
		if ($maxOrderId <= 0) {
			$maxOrderId = null;
		}
		
		$response['orders'] = \Order\loadListForUser(\Auth\getCurrentUserId(), $status, $maxOrderId);
	} catch (Exception $Exception) {
		trigger_error($Exception->getMessage(), E_USER_ERROR);
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
	}
	return $response;
}

echo json_encode(loadOrders());
