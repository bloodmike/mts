/** 
 * Общие для всех страниц функции
 * 
 * @author mkoshkin
 */

/**
 * По указанному коду возвращает текст ошибки.
 * 
 * @param {number} code код ошибки
 * 
 * @returns {string} текст ошибки или строка с кодом ошибки (если текст не найден)
 */
function code2error(code) {
    var CONSTANTS = CONSTANTS || {};
    if (!CONSTANTS.hasOwnProperty(code)) {
        console.log('Код [%s] не найден', code);
        return code.toString();
    }
    return CONSTANTS[code];
}

/**
 * Ajax-запрос
 * 
 * @param {string} method
 * @param {string} url
 * @param {Object} parameters
 * @param {Object} callback
 * @param {Object} [callbackFail]
 * 
 * @return {XMLHttpRequest}
 */
function ajaxJson(method, url, parameters, callback, callbackFail) {
    
    callbackFail = callbackFail || null;
    
    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200 || callbackFail === null) {
                callback(xhr.responseText);
            } else {
                callbackFail(xhr);
            }
        }
    };
    xhr.send(null);
    return xhr;
}
