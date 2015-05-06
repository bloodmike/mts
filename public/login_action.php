<?php
/**
 * Авторизация пользователя
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');


/**
 * @return array результат выполнения авторизации
 */
function logIn() {
    $response = [];
    
    try {
        if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') != 'POST') {
            throw new Exception('Неправильный метод запроса');
        }
        
        $login = mb_strtolower((string) filter_input(INPUT_POST, 'login'));
        $password = (string) filter_input(INPUT_POST, 'password');
        if (!preg_match('/^[a-z0-9]{1,25}$/', $login)) {
            \Response\jsonAddError($response, \Error\AUTH_BAD_LOGIN);
        }
        
        if ($password == '') {
            \Response\jsonAddError($response, \Error\AUTH_NO_PASSWORD);
        }
        
        if (\Response\jsonHasErrors($response)) {
            return $response;
        }
        
        $userId = \User\findUserIdByLogin($login);
        if ($userId <= 0) {
            return \Response\jsonAddError($response, \Error\USER_NOT_FOUND);
        }
        
        $userData = \User\loadById($userId);
        if ($userData === null) {
            return \Response\jsonAddError($response, \Error\USER_NOT_FOUND);
        }
        
        if (!password_verify($password, $userData['password'])) {
            return \Response\jsonAddError($response, \Error\AUTH_INCORRECT_PASSWORD);
        }
        
        \Auth\setUser($userId, $userData['password']);
        
    } catch (Exception $Exception) {
        error_log($Exception->getMessage());
        \Response\jsonAddError($response, \Error\PROCESSING_ERROR);
    }
    
    return $response;
}

echo json_encode(logIn());
