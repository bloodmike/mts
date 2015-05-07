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
        <title>VK Orders / <?=$pageTitle;?></title>
        <link rel="stylesheet" type="text/css" href="/css/public.css" />
        
        <script type="text/javascript" src="/js/errors.js"></script>
        <script type="text/javascript">var currentUser=<?=json_encode($currentUser);?>;</script>
        <script type="text/javascript" src="/js/public.js"></script>
        <?if(\Auth\getCurrentUserId() > 0){?>
        <script type="text/javascript" src="/js/ajax.js"></script>
        <?}?>
		<?foreach ($javascripts as $javascript){?>
		<script type="text/javascript" src="<?=htmlspecialchars($javascript);?>"></script>
		<?}?>
	</head>
	<body>
        <div id="body">
            <div id="header">
				<div id="header-bar">
					<a href="/" id="header-logo">Orders</a>

					<?if(\Auth\getCurrentUserId() > 0){?>
					<span id="header-balance"></span>
					<div id="header-menu">
						<a href="/feed.php"<?=($pageMenu == 'feed' ? ' class="selected"' : '')?>>Заказы</a>
						<a href="/my_orders.php"<?=($pageMenu == 'my_orders' ? ' class="selected"' : '')?>>Мои заказы</a>
						<a href="/executed_orders.php"<?=($pageMenu == 'executed_orders' ? ' class="selected"' : '')?>>Выполненные заказы</a>

						<a href="/logout.php" id="header-menu__logout">Выход</a>
					</div>
					<?}?>
				</div>
            </div>
            
            <div id="content"><?
            if (function_exists('htmlContent')) {
                htmlContent();
            }
            ?></div>
        </div>
	</body>
</html>