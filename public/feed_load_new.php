<?php
/** 
 * Скрипт получения новых заказов
 * 
 * @author mkoshkin
 */
require_once('../lib/autoload.php');

/**
 * @return array загрузка дайджеста заказов и пользователей, которым принадлежат заказы
 */
function loadNewDigest() {
    $response = [];
    
    try {
        $ts = (int) filter_input(INPUT_GET, 'ts', FILTER_SANITIZE_NUMBER_INT);
        if ($ts <= 0) {
            return ['orders' => []];
        }
        
		$ignoreUserId = (int) filter_input(INPUT_GET, 'first_user_id', FILTER_SANITIZE_NUMBER_INT);
		$ignoreOrderId = (int) filter_input(INPUT_GET, 'first_order_id', FILTER_SANITIZE_NUMBER_INT);
		
		$limit = DIGEST_FEED_NEW_LIMIT;		
        $response['orders'] = \Digest\loadNewOrders($limit, $ts, $ignoreUserId, $ignoreOrderId);
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

echo json_encode(loadNewDigest(), JSON_UNESCAPED_UNICODE);