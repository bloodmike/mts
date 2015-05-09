/**
 * Страница с дайджестом заказов
 * 
 * @author mkoshkin
 */

(function() {
	
	var addOrderErrorMessage = document.getElementById('add-order-error-message');
	
	var priceInput = document.getElementById('add-order-form__price');
	
	/**
	 * @param {Event} event
	 */
	var addOrderFormListener = function(event) {
		event.preventDefault();
		var price = parseFloat(priceInput.value);
		if (!isNaN(price) && price >= 0.00) {
			Html.removeClass(addOrderErrorMessage, 'error-visible');
			Actions.createOrder(
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
							alert('Добавлены');
						}
					},
					function(xhr) {
						console.log(xhr);
					});
		} else {
			Html.addClass(priceInput, 'error-box');
		}
	};
	
	document.getElementById('add-order-form').onsubmit = addOrderFormListener;
	
	var ordersList = document.getElementById('orders-list');
    
	/**
     * @type {Element}
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
     * Добавить в дайджест перечисленные заказы
     * 
     * @param {Array} orders заказы
     */
	var appendOrdersList = function(orders) {
		
        for (var i in orders) {
            var order = orders[i];
            
            var div = document.createElement('div');
            div.className = 'order';
            div.id = 'order-' + order['user_id'] + '-' + order['order_id'];
            
            var divOwner = document.createElement('div');
            divOwner.className = 'order__owner';
            divOwner.title = 'Заказчик';
            
            var login = UserStorage.getLogin(order['user_id']);
            if (login === '') {
                login = 'неизвестный';
            }
            divOwner.innerHTML = login;
            div.appendChild(divOwner);
            
            var divPrice = document.createElement('div');
            divPrice.className = 'order__price';
            divPrice.title = 'Сумма заказа';
            divPrice.innerHTML = order['price'].toString() + '&#8381;';
            div.appendChild(divPrice);
            
            var divOrderId = document.createElement('div');
            divOrderId.className = 'order__id';
            divOrderId.title = 'ID заказа';
            divOrderId.innerHTML = order['user_id'] + '-' + order['order_id'];
            div.appendChild(divOrderId);
            
            if (!UserStorage.isCurrent(order['user_id'])) {
                var divExecute = document.createElement('div');
                divExecute.className = 'order__execute';
                divExecute.title = 'Выполнить заказ';
                divExecute.innerHTML = 'Выполнить';
                divExecute.onclick = function() {
                    Actions.executeOrder(
                        order['user_id'], 
                        order['order_id'],
                        function(json) {
                            var Response = new JsonResponse(json);
                            if (Response.hasErrors()) {
                                if (Response.hasError(4)) {
                                    div.parentNode.removeChild(div);
                                }
                                
                                Errors.showFromResponse(Response);
                            } else {
                                Html.addClass(div, 'order_finished');
                                Layout.updateBalance(Response.getField('balanceDelta'));
                            }
                        }
                    );
                };
                div.appendChild(divExecute);
            }
            
            ordersList.appendChild(div);
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
                    UserStorage.addLogins(Response.getField('users', {}));
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
})();
