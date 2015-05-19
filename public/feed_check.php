<?php
/** 
 * Скрипт проверки наличия новых заказов в ленте
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

/**
 * @return array проверяет, есть ли более новые заказы
 */
function feedCheck() {
    $response = [];
    
    try {
        $ts = (int) filter_input(INPUT_GET, 'ts', FILTER_SANITIZE_NUMBER_INT);
        $ignoreUserId = (int) filter_input(INPUT_GET, 'first_user_id', FILTER_SANITIZE_NUMBER_INT);
		$ignoreOrderId = (int) filter_input(INPUT_GET, 'first_order_id', FILTER_SANITIZE_NUMBER_INT);
        
        // количество заказов, которые не следует учитывать (добавлены самим пользователем с текущей / другой вкладки)
        $ignoredOrdersCount = (int) filter_input(INPUT_GET, 'ignored_count', FILTER_SANITIZE_NUMBER_INT);
        
        if ($ts <= 0) {
            $response['orders_count'] = 0;
            return $response;
        }
        
        $i = 0;
        
        do {
            $ordersCount = \Digest\loadNewOrdersCount($ts, $ignoreUserId, $ignoreOrderId, $ignoredOrdersCount);
            if ($ordersCount > 0) {
                break;
            }
            $i++;
            sleep(1);
        } while ($i < FEED_CHECKS_COUNT);
        
        $response['orders_count'] = $ordersCount;
        
    } catch (Exception $Exception) {
        error_log($Exception->getFile() . '[' . $Exception->getLine() . ']: ' .  $Exception->getMessage());
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }
    
    return $response;
}

echo json_encode(feedCheck(), JSON_UNESCAPED_UNICODE);
