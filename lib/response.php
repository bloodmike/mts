<?php

/** 
 * Функции для формирования ответа на запрос
 * 
 * @author mkoshkin
 */

namespace Response;

/**
 * Добавляет в переданный ответ код ошибки
 * 
 * @param array &$response json-ответ
 * @param int $code код ошибки
 * 
 * @return array переданный параметром массив ответа
 */
function jsonAddError(array &$response, $code) {
    if (!array_key_exists('error', $response)) {
        $response['error'] = [$code];
    } else {
        $response['error'][] = $code;
    }
    
    return $response;
}

/**
 * @param array $response json-ответ
 * 
 * @return bool есть ли в ответе коды ошибок
 */
function jsonHasErrors(array &$response) {
    return array_key_exists('error', $response) && count($response['error']) > 0;
}

/**
 * Завершает работу скрипта выставлением заголовков для редиректа пользователя.
 * 
 * @param string $url ссылка, куда будет отправлен пользователь
 */
function redirect($url) {
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $url);
	exit;
}
