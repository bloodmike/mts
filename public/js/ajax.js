/**
 * Ajax-запросы
 * 
 * @author mkoshkin
 */

/**
 * Отправить запрос на добавление заказа
 * 
 * @param {number} price сумма заказа
 * @param {Object} callback обработчик корректного ответа сервера
 * @param {Object} [callbackFail] обработчик ошибочного ответа
 * 
 * @return {boolean} отправлен ли запрос на сервер
 */
function createOrder(price, callback, callbackFail) {
    callbackFail = callbackFail || null;
    if (price >= 0) {
        ajaxJson(
                "POST",
                "/order_add.php", {
                    price: price
                },
                callback, 
                callbackFail);
                
        return true;
    }
    return false;
}

/**
 * Отправить запрос на выполнение заказа
 * 
 * @param {number} userId ID заказчика
 * @param {number} orderId ID заказа
 * @param {Object} callback обработчик корректного ответа сервера
 * @param {Object} [callbackFail] обработчик ошибочного ответа
 * 
 * @returns {boolean} отправлен ли запрос на сервер
 */
function executeOrder(userId, orderId, callback, callbackFail) {
    callbackFail = callbackFail || null;
    if (userId > 0 && orderId > 0) {
        ajaxJson(
                "POST",
                "/order_execute.php",
                {
                    user_id: userId,
                    order_id: orderId
                },
                callback,
                callbackFail);
                
        return true;
    }
    return false;
}

