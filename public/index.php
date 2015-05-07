<?php
/**
 * Страница для отрисовки форм
 * 
 * @author mkoshkin
 */

require_once("../lib/autoload.php");

try {
	if (\Auth\authorize() !== null) {
		\Response\redirect('/feed.php');
	} else {
		\Response\redirect('/login.php');
	}
} catch (Exception $Exception) {
	error_log($Exception->getMessage());
	echo $Exception->getMessage();
}
