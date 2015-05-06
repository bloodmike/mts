<?php
/**
 * Страница авторизации
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

function htmlContent() {
    ?>
<div id="login-page" class="login-page">
    <div id="login-error-message" class="login-error-message"></div>
    <form id="login-form" class="login-form">
        <div class="login-form__row">
            <label>Логин:</label><input type="text" name="login" value="" required/>
        </div>
        <div class="login-form__row">
            <label>Пароль:</label><input type="password" name="password" value="" required/>
        </div>
        <div class="login-form__submit-row">
            <input type="submit" value="Войти" />
        </div>
    </form>
</div>
    <?
}

require_once('../templates/html.php');
