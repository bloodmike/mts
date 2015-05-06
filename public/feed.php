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

require_once('../templates/html.php');
