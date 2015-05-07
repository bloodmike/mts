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
    CONSTANTS = CONSTANTS || {};
	
    if (!CONSTANTS.hasOwnProperty(code)) {
        console.log('Код [%s] не найден', code);
        return code.toString();
    }
    return CONSTANTS[code];
}

/**
 * Сериализует переданный объект в строку
 * 
 * @param {Object} object сериализуемый объект
 * @param {string} [prefix] префикс для подстановки к полю при сериализации
 * 
 * @returns {string} строка с параметрами вида: a=b&c=d&e[0]=f&...
 */
function object2String(object, prefix) {
	var str = [];
	for (var i in object) {
		if (object.hasOwnProperty(i)) {
			var key = prefix ? prefix + "[" + i + "]" : i;
			var value = object[i];
			var part = typeof value === "object" ? object2String(value, key) : encodeURIComponent(key) + "=" + encodeURIComponent(value);
			str.push(part);
		}
	}
	return str.join("&");
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
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
			var json = JSON.parse(xhr.responseText);
            if (xhr.status === 200 && typeof json == 'object') {
                callback(json);
            } else if (callbackFail !== null) {
                callbackFail(xhr);
            } else {
				console.log(xhr);
			}
        }
    };
    xhr.send(object2String(parameters));
    return xhr;
}

/**
 * Удаляет у переданного элемента css-класс
 * 
 * @param {Element|null} element элемент
 * @param {string} className имя класса, которое требуется удалить
 * 
 * @returns {Element|null} переданный элемент
 */
function elementRemoveClass(element, className) {
	if (element !== null) {
		var classNames = element.className.split(' ');
		var newClassNames = [];
		
		for (var i in classNames) {
			if (className !== classNames[i]) {
				newClassNames.push(classNames[i]);
			}
		}
		element.className = newClassNames.join(' ');
	}
	
	return element;
}

/**
 * Добавляет переданному элементу css-класс
 * 
 * @param {Element|null} element элемент
 * @param {string} className имя класса, которое требуется добавить
 * 
 * @returns {Element|null} переданный элемент
 */
function elementAddClass(element, className) {
	if (element !== null) {
		element.className += ' ' + className;
	}
	
	return element;
}
