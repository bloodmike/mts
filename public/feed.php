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
    ?>
<div class="panel panel-container">
	<div id="orders-list" class="orders-list"></div>
    <div id="orders-list__load-more" class="orders-list__load-more hidden">
        <span>Загрузить еще</span>
    </div>
</div>
	<?
}

$pageTitle = 'Заказы';
$pageMenu = 'feed';
$javascripts = [
	'/js/pages/feed.js'
];

require_once('../templates/html.php');
