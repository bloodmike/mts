<?php
/** 
 * Дайджест заказов
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

\Action\checkUserAuthorized();

/**
 * Страница с лентой заказов
 */
function htmlContent() {
    
}

$pageTitle = 'Лента заказов';
$pageMenu = 'feed';

require_once('../templates/html.php');
