/**
 * Страница с дайджестом заказов
 * 
 * @author mkoshkin
 */

(function() {
	
	/**
	 * @type {Element} Список заказов на странице
	 */
	var ordersList = document.getElementById('orders-list');
    
	/**
     * @type {Element} Кнопка "Загрузить еще" внизу списка заказов
     */
    var ordersListLoadMore = document.getElementById('orders-list__load-more');
    
    /**
     * @type {Boolean} выполняется ли в настоящий момент загрузка заказов
     */
    var ordersListLoading = false;
        
    /**
     * @type {Boolean} выполняется ли в настоящий момент загрузка новых заказов
     */
    var ordersNewLoading = false;
    
	/**
     * @type {Element} Кнопка "Показать новые заказы" вверху списка заказов
     */
    var ordersListLoadNew = document.getElementById('orders-list__load-new');
    
	/**
     * @type {Element} Блок для вывода количества новых заказов
     */
    var ordersListNewCount = document.getElementById('orders-list__new-count');
    
    /**
     * @type {Number} минимальное время добавления заказа
     */
    var ordersMinTs = Math.ceil((new Date()).getTime() / 1000);
    
	/**
	 * @type Number ID последнего добавленного заказа
	 */
	var ordersLastOrderId = 0;
	
	/**
	 * @type Number ID заказчика последнего заказа
	 */
	var ordersLastUserId = 0;
	
    /**
     * @type Number
     */
    var ordersMaxTs = ordersMinTs;
    
	/**
	 * @type Number ID первого заказа в ленте
	 */
	var ordersFirstOrderId = 0;
	
	/**
	 * @type Number ID заказчика первого заказа в ленте
	 */
	var ordersFirstUserId = 0;
    
    /**
     * @type Boolean выполнена ли первая загрузка ленты
     */
    var firstLoadMade = false;
    
	/**
	 * @param {type} userId ID заказчика
	 * @param {type} orderId ID заказа
	 * 
	 * @returns {String} ID блока с заказом
	 */
	function getOrderDivId(userId, orderId) {
		return 'order-' + userId + '-' + orderId;
	}
	
	/**
	 * Создать html-блок для вывода заказа
	 * 
	 * @param {Object} order данные заказа
	 * @param {Number} order.user_id ID заказчика
	 * @param {Number} order.order_id ID заказа
	 * @param {Number} order.price сумма заказа
	 * @param {Number} order.ts unix-время создания заказа
	 * 
	 * @returns {Element} div с данными заказа
	 */
	var createOrderElement = function(order) {
		var div = document.createElement('div');
		div.className = 'order';
		div.id = getOrderDivId(order['user_id'], order['order_id']);
		div.ts = order['ts'];

		var divOwner = document.createElement('div');
		divOwner.className = 'order__owner';
		divOwner.title = 'Заказчик';

		var login = UserStorage.getLogin(order['user_id']);
		if (login === '') {
			login = 'неизвестный';
		}
		
		if (login.length >= 15) {
			divOwner.className += ' order__owner_long';
		}
		
		divOwner.innerHTML = login;
		div.appendChild(divOwner);

		var divPrice = document.createElement('div');
		divPrice.className = 'order__price';
		divPrice.title = 'Сумма заказа';
		divPrice.innerHTML = order['price'].toString();
		div.appendChild(divPrice);

		var divOrderId = document.createElement('div');
		divOrderId.className = 'order__id';
		divOrderId.title = 'ID заказа';
		divOrderId.innerHTML = order['user_id'] + '-' + order['order_id'];
		div.appendChild(divOrderId);

		if (!UserStorage.isCurrent(order['user_id'])) {
			var orderExecuting = false;
		
			var divExecute = document.createElement('div');
			divExecute.className = 'order__execute';
			divExecute.title = 'Выполнить заказ';
			divExecute.innerHTML = 'Выполнить';
			divExecute.onclick = function() {
				if (orderExecuting) {
					return;
				}
				Html.addClass(div, 'order_executing');
				orderExecuting = true;
				Actions.executeOrder(
					order['user_id'], 
					order['order_id'],
					function(json) {
						Html.removeClass(div, 'order_executing');
						orderExecuting = false;
						var Response = new JsonResponse(json);
						if (Response.hasErrors()) {
							if (Response.hasError(4)) {
								div.parentNode.removeChild(div);
							}
							Errors.showFromResponse(Response);
						} else {
							Html.addClass(div, 'order_finished');
							Layout.updateBalance(parseFloat(Response.getField('balanceDelta')));
							setTimeout(function() {
								div.parentNode.removeChild(div);
							}, 1000);
							Broadcast.orderExecuted(
                                    order['user_id'], 
                                    order['order_id'], 
                                    Response.getField('balanceDelta'), 
                                    Response.getField('finishTs'));
						}
					},
					function (xhr) {
						Html.removeClass(div, 'order_executing');
						orderExecuting = false;
					}
				);
			};
			div.appendChild(divExecute);
		} else {
            var divOwnerIsCurrent = document.createElement('div');
            divOwnerIsCurrent.className = 'order__owner-is-current';
            divOwnerIsCurrent.innerHTML = 'Это ваш заказ';
            div.appendChild(divOwnerIsCurrent);
        }
		return div;
	}
	
    /**
     * @param {Number} userId ID заказчика
     * @param {Number} orderId ID заказа
     * 
     * @returns {Boolean} выведен ли указанный заказ
     */
    var orderExists = function(userId, orderId) {
        return document.getElementById(getOrderDivId(userId, orderId)) !== null;
    };
    
	/**
	 * @param {Number} userId
	 * @param {Number} orderId
	 * @param {Number} ts
	 * 
	 * @returns {Element|null} блок, перед которым нужно разместить указанный заказ, или null если заказ нужно разместить в конце списка
	 */
	var getNodeToInsertBefore = function(userId, orderId, ts) {
		
		var prevNode = null;
		var orderDivId = getOrderDivId(userId, orderId);
		
		for (var i in ordersList.childNodes) {
			
			var div = ordersList.childNodes[i];
			var divTs = parseInt(div.ts);
			if (divTs > ts || (divTs === ts && div.id > orderDivId)) {
				return prevNode;
			}
			
			prevNode = div;
		}
		
		return null;
	};
    
    /**
     * Добавить в начало ленты перечисленные заказы
     * 
     * @param {Array} orders заказы, упорядоченные по возрастанию даты/ID заказчика/ID заказа
     */
	var prependOrdersList = function(orders) {
        for (var i in orders) {
            var order = orders[i];
            if (!orderExists(order['user_id'], order['order_id'])) {
                var orderDiv = createOrderElement(order);
                ordersList.insertBefore(
                        orderDiv, 
                        getNodeToInsertBefore(order.user_id, order.order_id, order.ts));
            }
        }
        
        if (orders.length > 0) {
            var topOrder = orders[orders.length - 1];
            ordersMaxTs  = topOrder.ts;
            ordersFirstUserId  = topOrder.user_id;
            ordersFirstOrderId  = topOrder.order_id;
        }
    }
    
    /**
     * Добавить в дайджест перечисленные заказы
     * 
     * @param {Array} orders заказы, упорядоченные по убыванию даты/ID заказчика/ID заказа
     */
	var appendOrdersList = function(orders) {
        for (var i in orders) {
            var order = orders[i];
            
            if (!firstLoadMade) {
                ordersMaxTs = order['ts'];
                ordersFirstOrderId = order['order_id'];
                ordersFirstUserId = order['user_id'];
                firstLoadMade = true;
            }
            
            if (!orderExists(order['user_id'], order['order_id'])) {
                ordersList.appendChild(createOrderElement(order));
                ordersMinTs = order['ts'];
                ordersLastOrderId = order['order_id'];
                ordersLastUserId = order['user_id'];
            }
        }
        
        if (orders.length >= 50) {
            Html.addClass(ordersListLoadMore, 'visible');
        } else {
            Html.removeClass(ordersListLoadMore, 'visible');
        }
    };
    
	/**
	 * Вызывает загрузку ленты с последующим добавлением её в конец списка
	 * 
	 * @param {Number} time ограничение по времени добавления заказа сверху
	 * @param {Number} lastUserId ID заказчика последнего заказа
	 * @param {Number} lastOrderId ID последнего заказа
	 */
	var loadFeed = function(time, lastUserId, lastOrderId) {
		ordersListLoading = true;
        Html.addClass(ordersListLoadMore, 'loading');
		
		var query = '';
		if (lastUserId > 0 && lastOrderId > 0) {
			query = '&last_user_id=' + lastUserId.toString() + '&last_order_id=' + lastOrderId.toString();
		} 
		
		ajaxJson(
				"GET", 
				"/feed_load.php?ts=" + time + query,
				null,
				function(json) {
                    Html.removeClass(ordersListLoadMore, 'loading');
                    var Response = new JsonResponse(json);
                    
                    if (Response.hasErrors()) {
                        Errors.showFromResponse(Response);
                    }
					
					var loginsMap = Response.getField('users', {});
					
                    UserStorage.addLogins(loginsMap);
					Broadcast.userLoginsLoaded(loginsMap);
					
                    appendOrdersList(Response.getField('orders', []));
                    
					if (Response.getField('orders_more')) {
						Html.removeClass(ordersListLoadMore, 'hidden');
					} else {
						Html.addClass(ordersListLoadMore, 'hidden');
					}
					
					ordersListLoading = false;
				},
				function(xhr) {
                    Html.removeClass(ordersListLoadMore, 'loading');
                    ordersListLoading = false;
				});
	};
	
	/**
	 * Вызывает загрузку новых заказов ленты с последующим добавлением их в начало списка
	 * 
	 * @param {Number} time ограничение по времени добавления заказа снизу
	 * @param {Number} firstUserId ID заказчика первого заказа
	 * @param {Number} firstOrderId ID первого заказа
	 */
    var loadNewFeed = function(time, firstUserId, firstOrderId) {
        ordersNewLoading = true;
        Html.addClass(ordersListLoadNew, 'loading');
        
		var query = '';
		if (firstUserId > 0 && firstOrderId > 0) {
			query = '&first_user_id=' + firstUserId.toString() + '&first_order_id=' + firstOrderId.toString();
		}
        
		ajaxJson(
				"GET", 
				"/feed_load_new.php?ts=" + time + query,
				null,
				function(json) {
                    Html.removeClass(ordersListLoadNew, 'loading');
                    var Response = new JsonResponse(json);
                    
                    if (Response.hasErrors()) {
                        Errors.showFromResponse(Response);
                    }
					
					var loginsMap = Response.getField('users', {});
					
                    UserStorage.addLogins(loginsMap);
					Broadcast.userLoginsLoaded(loginsMap);
					
                    prependOrdersList(Response.getField('orders', []));
                    
					if (Response.getField('orders_more')) {
						Html.removeClass(ordersListLoadNew, 'hidden');
						Html.addClass(ordersListNewCount, 'hidden');
					} else {
						Html.addClass(ordersListLoadMore, 'hidden');
					}
					
					ordersNewLoading = false;
				},
				function(xhr) {
                    Html.removeClass(ordersListLoadNew, 'loading');
                    ordersNewLoading = false;
				});
    };
    
	loadFeed(ordersMinTs, ordersLastUserId, ordersLastOrderId);
    
    ordersListLoadNew.onclick = function() {
        if (!ordersNewLoading) {
            loadNewFeed(ordersMaxTs, ordersFirstUserId, ordersFirstOrderId);
        }
    };
    
    ordersListLoadMore.onclick = function() {
        if (!ordersListLoading) {
            loadFeed(ordersMinTs, ordersLastUserId, ordersLastOrderId);
        }
    };
	
    /**
     * @type Number количество заказов которое следует игнорировать при проверке наличия новых заказов
     */
    var ignoredOrdersCount = 0;
    
	/**
	 * Добавление нового заказа в список
	 * 
	 * @param {Object} order
	 */
	var addNewOrderToList = function(order) {
        if (!orderExists(currentUser.id, order.order_id)) {
            ignoredOrdersCount++;
            
            var orderDiv = createOrderElement({
                user_id:	currentUser.id,
                order_id:	order.order_id,
                price:		order.price
            });

            ordersList.insertBefore(orderDiv, ordersList.firstChild);
            Html.addClass(orderDiv, 'order_new');
            setTimeout(function() {
                Html.removeClass(orderDiv, 'order_new');
            }, 2000);
        }
	}
	
    /**
     * Изменить видимость кнопки "Показать новые заказы"
     * 
     * @param {Number} newOrdersCount полученное с сервера количество новых заказов
     * @param {Number} ignoredCount отправленное на сервер количество игнорируемых заказов
     */
    var toggleNewOrdersLoadButton = function(newOrdersCount, ignoredCount) {
        var visibleNewCount = newOrdersCount - (ignoredOrdersCount - ignoredCount);
        if (visibleNewCount <= 0) {
            // скрыть кнопку
            Html.addClass(ordersListLoadNew, 'hidden');
        } else {
            // показать кнопку, показать и обновить счётчик
            Html.removeClass(ordersListLoadNew, 'hidden');
            Html.removeClass(ordersListNewCount, 'hidden');
            ordersListNewCount.innerHTML = visibleNewCount > 99 ? '99+' : visibleNewCount.toString();
        }
    };
    
    /**
     * Устанавливает таймер на проверку наличия новых заказов в ленте
     */
    var setUpNewOrdersCheck = function() {
        setTimeout(function() {
            if (!checkNewOrdersExists(ignoredOrdersCount)) {
                setUpNewOrdersCheck();
            }
            
        }, 5000);
    };
    
    /**
     * Вызывает проверку наличия новых заказов в ленте.
     * 
     * @param {Number} ignoredCount количество заказов, которые следует игнорировать.
     * 
     * @return {Boolean} был ли отправлен запрос на сервер
     */
    var checkNewOrdersExists = function(ignoredCount) {
        if (firstLoadMade) {
            
            ajaxJson(
                    "GET", 
                    "/feed_check.php?ts=" + ordersMaxTs.toString() + '&first_user_id=' + ordersFirstUserId.toString() + '&first_order_id=' + ordersFirstOrderId.toString() + '&ignored_count=' + ignoredCount.toString(), 
                    null,
                    function(json) {
                        var Response = new JsonResponse(json);
                        
                        if (Response.hasErrors()) {
                            Errors.showFromResponse(Response);
                        } else {
                            toggleNewOrdersLoadButton(Response.getField('orders_count', 0), ignoredCount);
                        }
                        
                        setUpNewOrdersCheck();
                    }, 
                    function(xhr) {
                        console.log(xhr);
                        setUpNewOrdersCheck();
                    });
            
            return true;
        }
        
        return false;
    }
    
	// вешаем обработку добавления заказа с текущей вкладки
	Layout.orderAddedListeners.push(addNewOrderToList);
	
	// вешаем обработку добавления заказа с другой вкладки
	Broadcast.orderAddedListener = function(orderId, price, ts) {
		addNewOrderToList({
			order_id:	orderId,
			price:		price,
			ts:			ts
		});
	};
	
	// вешаем обработку выполнения заказа в другой вкладке
	Broadcast.orderExecutedListener = function(userId, orderId, balanceDelta, finishTs) {
		var orderDiv = document.getElementById(getOrderDivId(userId, orderId));
		if (orderDiv !== null) {
			orderDiv.parentNode.removeChild(orderDiv);
		}
		Layout.updateBalance(parseFloat(balanceDelta));
	};
})();
