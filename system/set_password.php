<?php
/**
 * Скрипт для установки пароля пользователю по логину
 * 
 * @author mkoshkin
 */

require_once('../lib/autoload.php');

try {
    $login = (string)$argv[1];
    $password = (string)$argv[2];
        
    $userId = \User\findUserIdByLogin($login);
    if ($userId <= 0) {
        echo 'Пользователь [' . $login . '] не найден' . PHP_EOL;
        exit;
    }
    
    $link = \Database\getConnectionOrFall(\User\findHostId($userId));
    
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['salt' => 'there-is-no-salt-inmts']);
    
    mysqli_query(
            $link, 
            "UPDATE users "
            . "SET password='" . mysqli_real_escape_string($link, $passwordHash) . "' "
            . "WHERE id=" . $userId . ' LIMIT 1');
    
    echo 'Пароль изменён!' . PHP_EOL;
    
} catch (Exception $Exception) {
    error_log($Exception->getMessage());
    echo 'При выполнении команды возникла ошибка: ' . $Exception->getMessage() . PHP_EOL;
}
