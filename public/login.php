<?php
/**
 * Страница авторизации
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

\Auth\checkSessionStarted();

function htmlContent() {
    ?>
<div id="login-page" class="login-page panel panel-container">
    <div id="login-error-message" class="login-error-message"></div>
    <form id="login-form" class="login-form">
        <div class="login-form__row">
            <label>Логин:</label><input type="text" name="login" id="login-form__login" value="" />
        </div>
        <div class="login-form__row">
            <label>Пароль:</label><input type="password" name="password" id="login-form__password" value="" />
        </div>
        <div class="login-form__submit-row">
            <input type="submit" value="Войти" />
        </div>
    </form>
</div>
    <?
}

$pageTitle = 'Добро пожаловать!';
$javascripts = [
	'/js/login.js'
];

require_once('../templates/html.php');
