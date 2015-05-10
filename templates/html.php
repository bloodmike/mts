<?php
/**
 * Шаблон html-страницы системы.
 * Для работы требует наличия определенных функций.
 * 
 * @author mkoshkin
 */
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
        <title><?=$pageTitle;?></title>
        <link rel="stylesheet" type="text/css" href="/css/public.css" />
        <script type="text/javascript">var currentUser=<?=json_encode(\Auth\getCurrentUserPublicData());?>;</script>
	</head>
	<body>
        <div id="body">
            <div id="header">
				<div id="header-bar">
					<a href="/" id="header-logo">Orders</a>

					<?if(\Auth\getCurrentUserId() > 0){?>
					<div id="header-menu">
						<span id="header-menu__open-dialog" class="header-menu__open-dialog" title="Добавить заказ">Добавить заказ</span>
						<a href="/feed.php"<?=($pageMenu == 'feed' ? ' class="selected"' : '')?>>Заказы</a>
						<a href="/my_orders.php"<?=($pageMenu == 'my_orders' ? ' class="selected"' : '')?>>Мои заказы</a>
						<a href="/finished_orders.php"<?=($pageMenu == 'finished_orders' ? ' class="selected"' : '')?>>Выполненные заказы</a>
						
						<a href="/logout.php" id="header-menu__logout" onclick="return confirm('Хотите выйти?');">Выход</a>
						<span id="header-balance" class="header-balance" title="Баланс"><?=number_format($currentUser['balance'], 2, '.', '');?></span>
					</div>
					<?}?>
				</div>
                <div id="header-error" class="header-error"></div>
            </div>
            
            <div id="content"><?
            if (function_exists('htmlContent')) {
                htmlContent();
            }
            ?></div>
        </div>
        <?if(\Auth\getCurrentUserId() > 0){?>
		<div id="add-order-dialog" class="add-order-dialog hidden" title="Закрыть диалог">
			<div id="add-order-dialog__content" class="add-order-dialog__content" title="">
				<div class="panel panel-container">
					<form id="add-order-form" class="add-order-form">
						Сумма: <input type="text" id="add-order-form__price" value="" placeholder="100.00" style="width: 70px; text-align: right;"/>
						<span class="ruble">Р</span>
						
						<input type="submit" class="btn" value="Добавить заказ"/>
					</form>    
				</div>
                
                <div id="add-order-error-message" class="error-message"></div>
                <div id="add-order-success-message" class="success-message">Заказ добавлен</div>
			</div>
		</div>
		<?}?>
		<script type="text/javascript" src="/js/errors.js"></script>
        <script type="text/javascript" src="/js/public.js"></script>
        <?if(\Auth\getCurrentUserId() > 0){?>
        <script type="text/javascript" src="/js/actions.js"></script>
        <?}?>
		<?if (isset($javascripts)) {
			foreach ($javascripts as $javascript){?>
			<script type="text/javascript" src="<?=htmlspecialchars($javascript);?>"></script>
			<?}
		}?>
	</body>
</html>