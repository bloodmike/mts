--
-- Первичное наполнение схемы данными при условии, что данные раскиданы по хостам
--
INSERT INTO `users_shards`
    SET `from_user_id` = 1, `to_user_id` = 2, `host_id` = 1, `type` = 1;

INSERT INTO `users_shards`
    SET `from_user_id` = 2, `to_user_id` = 1000000, `host_id` = 2, `type` = 1;

INSERT INTO `orders_digest_shards`
    SET `from_ts` = 0, `to_ts` = 1893369600, `from_hash` = '', `to_hash` = '7fffffffffffffffffffffffffffffff', `host_id` = 1;

INSERT INTO `orders_digest_shards`
    SET `from_ts` = 0, `to_ts` = 1893369600, `from_hash` = '80000000000000000000000000000000', `to_hash` = 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', `host_id` = 2;

INSERT INTO `users_logins_shards`
    SET `from_login` = '', `to_login` = 'zzzzzzzzzzzzzzzzzzzzzzzzz', `host_id` = 1, `type` = 1;

--
-- Добавляем пользователя 1 и информацию о расположении его данных (лежит на первом хосте, данные - на втором)
--
INSERT INTO `users`
    SET `id` = 1, `login` = 'mike', `password` = '$2y$10$dGhlcmUtaXMtbm8tc2Fsd.dzrCAuWSXQGrk2bDS5XI1RuYRio.4P2', `balance` = '0.00';

INSERT INTO `users_logins`
    SET `login` = 'mike', `id` = 1;

INSERT INTO `users_orders_shards`
    SET `user_id` = 1, `from_order_id` = 1, `host_id` = 2;

INSERT INTO `finished_orders_shards`
    SET `finished_user_id` = 1, `from_finished_ts` = 0, `host_id` = 2;

--
-- Добавляем пользователя 2 и информацию о расположении его данных (лежит на втором хосте, данные - там же)
--
INSERT INTO `users`
    SET `id` = 2, `login` = 'vadim', `password` = '$2y$10$dGhlcmUtaXMtbm8tc2Fsd.dzrCAuWSXQGrk2bDS5XI1RuYRio.4P2', `balance` = '0.00';

INSERT INTO `users_logins`
    SET `login` = 'vadim', `id` = 2;

INSERT INTO `users_orders_shards`
    SET `user_id` = 2, `from_order_id` = 1, `host_id` = 2;

INSERT INTO `finished_orders_shards`
    SET `finished_user_id` = 2, `from_finished_ts` = 0, `host_id` = 2;

--
-- Добавляем пользователя 3 и информацию о расположении его данных (лежит на втором хосте, данные - на первом)
--
INSERT INTO `users`
    SET `id` = 3, `login` = 'alex', `password` = '$2y$10$dGhlcmUtaXMtbm8tc2Fsd.dzrCAuWSXQGrk2bDS5XI1RuYRio.4P2', `balance` = '0.00';

INSERT INTO `users_logins`
    SET `login` = 'alex', `id` = 3;

INSERT INTO `users_orders_shards`
    SET `user_id` = 3, `from_order_id` = 1, `host_id` = 1;

INSERT INTO `finished_orders_shards`
    SET `finished_user_id` = 3, `from_finished_ts` = 0, `host_id` = 1;
