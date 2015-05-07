<?php
/** 
 * Загрузка выполненных пользователем заказов
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

/**
 * @return array результат загрузки выполненных пользователем заказов
 */
function loadFinishedOrders() {
    $response = [];
    
    try {
        $user = \Auth\authorize();
        if ($user === null) {
            return \Response\jsonAddError($response, \Error\USER_NOT_AUTHORIZED);
        }
        
        $maxTs = (int) filter_input(INPUT_GET, 'ts', FILTER_SANITIZE_NUMBER_INT);
        if ($maxTs <= 0) {
            $maxTs = time() + 1;
        }
        
        $orders = \Order\loadFinishedListForUser($user['id'], $maxTs);
        
        $response['orders'] = $orders;
        
        $userIdsMap = [];
        
        foreach ($orders as $order) {
            $userIdsMap[$order['user_id']] = true;
        }
        
        $response['users'] = \User\loadListByIds(array_keys($userIdsMap));
        
    } catch (Exception $Exception) {
        error_log($Exception->getMessage());
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }
    
    return $response;
}

echo json_encode(loadFinishedOrders());
