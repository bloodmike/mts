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
						<a href="/feed.php"<?=($pageMenu == 'feed' ? ' class="selected"' : '')?>>Заказы</a>
						<a href="/my_orders.php"<?=($pageMenu == 'my_orders' ? ' class="selected"' : '')?>>Мои заказы</a>
						<a href="/finished_orders.php"<?=($pageMenu == 'finished_orders' ? ' class="selected"' : '')?>>Выполненные заказы</a>
						
						<span id="header-balance"><?=$currentUser['balance'];?></span>

						<a href="/logout.php" id="header-menu__logout">Выход</a>
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