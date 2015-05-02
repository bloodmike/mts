<?php
/**
 * Функции для работы с базами данных.
 * 
 * @author mkoshkin
 */
namespace Database;

/**
 * @staticvar mysqli[] $connectionLinksMap хранилище всех открытых подключений к базам
 * 
 * @return mysqli[]& ссылка на хэшмэп с подключениями
 */
function &getConnections() {
    static $connectionLinksMap = [];
    return $connectionLinksMap;
}

/**
 * @param int $hostId ID хоста
 * 
 * @return mysqli|bool открытое подключение к указанному хосту
 */
function getConnection($hostId) {
    $connectionLinksMap =& getConnections();
    
    if (!array_key_exists($hostId, $connectionLinksMap)) {
        $connectionLinksMap[$hostId] = mysqli_connect(getHostName($hostId), 'root', '123', 'mts', getHostPort($hostId));
    }
    
    return $connectionLinksMap[$hostId];
}

/**
 * @param int $hostId ID хоста
 * 
 * @return mysqli открытое подключение к указанному хосту
 * 
 * @throws Exception когда невозможно открыть подключение к хосту
 */
function getConnectionOrFall($hostId) {
    $link = getConnection($hostId);
    if ($link === false) {
        throw new Exception('Невозможно подключиться к хосту [' . $hostId . ']');
    }
    return $link;
}

/**
 * @todo должна зависить от конфигурации
 * 
 * @param int $hostId ID хоста
 * 
 * @return string адрес хоста
 */
function getHostName($hostId) {
    return 'host' . $hostId . '.database';
}

/**
 * @param int $hostId
 * 
 * @return int номер порта для подключения
 */
function getHostPort($hostId) {
    return 3305 + $hostId;
}

/**
 * Закрывает все открытые подключения
 * 
 * @todo провять результат закрытия подключений
 */
function closeConnections() {
    $connectionsMap =& getConnections();
    foreach ($connectionsMap as $link) {
        if ($link !== false) {
            mysqli_close($link);
        }
    }
    $connectionsMap = [];
}

/**
 * @param int $hostId ID хоста
 * 
 * @return bool удалось ли закрыть подключение: true только если подключение было открыто и успешно закрылось
 */
function closeConnection($hostId) {
    $connectionsMap =& getConnections();
    $result = false;
    if (array_key_exists($hostId, $connectionsMap)) {
        if ($connectionsMap[$hostId] !== false) {
            $result = mysqli_close($connectionsMap[$hostId]);
        }
        unset($connectionsMap[$hostId]);
    }
    return $result;
}
