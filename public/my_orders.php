<?php
/**
 * Страница с добавленными пользователем заказами
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

\Action\checkUserAuthorized();

$loadStatus = (string) filter_input(INPUT_GET, 'st', FILTER_SANITIZE_STRING);
$loadStatusId = \Order\LOAD_USER_ORDERS_ACTIVE;
if ($loadStatus == 'all') {
	$loadStatusId = \Order\LOAD_USER_ORDERS_ALL;
} elseif ($loadStatus == 'executed') {
	$loadStatusId = \Order\LOAD_USER_ORDERS_FINISHED;
}

/**
 * Отрисовка страницы с заказами
 */
function htmlContent() {
	global $loadStatusId;
    ?>
<div class="panel panel-container my-orders-page">
	<div class="my-orders-page__filter">
		<a href="/my_orders.php"<?if($loadStatusId == \Order\LOAD_USER_ORDERS_ACTIVE){?> class="selected"<?}?>>Невыполненные</a>
		<a href="/my_orders.php?st=executed"<?if($loadStatusId == \Order\LOAD_USER_ORDERS_FINISHED){?> class="selected"<?}?>>Выполненные</a>
		<a href="/my_orders.php?st=all"<?if($loadStatusId == \Order\LOAD_USER_ORDERS_ALL){?> class="selected"<?}?>>Все</a>
	</div>
	
	<table class="orders-table" id="orders-table">
		<colgroup>
			<col class="orders-table__id-column" />
			<col class="orders-table__date-column" />
			<col class="orders-table__price-column" />
			<col class="orders-table__status-column" />
		</colgroup>
		<thead>
			<tr>
				<th>ID</th>
				<th>Добавлен</th>
				<th>Сумма (<span class="ruble">Р</span>)</th>
				<th>Статус</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
	
	<div class="orders-list__load-more hidden" id="orders-list__load-more">
		<span>Загрузить еще</span>
	</div>
</div>
<script type="text/javascript">var loadStatusId = <?=$loadStatusId;?>;</script>
	<?
}

$pageTitle = 'Мои заказы';
$pageMenu = 'my_orders';
$javascripts = [
	'/js/pages/my_orders.js'
];

require_once('../templates/html.php');
