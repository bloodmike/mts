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

/**
 * @param mysqli $link подключение к базе
 * @param string $query запрос
 * 
 * @return mixed|null значение из первой ячейки первой строки результата запроса или null, если запрос не вернул строк
 * 
 * @throws Exception при ошибках выполнения запроса
 */
function fetchOne(mysqli $link, $query) {
    $result = mysqli_query($link, $query);
    if ($result === false) {
        throw new Exception('При запросе возникла ошибка: ' . mysqli_error($link));
    } elseif ($result->num_rows == 0) {
        return null;
    }
    
    $row = mysqli_fetch_row($result);
    mysqli_free_result($result);
    return $row[0];
}

/**
 * @param mysqli $link подключение к базе
 * @param string $query запрос
 * 
 * @return array все данные запроса в виде ассоциативных массивов
 * 
 * @throws Exception при ошибках выполнения запроса
 */
function fetchAll(mysqli $link, $query) {
    $result = mysqli_query($link, $query);
    if ($result === false) {
        throw new Exception('При запросе возникла ошибка: ' . mysqli_error($link));
    } 
    
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    
    mysqli_free_result($result);
    return $rows;
}

/**
 * @param mysqli $link подключение к базе
 * @param string $query запрос
 * 
 * @return array|null первую строку результата запроса или null, если запрос не вернул строк
 * 
 * @throws Exception при ошибках выполнения запроса
 */
function fetchRow(mysqli $link, $query) {
    $result = mysqli_query($link, $query);
    if ($result === false) {
        throw new Exception('При запросе возникла ошибка: ' . mysqli_error($link));
    } elseif ($result->num_rows == 0) {
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row;
}
