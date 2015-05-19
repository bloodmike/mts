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
			if (divTs > ts || (divTs == ts && div.id > orderDivId)) {
				return prevNode;
			}
			
			prevNode = ordersList.childNodes[i];
		}
		
		return null;
	};
	
    /**
     * Добавить в дайджест перечисленные заказы
     * 
     * @param {Array} orders заказы
     */
	var appendOrdersList = function(orders) {
        for (var i in orders) {
            var order = orders[i];
            ordersList.appendChild(createOrderElement(order));
            ordersMinTs = order['ts'];
			ordersLastOrderId = order['order_id'];
			ordersLastUserId = order['user_id'];
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
                    var Response = new JsonResponse(json);
                    Html.removeClass(ordersListLoadMore, 'loading');
                    
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
	
	loadFeed(ordersMinTs, ordersLastUserId, ordersLastOrderId);
    
    ordersListLoadMore.onclick = function() {
        if (!ordersListLoading) {
            loadFeed(ordersMinTs, ordersLastUserId, ordersLastOrderId);
        }
    };
	
	/**
	 * Добавление нового заказа в список
	 * 
	 * @param {Object} order
	 */
	var addNewOrderToList = function(order) {
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
