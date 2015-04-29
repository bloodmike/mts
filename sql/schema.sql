--
-- Таблица с пользователями
--
CREATE TABLE `users`(
    `id`		BIGINT			NOT NULL UNSIGNED,
    `login`		VARCHAR(25)		NOT NULL,
    `password`	VARCHAR(50)		NOT NULL,
    `balance`	DECIMAL(10,5)	NOT NULL UNSIGNED DEFAULT '0.00',
    PRIMARY KEY (`id`)
) ENGINE=innoDB DEFAULT CHARSET=utf8;

--
-- Таблица с информацией о расположении данных пользователей
--
CREATE TABLE `users_shards`(
    `from_user_id`  BIGINT  NOT NULL UNSIGNED, -- минимальный ID юзера (включительно)
    `to_user_id`    BIGINT  NOT NULL UNSIGNED, -- максимальный ID юзера (не включительно)
    `host_id`       INT     NOT NULL UNSIGNED, -- ID хоста
    `type`          TINYINT NOT NULL UNSIGNED, -- тип записи: 0 - по адресу лежат данные о расположении, 1 - данные о пользователях
    PRIMARY KEY (`from_user_id`, `to_user_id`)
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
