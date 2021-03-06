<?php
/**
 * Файл с подключениями всех библиотек
 * 
 * @author mkoshkin
 */

ini_set('display_errors', E_ALL);
error_reporting(E_ALL);

$environmentVersion = getenv('MTS_ENVIRONMENT');
if ($environmentVersion === false) {
    die('MTS_ENVIRONMENT not defined');
}

require_once(__DIR__ . '/../config/' . $environmentVersion . '.php');
require_once(__DIR__ . '/error_codes.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/user.php');
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/order.php');
require_once(__DIR__ . '/order_digest.php');
require_once(__DIR__ . '/do_log.php');
require_once(__DIR__ . '/action.php');
require_once(__DIR__ . '/response.php');

// можно определить эти константы в конфиге

// количество загружаемых в ленту записей
if (!defined('DIGEST_FEED_LIMIT')) {
    define('DIGEST_FEED_LIMIT', 12);
}

// количество загружаемых новых записей в ленту
if (!defined('DIGEST_FEED_NEW_LIMIT')) {
    define('DIGEST_FEED_NEW_LIMIT', 12);
}

// количество проверок в запросе обновлений ленты
if (!defined('FEED_CHECKS_COUNT')) {
    define('FEED_CHECKS_COUNT', 10);
}

// количество загружаемых заказов на странице "Мои заказы"
if (!defined('MY_ORDERS_LOAD_LIMIT')) {
    define('MY_ORDERS_LOAD_LIMIT', 10);
}

// количество загружаемых заказов на странице "Выполненные заказы"
if (!defined('FINISHED_ORDERS_LOAD_LIMIT')) {
    define('FINISHED_ORDERS_LOAD_LIMIT', 10);
}

// регистрируем функцию, вызываемую после выполнения скрипта
register_shutdown_function(function() {
    \Database\closeConnections();
});
