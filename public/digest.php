<?php
/** 
 * Скрипт получения заказов для отрисовки ленты заказов
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

/**
 * @return array
 */
function loadDigest() {
    $response = [];
    
    try {
        $ts = (int) filter_input(INPUT_GET, 'ts', FILTER_SANITIZE_NUMBER_INT);
        if ($ts <= 0) {
            return ['orders' => []];
        }
        
        $response['orders'] = \Digest\loadOrders(50, $ts);
        $userIdsMap = [];
        foreach ($response['orders'] as $order) {
            $userIdsMap[$order['user_id']] = true;
        }
        
        $response['users'] = \User\loadListByIds(array_keys($userIdsMap));

    } catch (Exception $Exception) {
        $response['error'] = 'При обработке запроса возникла ошибка';
    }

    return $response;
}

echo json_response(loadDigest(), JSON_UNESCAPED_UNICODE);
