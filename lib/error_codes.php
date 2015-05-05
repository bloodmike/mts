<?php
/**
 * Константы кодов ошибок, возвращаемых ajax-запросами
 * 
 * @author mkoshkin
 */

namespace Error;

/**
 * Пользователь не авторизован или данные авторизации устарели
 */
const USER_NOT_AUTHORIZED = 1;

/**
 * Заказ не найден
 */
const ORDER_NOT_FOUND = 2;

/**
 * При обработке запроса возникла ошибка
 */
const PROCESSING_ERROR = 3;

/**
 * Заказ уже выполнен
 */
const ORDER_ALREADY_EXECUTED = 4;

/**
 * Нельзя выполнить собственный заказ
 */
const CANNT_EXECUTE_OWN_ORDER = 5;
