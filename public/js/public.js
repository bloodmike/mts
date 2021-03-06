/** 
 * Общие для всех страниц функции
 * 
 * @author mkoshkin
 */

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
	if (method === "POST") {
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	}
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            var json = null;
            try {
                json = JSON.parse(xhr.responseText);
            } catch (e) {
                console.log(e);
            }
            
            if (xhr.status === 200 && typeof json == 'object') {
                callback(json);
                return;
            } else if (!!xhr.getAllResponseHeaders()) { 
                Errors.show('При выполнении запроса возникла ошибка.');
                if (callbackFail !== null) {
                    callbackFail(xhr);
                } else {
                    console.log(xhr);
                }
            }
        }
    };
	if (method === "POST") {
		xhr.send(object2String(parameters));
	} else {
		xhr.send(null);
	}
    return xhr;
}

var Html = {
    /**
     * Добавляет переданному элементу css-класс
     * 
     * @param {Element|null} element элемент
     * @param {string} className имя класса, которое требуется добавить
     * 
     * @returns {Element|null} переданный элемент
     */
    addClass: function(element, className) {
        if (element !== null) {
            element.className += ' ' + className;
        }

        return element;
    },
    
    /**
     * Удаляет у переданного элемента css-класс
     * 
     * @param {Element|null} element элемент
     * @param {string} className имя класса, которое требуется удалить
     * 
     * @returns {Element|null} переданный элемент
     */
    removeClass: function(element, className) {
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
};

/**
 * @type {Object} функционал для вывода ошибок
 */
var Errors = {
    /**
     * @type {Element}
     */
    __container: null,
    
    /**
     * @type {number}
     */
    __timeout: null,
    
    /**
     * @returns {Element} элемент для отображения ошибок
     */
    getContainer: function() {
            if (this.__container === null) {
                this.__container = document.getElementById('header-error');
            }
            return this.__container;
        },
    
    /**
     * Скрыть выведенную ошибку
     */
    hide: function() {
            Errors.__timeout = null;
            Html.removeClass(Errors.getContainer(), 'visible');
        },
    
    /**
     * Вывести системную ошибку.
     * Если в это время уже выведена другая ошибка - она будет скрыта.
     * Сообщение об ошибке скрывается через 5 секунд после появления
     * 
     * @param {string} error текст ошибки
     */
    show: function(error) {
            var container = this.getContainer();
            Html.addClass(container, 'visible');
            container.innerHTML = error;
            if (this.__timeout !== null) {
                clearTimeout(this.__timeout);
            }
            
            this.__timeout = setTimeout(this.hide, 5000);
        },
    
    /**
     * Вывести ошибки из json-ответа
     * 
     * @param {JsonResponse} Response json-ответ
     * 
     * @returns {undefined}
     */
    showFromResponse: function(Response) {
        if (Response.hasErrors()) {
            this.show(this.codes2errors(Response.getErrors()).join('\n'));
        }
    },
    
    /**
     * По указанному коду возвращает текст ошибки.
     * 
     * @param {number} code код ошибки
     * 
     * @returns {string} текст ошибки или строка с кодом ошибки (если текст не найден)
     */
    code2error: function(code) {
        CONSTANTS = CONSTANTS || {};
        if (!CONSTANTS.hasOwnProperty(code)) {
            console.log('Код [%s] не найден', code);
            return code.toString();
        }
        return CONSTANTS[code];
    },
    
    /**
     * @param {Number[]} codes список кодов ошибок
     * 
     * @returns {String[]} список текстов ошибок, соответствующих кодам
     */
    codes2errors: function(codes) {
        var messages = [];
        CONSTANTS = CONSTANTS || {};
        
        for (var i in codes) {
            messages.push(this.code2error(codes[i]));
        }
        
        return messages;
    }
};

/**
 * Хранилище данных о пользователях
 * 
 * @type {Object}
 */
var UserStorage = {
    /**
     * @type {Object} справочник логинов пользователей
     */
    __logins: {},
    
    /**
     * @param {Number} id ID пользователя
     * 
     * @returns {String} логин пользователя (или пустая строка - если логина нет в справочнике)
     */
    getLogin: function(id) {
        if (this.__logins.hasOwnProperty(id) && this.__logins[id] !== null) {
            return this.__logins[id];
        }
        return '';
    },
    
    /**
     * Добавить логин в справочник
     * 
     * @param {Number} id ID пользователя
     * @param {String} login логин
     */
    addLogin: function(id, login) {
        this.__logins[id] = login;
    },
    
    /**
     * Добавить всех пользователей из хэшмэпа
     * 
     * @param {Object} loginsMap хэшмэп пользователей: userId => login
     */
    addLogins: function(loginsMap) {
        for (var userId in loginsMap) {
            if (loginsMap.hasOwnProperty(userId)) {
                this.addLogin(userId, loginsMap[userId]);
            }
        }
    },
    
    /**
     * @param {Number} id ID пользователя
     * 
     * @returns {Boolean} есть ли пользователь в справочнике
     */
    hasLogin: function(id) {
        return this.__logins.hasOwnProperty(id);
    },
    
    /**
     * Сохраняет информацию, что пользователь не найден
     * 
     * @param {Number} id ID пользователя
     */
    setEmptyLogin: function(id) {
        this.__logins[id] = null;
    },
    
    /**
     * @param {Number} id ID пользователя
     * 
     * @returns {Boolean} является ли указанный пользователь текущим
     */
    isCurrent: function(id) {
        return currentUser !== null && id === currentUser.id;
    }
};

if (currentUser !== null) {
    UserStorage.addLogin(currentUser.id, currentUser.login);
}


/**
 * Обработчик json-ответов
 * 
 * @constructor
 * 
 * @param {Object} json
 */
var JsonResponse = function(json) {
    /**
     * @property {Number[]} __errors коды ошибок
     */
    this.__errors = [];
    
    if (json.hasOwnProperty('error')) {
        this.__errors = json['error'];
    }
    
    /**
     * @property {Object} __fields прочие данные, переданные с сервера
     */
    this.__fields = {};
    
    for (var i in json) {
        if (json.hasOwnProperty(i) && i !== 'error') {
            this.__fields[i] = json[i];
        }
    }
	
    return this;
};

/**
 * @returns {Boolean} есть ли в ответе ошибки
 */
JsonResponse.prototype.hasErrors = function() {
    return this.__errors.length > 0;
}

/**
 * @param {Number} code код ошибки
 * 
 * @returns {Boolean} есть ли указанный код ошибки в ответе
 */
JsonResponse.prototype.hasError = function(code) {
    for (var i in this.__errors) {
        if (this.__errors[i] == code) {
            return true;
        }
    }
    return false;
}

/**
 * @returns {Number[]} список кодов ошибок
 */
JsonResponse.prototype.getErrors = function() {
    return this.__errors;
}

/**
 * Получить значение поля ответа
 * 
 * @param {String} field имя поля из ответа
 * @param {*} defaultValue значение, которое следует вернуть если указанного поля в ответе не было
 * 
 * @returns {*}
 */
JsonResponse.prototype.getField = function(field, defaultValue) {
    if (this.__fields.hasOwnProperty(field)) {
        return this.__fields[field];
    }
    return defaultValue;
}

/**
 * Функции для оповещений других вкладок о событиях текущей вкладки
 * 
 * @type Object
 */
var Broadcast = {
    
    /**
     * @type Number событие "пользователь разлогинился"
     */
    EVENT_LOGOUT: 1,
    
    /**
     * @type Number событие "пользователь добавил заказ"
     */
    EVENT_ORDER_ADDED: 2,
    
    /**
     * @type Number событие "пользователь выполнил заказ"
     */
    EVENT_ORDER_EXECUTED: 3,
    
	/**
	 * @type Number событие "загружены логины пользователей"
	 */
	EVENT_USER_LOGINS_LOADED: 4,
	
    /**
     * @type String ссылка, передаваемая параметром origin
     */
    __origin: '',
    
    /**
     * Отправить широковещательный запрос
     * 
     * @private
     * 
     * @param {Number} eventId
     * @param {Array} data
     */
    __sendMessage: function(eventId, data) {
        if (!!window.postMessage) {
            //window.postMessage(eventId.toString() + "|" + data.join("|"), Broadcast.__origin);
			window.localStorage.setItem('event', eventId.toString() + "|" + data.join("|"));
        }
    },
    
    /**
     * Текущий пользователь вышел из системы
     */
    logOut: function() {
        Broadcast.__sendMessage(Broadcast.EVENT_LOGOUT, []);
    },
    
    /**
     * Текущий пользователь выполнил заказ
     * 
     * @param {Number} userId ID заказчика
     * @param {Number} orderId ID заказа
     * @param {Number} balanceDelta сумма, на которую был изменен баланс
     * @param {Number} finishTs unix-время выполнения заказа
     */
    orderExecuted: function(userId, orderId, balanceDelta, finishTs) {
        Broadcast.__sendMessage(Broadcast.EVENT_ORDER_EXECUTED, [userId, orderId, balanceDelta, finishTs]);
    },
    
    /**
     * Текущий пользователь добавил заказ
     * 
     * @param {Number} orderId ID заказа
     * @param {Number} price сумма заказа
     * @param {Number} ts unix-время добавления заказа
     */
    orderAdded: function(orderId, price, ts) {
        Broadcast.__sendMessage(Broadcast.EVENT_ORDER_ADDED, [orderId, price, ts]);
    },
    
	/**
	 * Загружены логины пользователей
	 * 
	 * @param {Object} loginsMap хэшмэп логинов пользователей
	 */
	userLoginsLoaded: function(loginsMap) {
		var hasLogins = false;
		for (var i in loginsMap) {
			if (loginsMap.hasOwnProperty(i)) {
				hasLogins = true;
				break;
			}
		}
		
		if (hasLogins) {
			Broadcast.__sendMessage(Broadcast.EVENT_USER_LOGINS_LOADED, [JSON.stringify(loginsMap)]);
		}
	},
	
    /**
     * Обработчик события "пользователь разлогинился"
     */
    logOutListener: function() {
        window.location.href = "/login.php";
    },
    
    /**
     * Обработчик события "пользователь добавил заказ"
     * 
     * @param {Number} orderId
     * @param {Number} price
     * @param {Number} ts
     */
    orderAddedListener: function(orderId, price, ts) {
        
    },
    
    /**
     * Обработчик события "пользователь выполнил заказ"
     * 
     * @param {Number} userId
     * @param {Number} orderId
     * @param {Number} balanceDelta
     * @param {Number} finishTs
     */
    orderExecutedListener: function(userId, orderId, balanceDelta, finishTs) {
        Layout.updateBalance(parseFloat(balanceDelta));
    },
    
	/**
	 * Обработчик события "загружены логины пользователей"
	 * 
	 * @param {Object} loginsMap
	 */
	userLoginsLoadedListener: function(loginsMap) {
		UserStorage.addLogins(loginsMap);
	},
	
    /**
     * Обработчик событий о приходе сообщений
     * 
     * @param {Object} event
     * @param {String} event.data
     * @param {String} event.origin
     */
    receiveMessage: function(event) {
		console.log('Событие получено: %s', event.newValue);
		console.log(event);
		        
        var data = event.newValue.toString().split('|');
        var eventId = parseInt(data[0]);
        if (isNaN(eventId)) {
            eventId = 0;
        }
        
        switch (eventId) {
            case Broadcast.EVENT_LOGOUT:
                Broadcast.logOutListener();
                break;
                
            case Broadcast.EVENT_ORDER_ADDED:
                Broadcast.orderAddedListener(data[1], data[2], data[3]);
                break;
                
            case Broadcast.EVENT_ORDER_EXECUTED:
                Broadcast.orderExecutedListener(data[1], data[2], data[3], data[4]);
                break;
            
			case Broadcast.EVENT_USER_LOGINS_LOADED:
				Broadcast.userLoginsLoadedListener(JSON.parse(data[1]));
				break;
			
            default:
                console.log('Неизвестное событие: %s', event.newValue.toString());
                break;
        }
    },
    
    /**
     * Настройка прослушивания и отправки сообщений
     */
    setUp: function() {
        Broadcast.__origin = window.location.protocol + "//" + window.location.host;
        //window.addEventListener("message", Broadcast.receiveMessage, true);
		if (window.addEventListener) {
			window.addEventListener("storage", Broadcast.receiveMessage, false);
		} else {
			window.attachEvent("onstorage", Broadcast.receiveMessage);
		}
    }
};

/**
 * Функции для управления общими блоками страницы: баланс, попапы и т.д.
 * 
 * @type {Object}
 */
var Layout = {
    /**
     * @private
     * 
     * @type {Element} блок для вывода баланса
     */
    __balanceBlock: null,
    
    /**
     * @returns {Element} блок вывода баланса
     */
    getBalanceBlock: function() {
        if (this.__balanceBlock === null) {
            this.__balanceBlock = document.getElementById('header-balance');
        }
        
        return this.__balanceBlock;
    },
    
    /**
     * Увеличивает выводимое значение баланса на указанную величину
     * 
     * @param {Number} balanceDelta величина, на которую нужно увеличить баланс
     */
    updateBalance: function(balanceDelta) {
		if (balanceDelta > 0) {
            var block = this.getBalanceBlock();
            var balance = parseFloat(block.innerHTML) + balanceDelta;
			var balanceString = Math.round(balance * 100).toString().replace(/([\d]{2})$/, '.$1');
			
            block.innerHTML = balanceString;
        }
    },
	
	/**
	 * Вешает события, связанные с диалогом добавления заказа.
	 */
	setUp: function() {
		
		var logOutButton = document.getElementById('header-menu__logout');
		if (logOutButton !== null) {
			logOutButton.onclick = function(event) {
				if (confirm('Хотите выйти?')) {
					Broadcast.logOut();
				} else {
					return false;
				}
			};
		}
		
        /**
         * @type Boolean выполняется ли сейчас добавление заказа
         */
        var orderAddProcessing = false;
        
		var divDialog = document.getElementById('add-order-dialog');
		if (divDialog === null) {
			return;
		}
		divDialog.onclick = function() {
			Html.addClass(divDialog, 'hidden');
		};
		
		document.getElementById('header-menu__open-dialog').onclick = function() {
			Html.removeClass(divDialog, 'hidden');
		};
		
		document.getElementById('add-order-dialog__content').onclick = function(event) {
			event.stopPropagation();
		};
		
		var addOrderErrorMessage = document.getElementById('add-order-error-message');
        
        var addOrderSuccessMessage = document.getElementById('add-order-success-message');
	
		var priceInput = document.getElementById('add-order-form__price');

		/**
		 * @param {Event} event
		 */
		var addOrderFormListener = function(event) {
            if (orderAddProcessing) {
                return;
            }
            
			event.preventDefault();
			var price = parseFloat(priceInput.value.replace(/[ ]/g, ''));
			if (!isNaN(price)) {
				Html.removeClass(priceInput, 'error-box');
				Html.removeClass(addOrderErrorMessage, 'error-visible');
				Html.removeClass(addOrderSuccessMessage, 'success-visible');
				orderAddProcessing = Actions.createOrder(
						price, 
						function(json) {
							var Response = new JsonResponse(json);
							if (Response.hasErrors()) {
								addOrderErrorMessage.innerHTML = '';
								Html.addClass(addOrderErrorMessage, 'error-visible');

								var errors = Response.getErrors();
								for (var i in errors) {
									var div = document.createElement('div');
									div.innerHTML = Errors.code2error(errors[i]);
									addOrderErrorMessage.appendChild(div);
								}
							} else {
								priceInput.value = '';
								priceInput.focus();
                                
                                Html.addClass(addOrderSuccessMessage, 'success-visible');
                                setTimeout(function() {
                                    Html.removeClass(addOrderSuccessMessage, 'success-visible');
                                }, 1500);
                                
								for (var i in Layout.orderAddedListeners) {
									Layout.orderAddedListeners[i]({
										order_id:	json.order.id,
										price:		json.order.price,
										ts:			json.order.ts
									});
								}
                                
                                Broadcast.orderAdded(json.order.id, json.order.price, json.order.ts);
							}
                            orderAddProcessing = false;
                            Html.removeClass(divDialog, 'processing');
						},
						function(xhr) {
							console.log(xhr);
                            orderAddProcessing = false;
                            Html.removeClass(divDialog, 'processing');
						});
                
                if (orderAddProcessing) {
                    Html.addClass(divDialog, 'processing');
                }
                        
			} else {
				Html.addClass(priceInput, 'error-box');
			}
		};

		document.getElementById('add-order-form').onsubmit = addOrderFormListener;
	},
	
	/**
	 * @type Function[] коллбэки, вызываемые при добавлении заказа через диалог
	 */
	orderAddedListeners: []
}

/**
 * 
 * @type Object функции для работы с датами / временем
 */
var DateProc = {
    /**
     * @type Number миллисекунд в сутках
     */
    TIME_DAY: 86400000,
    
    /**
     * @param {Number} num номер дня / месяца
     * 
     * @returns {String} указанное число с проставленным нулём слева (если требуется)
     */
    padNumber: function(num) {
        return (num < 10 ? '0' : '') + num.toString();
    },
    
    /**
     * @param {Number} timestamp unix-время (в секундах)
     * 
     * @returns {String} дата-время, в формате d.m.Y H:i
     */
    dateTime: function(timestamp) {
        var date = new Date(timestamp * 1000);
        var dateStr = this.padNumber(date.getDate()) + '.' + this.padNumber(date.getMonth() + 1) + '.' + date.getFullYear();
        
        var timeStr = this.padNumber(date.getHours()) + ':'+ this.padNumber(date.getMinutes());
        
        return dateStr + ' ' + timeStr;
    },
    
    /**
     * @param {Number} timestamp unix-время (в секундах)
     * 
     * @returns {String} дата-время в минималистичном формате: 
     *                  сегодняшние - только время, 
     *                  вчерашние - дата/время,
     *                  остальные - дата
     */
    shortDateTime: function(timestamp) {
        var timestampMs = timestamp * 1000;
        var date = new Date(timestampMs);
        
        var dayDiff = this.getDayDiff(timestampMs, (new Date()).getTime());
        
        if (dayDiff === 0) {
            return this.padNumber(date.getHours()) + ':'+ this.padNumber(date.getMinutes()) + ':' + this.padNumber(date.getSeconds());
        } else if (dayDiff === 1) {
            return this.dateTime(timestamp);
        }
        
        return this.padNumber(date.getDate()) + '.' + this.padNumber(date.getMonth() + 1) + '.' + date.getFullYear();
    },
    
    /**
     * @param {Number} timestampFrom меньшее unix-время (в миллисекундах)
     * @param {Number} timestampTo большее unix-время (в миллисекундах)
     * 
     * @returns {Number} разница в днях между указанными отметками времени
     */
    getDayDiff: function(timestampFrom, timestampTo) {
        return Math.floor(timestampTo / this.TIME_DAY) - Math.floor(timestampFrom / this.TIME_DAY);
    }
    
};

/* Инициализируем страницу */
Layout.setUp();

/* Вешаем прослушку событий postMessage */
Broadcast.setUp();
