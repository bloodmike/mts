<?php
/**
 * Страница с добавленными пользователем заказами
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

\Action\checkUserAuthorized();

/**
 * Отрисовка страницы с заказами
 */
function htmlContent() {
    
}

$pageTitle = 'Мои заказы';
$pageMenu = 'my_orders';

require_once('../templates/html.php');
