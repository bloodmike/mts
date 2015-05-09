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
	<div id="add-order-error-message" class="error-message"></div>
	
	<form id="add-order-form">
		Заказ на сумму <input type="text" id="add-order-form__price" value="" placeholder="100.00" style="width: 70px; text-align: right;"/> руб. <input type="submit" value="Добавить"/>
	</form>
</div>

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
