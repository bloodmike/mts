<?php
/**
 * Функции для работы с пользователями
 * 
 * @author mkoshkin
 */
namespace User;

use Exception;
use mysqli;

/**
 * Максимально допустимое количество итераций при поиске шарды с пользователем
 */
const MAX_LOAD_BY_ID_ITERATIONS_COUNT = 15;

/**
 * Максимально допустимое количество итераций при поиске шарды с пользователем
 */
const MAX_LOAD_BY_LOGIN_ITERATIONS_COUNT = 10;

/**
 * Тип записи в шарде: данные о расположении пользователей
 */
const SHARD_RECORD_TYPE_SHARD = 0;

/**
 * Тип записи в шарде: данные пользователей
 */
const SHARD_RECORD_TYPE_USER = 1;

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
    return \Database\fetchRow($link, 'SELECT * FROM users WHERE id=' . $userId . ' LIMIT 1');
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
    
    return \Database\getConnectionOrFall($hostId);
}

/**
 * @return mysqli|null открытое подключение к хосту с данными текущего пользователя, 
 *                      null - если пользователь не авторизован или хост не найден
 * 
 * @throws Exception при ошибках в поиске хоста или невозможности открыть подключение к хосту
 */
function getHostConnectionForCurrentUser() {
    return getHostConnection(\Auth\getCurrentUserId());
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
        
        $link = \Database\getConnectionOrFall($hostId);
        
        $userBlockInfo = \Database\fetchRow(
                $link, 
                'SELECT host_id, type FROM users_shards WHERE from_user_id <= ' . $userId . ' AND ' . $userId . ' < to_user_id LIMIT 1');
        
        if ($userBlockInfo === null) {
            // шарда не найдена
            return 0;
        }
        
        $visitedHostsMap[$hostId] = true;
        $hostId = $userBlockInfo['host_id'];
        
    } while($userBlockInfo['type'] != SHARD_RECORD_TYPE_USER);
    
    return $userBlockInfo['host_id'];
}

/**
 * Ищет хосты, на которых лежат данные пользователей
 * 
 * @global int $rootDatabaseHostId ID хоста, к которому обращаться за данными по умолчанию
 * 
 * @param int[] $userIds список уникальных ID пользователей
 * 
 * @return int[] хэшмэп с ID хостов, на которых лежат данные о пользователях: userId => hostId
 *              если хост пользователя не найден - его нет в массиве результатов
 */
function findHostsIds(array $userIds) {
    global $rootDatabaseHostId;
    
    if (count($userIds) == 0) {
        return [];
    }
    
    $result = [];
    
    // сперва ищем всех на корневом хосте
    $searchAt = [
        $rootDatabaseHostId => $userIds
    ];
    $loops = 0;
    
    do {
        if ($loops >= MAX_LOAD_BY_ID_ITERATIONS_COUNT) {
            throw new Exception('Превышено допустимое количество итерации при поиске шард пользователей');
        }
        $loops++;
        $searchAtNew = [];
        
        foreach ($searchAt as $hostId => $searchUserIds) {
            $whereArray = [];
            foreach ($searchUserIds as $userId) {
                $whereArray[] = '(from_user_id <= ' . $userId . ' AND ' . $userId . ' < to_user_id)';
            }
            
            $link = \Database\getConnection($hostId);
            if ($link === false) {
                // если обломались с подключением - не ищем данные
                continue;
            }
            
            $userBlocks = \Database\fetchAll(
                    $link, 
                    'SELECT from_user_id, to_user_id, host_id, type FROM users_shards WHERE ' . implode(' OR ', $whereArray),
                    false);
            
            if (count($userBlocks) == 0) {
                continue;
            }
            
            foreach ($searchUserIds as $userId) {
                foreach ($userBlocks as $userBlockInfo) {
                    if ($userBlockInfo['from_user_id'] <= $userId && $userId < $userBlockInfo['to_user_id']) {
                        
                        if ($userBlockInfo['type'] == SHARD_RECORD_TYPE_USER) {
                            // записываем в результат запись о хосте с данными юзера
                            $result[ $userId ] = $userBlockInfo['host_id'];
                        } else {
                            if (!array_key_exists($userBlockInfo['host_id'], $searchAtNew)) {
                                $searchAtNew[$userBlockInfo['host_id']] = [];
                            }
                            // записываем юзера в дальшнейший поиск
                            $searchAtNew[$userBlockInfo['host_id']][] = $userId;
                        }
                        break;
                    }
                }
            }
        }
        
        $searchAt = $searchAtNew;
    } while (count($searchAt) > 0);
	
    return $result;
}

/**
 * Загружает логины пользователей.
 * Если по каким-то пользователям данные получить невозможно или данных нет - они игнорируются.
 * 
 * @param int[] $userIds список уникальных ID пользователей, которых требуется найти
 * 
 * @return string[] хэшмэп с логинами пользователей: userId => userLogin
 *              если пользователь не найден или не может быть найден (из-за ошибок), то ячейки с его ID нет среди результатов
 */
function loadListByIds($userIds) {
    // получаем данные о хостах, на которых лежат пользователи
    $hostIds = findHostsIds($userIds);
    
	$grouppedHostIds = [];
	foreach ($hostIds as $userId => $hostId) {
		if (!array_key_exists($hostId, $grouppedHostIds)) {
			$grouppedHostIds[$hostId] = [];
			$grouppedHostIds[$hostId][] = $userId;
		}
	}
	
    $users = [];
    
    foreach ($grouppedHostIds as $hostId => $searchUserIds) {
        $link = \Database\getConnection($hostId);
        if ($link === false) {
            // если подключение не удалось установить - игнорируем блок пользователей
            continue;
        }
        
        // загружаем данные пользователей с хоста
        $usersList = \Database\fetchAll(
                $link, 
                'SELECT id, login FROM users WHERE id IN (' . implode(',', $searchUserIds) . ') LIMIT ' . count($searchUserIds),
                false);
        
        foreach ($usersList as $userData) {
            $users[$userData['id']] = $userData['login'];
        }
    }
    
    return $users;
}

/**
 * @global int $rootDatabaseHostId ID хоста, к которому обращаться за данными по умолчанию
 * 
 * @param string $login логин пользователя
 * 
 * @throws Exception если возникла ошибка при выполнении запроса, запросы пошли по кругу или их количество превысило предел
 * 
 * @return int ID пользователя с указанным логином (0 - пользователь не найден)
 */
function findUserIdByLogin($login) {
	global $rootDatabaseHostId;
	
	$loops = 0;
	$hostId = $rootDatabaseHostId;
    $visitedHostsMap = [];
	
	do {
        if ($loops >= MAX_LOAD_BY_LOGIN_ITERATIONS_COUNT) {
            throw new Exception('Превышено допустимое количество итерации при поиске шард пользователей');
        }
        $loops++;
        
        if (array_key_exists($hostId, $visitedHostsMap)) {
            throw new Exception('При поиске шарды с логином пользователя [' . $login . '] возникла ошибка цикличности');
        }
		
		$link = \Database\getConnectionOrFall($hostId);
		$escapedLogin = mysqli_real_escape_string($link, $login);
		
		$hostInfo = \Database\fetchRow(
				$link, 
				"SELECT host_id, type "
				. "FROM users_logins_shards "
				. "WHERE from_login <= '" . $escapedLogin . "' AND '" . $escapedLogin . "' < to_login "
				. "LIMIT 1");
		
		if ($hostInfo === null) {
			return 0;
		}
        
        $visitedHostsMap[$hostId] = true;
        $hostId = $hostInfo['host_id'];
		
	} while ($hostInfo['type'] == SHARD_RECORD_TYPE_SHARD);
	
	$link = \Database\getConnectionOrFall($hostId);
	return (int)\Database\fetchOne(
			$link, 
			"SELECT id "
			. "FROM users_logins "
			. "WHERE login='" . mysqli_real_escape_string($link, $login) . "' "
			. "LIMIT 1");
}
