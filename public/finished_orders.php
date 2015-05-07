<?php
/**
 * Лента выполненных пользователем заказов
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

\Action\checkUserAuthorized();

/**
 * Страница с лентой выполненных заказов
 */
function htmlContent() {
    
}

$pageTitle = 'Выполненные заказы';
$pageMenu = 'finished_orders';

require_once('../templates/html.php');
