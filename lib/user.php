<?php
/**
 * Функции для работы с пользователями
 * 
 * @author mkoshkin
 */
namespace User;

/**
 * Загружает данные авторизованного пользователя 
 * 
 * @return array|null
 */
function authorize() {
	$userId = (int) filter_input(INPUT_SESSION, 'uid', FILTER_VALIDATE_INT);
	$passwordHash = (string) filter_input(INPUT_SESSION, 'pass');
	if ($userId <= 0 || $passwordHash == '') {
		return null;
	}
}

/**
 * Получить юзера по ID из базы
 * 
 * @param int $id ID юзера
 * 
 * @return array|null массив с данными пользователя или null, если пользователь не найден
 */
function loadById($id) {
	if ($id <= 0) {
		return null;
	}
	
	
}

/**
 * 
 */
function findUserShard() {
	
}
