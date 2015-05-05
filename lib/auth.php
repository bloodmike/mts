<?php
/** 
 * Функции, реализующие авторизацию.
 * 
 * @author mkoshkin
 */
namespace Auth;

use Exception;

/**
 * Поле сессии, в котором хранится ID авторизованного пользователя
 */
const USER_ID_SESSION_KEY = 'uid';

/**
 * Поле сессии, в котором хранится хэш пароля
 */
const PASSWORD_HASH_SESSION_KEY = 'pass';

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

/**
 * Проверяет данные авторизации в сессии.
 * 
 * @return array|null данные пользователя или null, если данные авторизации некорректны
 * 
 * @throws Exception при ошибках в работе с базой
 */
function authorize() {
	$userId = getCurrentUserId();
	if ($userId <= 0 || !array_key_exists(PASSWORD_HASH_SESSION_KEY, $_SESSION) || strlen($_SESSION[PASSWORD_HASH_SESSION_KEY]) != 60) {
		logOut();
		return null;
	}
	
	$userData = \User\loadById($userId);
	if ($userData === null) {
		logOut();
		return null;
	}
	
	$userPasswordHash = $_SESSION[PASSWORD_HASH_SESSION_KEY];
	if ($userPasswordHash != $userData['password']) {
		logOut();
		return null;
	}
	
	return $userData;
}
