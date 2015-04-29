<?php
/**
 * Функции для работы с пользователями
 * 
 * @author mkoshkin
 */
namespace User;

/**
 * Максимально допустимое количество итераций при поиске шарды с пользователем
 */
const MAX_LOAD_BY_ID_ITERATIONS_COUNT = 15;

/**
 * Тип записи в шарде: данные о расположении пользователей
 */
const SHARD_RECORD_TYPE_SHARD = 0;

/**
 * Тип записи в шарде: данные пользователей
 */
const SHARD_RECORD_TYPE_USER = 1;

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
 * @param int $userId ID юзера
 * 
 * @throws Exception если возникла ошибка при выполнении запроса, запросы пошли по кругу или их количество превысило предел
 * 
 * @return array|null массив с данными пользователя или null, если пользователь не найден
 */
function loadById($userId) {
    $link = getHostConnection($userId);
    if ($link === null) {
        // не найден хост - не найден пользователь
        return null;
    }
    
    $queryResult = $link->query('SELECT * FROM users WHERE id=' . $userId . ' LIMIT 1');
    if ($queryResult === false) {
        throw new Exception('При выполнении запроса возникла ошибка: ' . mysqli_error($link));
    } elseif ($queryResult->num_rows > 0) {
        return $queryResult->fetch_assoc();
    } else {
        // пользователь не найден
        return null;
    }
}

/**
 * @param int $userId ID пользователя
 * 
 * @return mysqli|null открытое подключение к хосту с данными пользователя, null - если хост не найден
 * 
 * @throws Exception при ошибках в поиске хоста или при невозможности получить открытое подключение к хосту
 */
function getHostConnection($userId) {
    $hostId = findHostId($userId);
    if ($hostId == 0) {
        return null;
    }
    
    $link = Database\getConnection($hostId);
    if ($link === false) {
        throw new Exception('Ошибка при получении подключения к хосту [' . $hostId . ']');
    }
    
    return $link;
}


/**
 * Ищет хост, на котором лежат данные пользователя
 * 
 * @global int $rootDatabaseHostId ID хоста, к которому обращаться за данными по умолчанию
 * 
 * @param int $userId ID пользователя
 * 
 * @throws Exception если возникла ошибка при выполнении запроса, запросы пошли по кругу или их количество превысило предел
 * 
 * @return int ID хоста, на котором нужно искать указанного пользователя;
 *              0 - если шарда с данными пользователя не найдена
 */
function findHostId($userId) {
    global $rootDatabaseHostId;
    
	if ($userId <= 0) {
		return null;
	}
	
    $hostId = $rootDatabaseHostId;
    $loops = 0;
    $visitedHostsMap = [];
    
    do {
        if ($loops >= MAX_LOAD_BY_ID_ITERATIONS_COUNT) {
            throw new Exception('Превышено допустимое количество итерации при поиске шарды пользователя [' . $userId . ']');
        }
        $loops++;
        
        if (array_key_exists($hostId, $visitedHostsMap)) {
            throw new Exception('При поиске шарды пользователя [' . $userId . '] возникла ошибка цикличности');
        }
        
        $link = Database\getConnection($hostId);
        if ($link === false) {
            throw new Exception('Ошибка при получении подключения к хосту [' . $hostId . ']');
        }
        
        $queryResult = $link->query(
                'SELECT host_id, type FROM users_shards WHERE from_user_id <= ' . $userId . ' AND to_user_id < ' . $userId . ' LIMIT 1');
        
        if ($queryResult === false) {
            throw new Exception('При выполнении запроса возникла ошибка: ' . mysqli_error($link));
        } elseif ($queryResult->num_rows > 0) {
            $userBlockInfo = $queryResult->fetch_assoc();
        } else {
            // шарда не найдена
            return 0;
        }
        
        $visitedHostsMap[$hostId] = true;
        $hostId = $userBlockInfo['host_id'];
        
    } while($userBlockInfo['type'] != SHARD_RECORD_TYPE_USER);
    
    return $userBlockInfo['host_id'];
}
