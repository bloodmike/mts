<?php
/** 
 * Функции, реализующие авторизацию.
 * 
 * @author mkoshkin
 */
namespace Auth;

/**
 * Поле сессии, в котором хранится ID авторизованного пользователя
 */
const USER_ID_SESSION_KEY = 'uid';

/**
 * Проверяет, что сессия начата;
 * если не начата - начинает её.
 */
function checkSessionStarted() {
    if (session_status() == PHP_SESSION_NONE) {
        if (!session_start()) {
            die('Unable to start session');
        }
    }
}

/**
 * @return int ID авторизованного пользователя
 */
function getCurrentUserId() {
    checkSessionStarted();
    
    if (array_key_exists(USER_ID_SESSION_KEY, $_SESSION)) {
        return $_SESSION[USER_ID_SESSION_KEY];
    }
    
    return 0;
}

/**
 * Очищает сессию от данных авторизации
 */
function logOut() {
    checkSessionStarted();
    
    if (array_key_exists(USER_ID_SESSION_KEY, $_SESSION)) {
        unset($_SESSION[USER_ID_SESSION_KEY]);
    }
}
