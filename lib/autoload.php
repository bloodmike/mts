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
require_once(__DIR__ . '/action.php');
require_once(__DIR__ . '/response.php');


// регистрируем функцию, вызываемую после выполнения скрипта
register_shutdown_function(function() {
    \Database\closeConnections();
});
