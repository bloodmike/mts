<?php
/**
 * Выход
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

\Auth\logOut();

header('Location: /login.php');
