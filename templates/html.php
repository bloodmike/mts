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
        <script type="text/javascript" src="/js/public.js"></script>
	</head>
	<body>
        <div id="body">
            <div id="header">
                <a href="/" id="header-logo">VK Orders</a>
                
                <?if(\Auth\getCurrentUserId() > 0){?>
                <span id="header-balance"></span>
                <div id="header-menu">
                    <a href="/feed.php">Заказы</a>
                    <a href="/my_orders.php">Мои заказы</a>
                    <a href="/executed_orders.php">Выполненные заказы</a>
                
                    <a href="/logout.php" id="header-menu__logout">Выход</a>
                </div>
                <?}?>
            </div>
            
            <div id="content"><?
            if (function_exists('htmlContent')) {
                htmlContent();
            }
            ?></div>
        </div>
	</body>
</html>