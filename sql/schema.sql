--
-- Таблица с пользователями
--
CREATE TABLE `users`(
    `id`		BIGINT			NOT NULL UNSIGNED,
    `login`		VARCHAR(25)		NOT NULL,
    `password`	VARCHAR(60)		NOT NULL,
    `balance`	DECIMAL(10,5)	NOT NULL UNSIGNED DEFAULT 0.00,
    PRIMARY KEY(`id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с информацией о расположении данных пользователей
--
CREATE TABLE `users_shards`(
    `from_user_id`  BIGINT  NOT NULL UNSIGNED, -- минимальный ID юзера (включительно)
    `to_user_id`    BIGINT  NOT NULL UNSIGNED, -- максимальный ID юзера (не включительно)
    `host_id`       INT     NOT NULL UNSIGNED, -- ID хоста
    `type`          TINYINT NOT NULL UNSIGNED, -- тип записи: 0 - по адресу лежат данные о расположении, 1 - данные о пользователях
    PRIMARY KEY(`from_user_id`, `to_user_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с распределением логинов пользователей по хостам (нужна для поиска по логину)
--
CREATE TABLE `users_logins_shards`(
	`from_login`	VARCHAR(25) NOT NULL,
	`to_login`		VARCHAR(25) NOT NULL,
	`host_id`		INT			NOT NULL UNSIGNED, -- ID хоста
	`type`			TINYINT		NOT NULL UNSIGNED, -- тип записи: 0 - по адресу лежат данные о расположении, 1 - данные 0 пользователях
	PRIMARY KEY(`from_login`, `to_login`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с логинами пользователей (нужна для поиска по логину)
--
CREATE TABLE `users_logins`(
	`login`	VARCHAR(25) NOT NULL,
	`id`	BIGINT NOT NULL UNSIGNED,
	PRIMARY KEY(`login`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с информацией о расположении ленты заказов пользователя.
-- Данные по расположению лент пользователей лежат на одних хостах с данными пользователей.
--
CREATE TABLE `users_orders_shards`(
    `user_id`       BIGINT  NOT NULL UNSIGNED, -- ID юзера
    `from_order_id` BIGINT  NOT NULL UNSIGNED, -- минимальный ID заказа (включительно)
    `host_id`       INT     NOT NULL UNSIGNED, -- ID хоста, где лежат данные заказов
    PRIMARY KEY(`user_id`, `from_order_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с заказами пользователей
--
CREATE TABLE `users_orders`(
    `user_id`           BIGINT          NOT NULL UNSIGNED, -- ID заказчика
    `order_id`          BIGINT          NOT NULL UNSIGNED, -- ID заказа
    `ts`                BIGINT          NOT NULL UNSIGNED, -- unix-время добавления заказа
    `price`             DECIMAL(10,2)   NOT NULL UNSIGNED DEFAULT '0.00', -- сумма заказа
    `finished`          TINYINT         NOT NULL UNSIGNED DEFAULT '0', -- выполнен ли заказ
    `finished_user_id`  BIGINT          NOT NULL UNSIGNED, -- ID исполнителя заказа
    PRIMARY KEY(`user_id`, `order_id`),
    KEY(`user_id`, `finished`, `order_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с дайджестом заказов.
-- В дайджестом содержатся только невыполненные заказы.
--
CREATE TABLE `orders_digest`(
    `ts`        BIGINT          NOT NULL UNSIGNED, -- unix-время добавления заказа
    `user_id`   BIGINT          NOT NULL UNSIGNED, -- ID заказчика
    `order_id`  BIGINT          NOT NULL UNSIGNED, -- ID заказа
    `price`     DECIMAL(10,2)   NOT NULL UNSIGNED DEFAULT '0.00', -- сумма заказа
    PRIMARY KEY(`ts`, `user_id`, `order_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с данными о шардах даджеста.
--
CREATE TABLE `orders_digest_shards`(
    `from_ts`   BIGINT NOT NULL UNSIGNED, -- минимальное unix-время заявки (включительно)
    `to_ts`     BIGINT NOT NULL UNSIGNED, -- максимальное unix-время заявки (не включительно)
    `from_hash` VARCHAR(32) NOT NULL DEFAULT '',
    `to_hash`   VARCHAR(32) NOT NULL DEFAULT '',
    `host_id`   INT     NOT NULL UNSIGNED, -- ID хоста
    PRIMARY KEY(`from_ts`, `to_ts`, `from_hash`, `to_hash`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Информация о шардах выполненных пользователем заказов.
-- Данные по расположению выполненных заказов лежат на одних хостах с данными пользователей.
--
CREATE TABLE `finished_orders_shards`(
    `finished_user_id`  BIGINT NOT NULL UNSIGNED, -- ID пользователя, выполнившего заказ
    `from_finish_ts`    BIGINT NOT NULL UNSIGNED, -- минимальное время выполнения заказа (включительно)
    PRIMARY KEY(`finished_user_id`, `from_finish_ts`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с данными по выполненным заказам
--
CREATE TABLE `finished_orders`(
    `finished_user_id`  BIGINT NOT NULL UNSIGNED, -- ID исполнителя
    `finish_ts`         BIGINT NOT NULL UNSIGNED, -- unix-время исполнения заказа
    `user_id`           BIGINT NOT NULL UNSIGNED, -- ID заказчика
    `order_id`          BIGINT NOT NULL UNSIGNED, -- ID заказа
    PRIMARY KEY(`finished_user_id`, `finish_ts`, `user_id`, `order_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;
