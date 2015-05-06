--
-- Подготовка схемы
--

CREATE SCHEMA `mts` DEFAULT CHARACTER SET utf8;

CREATE USER mts@localhost IDENTIFIED BY '123';

GRANT ALL PRIVILEGES ON mts.* TO mts@localhost IDENTIFIED BY '123';
