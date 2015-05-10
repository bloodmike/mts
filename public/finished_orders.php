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
    ?>
<div class="panel panel-container finished-orders-page">
    <center>
        <table class="orders-table" id="finished-orders-table">
            <colgroup>
                <col class="orders-table__date-column" />
                <col class="orders-table__price-column" />
                <col class="orders-table__id-column" />
                <col class="orders-table__owner-column" />
            </colgroup>
            <thead>
                <tr>
                    <th>Выполнен</th>
                    <th>Прибыль (<span class="ruble">Р</span>)</th>
                    <th>ID</th>
                    <th>Заказчик</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </center>
	
	<div class="orders-list__load-more hidden" id="orders-list__load-more">
		<span>Загрузить еще</span>
	</div>
</div>
    <?
}

$pageTitle = 'Выполненные заказы';
$pageMenu = 'finished_orders';
$javascripts = [
    '/js/pages/finished_orders.js'
];

require_once('../templates/html.php');
