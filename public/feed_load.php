<?php
/** 
 * Скрипт получения заказов для отрисовки ленты заказов
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

/**
 * @return array загрузка дайджеста заказов и пользователей, которым принадлежат заказы
 */
function loadDigest() {
    $response = [];
    
    try {
        $ts = (int) filter_input(INPUT_GET, 'ts', FILTER_SANITIZE_NUMBER_INT);
        if ($ts <= 0) {
            return ['orders' => []];
        }
        
		$ignoreUserId = (int) filter_input(INPUT_GET, 'last_user_id', FILTER_SANITIZE_NUMBER_INT);
		$ignoreOrderId = (int) filter_input(INPUT_GET, 'last_order_id', FILTER_SANITIZE_NUMBER_INT);
		
		$limit = 10;		
        $response['orders'] = \Digest\loadOrders($limit, $ts, $ignoreUserId, $ignoreOrderId);
		$response['orders_more'] = (count($response['orders']) >= $limit);
		
        $userIdsMap = [];
        foreach ($response['orders'] as $order) {
            $userIdsMap[$order['user_id']] = true;
        }
        
        $response['users'] = \User\loadListByIds(array_keys($userIdsMap));

    } catch (Exception $Exception) {
		error_log($Exception->getFile() . '[' . $Exception->getLine() . ']: ' .  $Exception->getMessage());
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }

    return $response;
}

echo json_encode(loadDigest(), JSON_UNESCAPED_UNICODE);
