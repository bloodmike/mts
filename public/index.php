<?php
/**
 * Страница для отрисовки форм
 * 
 * @author mkoshkin
 */

require_once("../lib/autoload.php");

$authorizedUser = User\authorize();
if ($authorizedUser === null) {
	
}

