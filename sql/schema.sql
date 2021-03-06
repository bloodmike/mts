--
-- Таблица с пользователями
--
CREATE TABLE `users`(
    `id`		BIGINT			UNSIGNED NOT NULL,
    `login`		VARCHAR(25)		NOT NULL,
    `password`	VARCHAR(60)		NOT NULL,
    `balance`	DECIMAL(10,5)	UNSIGNED NOT NULL DEFAULT 0.00,
    PRIMARY KEY(`id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с информацией о расположении данных пользователей
--
CREATE TABLE `users_shards`(
    `from_user_id`  BIGINT  UNSIGNED NOT NULL, -- минимальный ID юзера (включительно)
    `to_user_id`    BIGINT  UNSIGNED NOT NULL, -- максимальный ID юзера (не включительно)
    `host_id`       INT     UNSIGNED NOT NULL, -- ID хоста
    `type`          TINYINT UNSIGNED NOT NULL, -- тип записи: 0 - по адресу лежат данные о расположении, 1 - данные о пользователях
    PRIMARY KEY(`from_user_id`, `to_user_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с распределением логинов пользователей по хостам (нужна для поиска по логину)
--
CREATE TABLE `users_logins_shards`(
	`from_login`	VARCHAR(25) NOT NULL,
	`to_login`		VARCHAR(25) NOT NULL,
	`host_id`		INT		    UNSIGNED NOT NULL, -- ID хоста
	`type`			TINYINT		UNSIGNED NOT NULL, -- тип записи: 0 - по адресу лежат данные о расположении, 1 - данные 0 пользователях
	PRIMARY KEY(`from_login`, `to_login`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с логинами пользователей (нужна для поиска по логину)
--
CREATE TABLE `users_logins`(
	`login`	VARCHAR(25) NOT NULL,
	`id`	BIGINT		UNSIGNED NOT NULL,
	PRIMARY KEY(`login`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с информацией о расположении ленты заказов пользователя.
-- Данные по расположению лент пользователей лежат на одних хостах с данными пользователей.
--
CREATE TABLE `users_orders_shards`(
    `user_id`       BIGINT  UNSIGNED NOT NULL, -- ID юзера
    `from_order_id` BIGINT  UNSIGNED NOT NULL, -- минимальный ID заказа (включительно)
    `host_id`       INT     UNSIGNED NOT NULL, -- ID хоста, где лежат данные заказов
    PRIMARY KEY(`user_id`, `from_order_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с заказами пользователей
--
CREATE TABLE `users_orders`(
    `user_id`           BIGINT          UNSIGNED NOT NULL, -- ID заказчика
    `order_id`          BIGINT          UNSIGNED NOT NULL, -- ID заказа
    `ts`                BIGINT          UNSIGNED NOT NULL, -- unix-время добавления заказа
    `price`             DECIMAL(10,2)   UNSIGNED NOT NULL DEFAULT '0.00', -- сумма заказа
    `finished`          TINYINT         UNSIGNED NOT NULL DEFAULT '0', -- выполнен ли заказ
    `finished_user_id`  BIGINT          UNSIGNED NOT NULL, -- ID исполнителя заказа
    `finished_ts`       INT             UNSIGNED NOT NULL DEFAULT 0, -- unix-время выполнения заказа
    PRIMARY KEY(`user_id`, `order_id`),
    KEY(`user_id`, `finished`, `order_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с дайджестом заказов.
-- В дайджестом содержатся только невыполненные заказы.
--
CREATE TABLE `orders_digest`(
    `ts`        BIGINT          UNSIGNED NOT NULL, -- unix-время добавления заказа
    `user_id`   BIGINT          UNSIGNED NOT NULL, -- ID заказчика
    `order_id`  BIGINT          UNSIGNED NOT NULL, -- ID заказа
    `price`     DECIMAL(10,2)   UNSIGNED NOT NULL DEFAULT '0.00', -- сумма заказа
    PRIMARY KEY(`ts`, `user_id`, `order_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с данными о шардах даджеста.
--
CREATE TABLE `orders_digest_shards`(
    `from_ts`   BIGINT UNSIGNED NOT NULL, -- минимальное unix-время заявки (включительно)
    `to_ts`     BIGINT UNSIGNED NOT NULL, -- максимальное unix-время заявки (не включительно)
    `from_hash` VARCHAR(32) NOT NULL DEFAULT '',
    `to_hash`   VARCHAR(32) NOT NULL DEFAULT '',
    `host_id`   INT     UNSIGNED NOT NULL, -- ID хоста
    PRIMARY KEY(`from_ts`, `to_ts`, `from_hash`, `to_hash`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Информация о шардах выполненных пользователем заказов.
-- Данные по расположению выполненных заказов лежат на одних хостах с данными пользователей.
--
CREATE TABLE `finished_orders_shards`(
    `finished_user_id`  BIGINT  UNSIGNED NOT NULL, -- ID пользователя, выполнившего заказ
    `from_finished_ts`  BIGINT  UNSIGNED NOT NULL, -- минимальное время выполнения заказа (включительно)
    `host_id`           INT     UNSIGNED NOT NULL, -- ID хоста, на котором лежит лента выполненных заказов пользователя
    PRIMARY KEY(`finished_user_id`, `from_finished_ts`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с данными по выполненным заказам
--
CREATE TABLE `finished_orders`(
    `finished_user_id`  BIGINT UNSIGNED NOT NULL, -- ID исполнителя
    `finished_ts`       BIGINT UNSIGNED NOT NULL, -- unix-время исполнения заказа
    `user_id`           BIGINT UNSIGNED NOT NULL, -- ID заказчика
    `order_id`          BIGINT UNSIGNED NOT NULL, -- ID заказа
    `income`            DECIMAL(10,2)   UNSIGNED NOT NULL DEFAULT '0.00', -- прибыль исполнителя
    PRIMARY KEY(`finished_user_id`, `finished_ts`, `user_id`, `order_id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;
