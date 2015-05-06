<?php
/**
 * Шаблон html-страницы системы.
 * Для работы требует наличия определенных функций.
 * 
 * 
 * @author mkoshkin
 */
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
        <title>VK Orders</title>
        <link rel="stylesheet" type="text/css" href="/css/public.css" />
	</head>
	<body>
        <div id="body">
            <div id="header">
                <a href="/" id="header-logo">VK Orders</a>
                <span id="header-balance"></span>
                <?if(\Auth\getCurrentUserId() > 0){?>
                <a href="/feed.php">Заказы</a>
                <a href="/my_orders.php">Мои заказы</a>
                <a href="/executed_orders.php">Выполненные заказы</a>
                
                <a href="/logout.php">Выход</a>
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