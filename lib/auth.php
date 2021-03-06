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
 * @var array|null переменная, в которой лежат данные авторизованного пользователя;
 *                  заполняется данными при вызове \Auth\authorize()
 */
$currentUser = null;

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
 * @global array|null $currentUser переменная для хранения данных текущего пользователя
 * 
 * @return array|null данные пользователя или null, если данные авторизации некорректны
 * 
 * @throws Exception при ошибках в работе с базой
 */
function authorize() {    
    global $currentUser;
    
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
	
    $currentUser = $userData;
	return $userData;
}

/**
 * Сохраняет переданного пользователя в сессию в качестве авторизованного.
 * 
 * @param int $userId ID пользователя
 * @param string $passwordHash хэш пароля
 */
function setUser($userId, $passwordHash) {
	checkSessionStarted();
    $_SESSION[USER_ID_SESSION_KEY] = $userId;
    $_SESSION[PASSWORD_HASH_SESSION_KEY] = $passwordHash;
}

/**
 * @global array|null $currentUser авторизованный пользователь
 * 
 * @return array|null данные пользователя для вывода в javascript'е
 */
function getCurrentUserPublicData() {
    global $currentUser;
    
    if ($currentUser !== null) {
        return [
            'id'        => $currentUser['id'],
            'login'     => $currentUser['login'],
            'balance'   => $currentUser['balance'],
        ];
    }
    
    return null;
}
