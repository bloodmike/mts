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
        
        $response['orders'] = \Digest\loadOrders(50, $ts);
        $userIdsMap = [];
        foreach ($response['orders'] as $order) {
            $userIdsMap[$order['user_id']] = true;
        }
        
        $response['users'] = \User\loadListByIds(array_keys($userIdsMap));

    } catch (Exception $Exception) {
		trigger_error($Exception->getMessage(), E_USER_ERROR);
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }

    return $response;
}

echo json_response(loadDigest(), JSON_UNESCAPED_UNICODE);