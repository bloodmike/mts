/**
 * Страница выполненных текущим пользователем заказов
 * 
 * @author mkoshkin
 */

(function() {
    
    /**
     * @type Number unix-время последнего загруженного заказа
     */
    var maxOrderTs = 0;
    
	/**
	 * 
	 * @type {Element} Таблица заказов на странице
	 */
	var finishedOrdersTable = document.getElementById('finished-orders-table');
	
	/**
	 * @type {Element}
	 */
	var finishedOrdersTableBody = finishedOrdersTable.getElementsByTagName('tbody')[0];
	
	/**
     * @type {Element} Кнопка "Загрузить еще" внизу списка заказов
     */
    var ordersListLoadMore = document.getElementById('orders-list__load-more');
	
    /**
     * @type {Boolean} выполняется ли в настоящий момент загрузка заказов
     */
	var ordersListLoading = false;
    
    /**
     * Добавляет в конец таблицы выполненные заказы
     * 
     * @param {Array} orders выполненные заказы
     */
    function addOrdersToTable(orders) {
        for (var i in orders) {
            var order = orders[i];
            addOrderToTable(finishedOrdersTableBody.rows.length, order);
            maxOrderTs = order.finished_ts;
        }
    }
    
	/**
	 * Добавить заказ в начало таблицы
	 * 
	 * @param {Number} position
	 * @param {Object} order
	 * @param {Number} order.user_id
	 * @param {Number} order.order_id
	 * @param {Number} order.income
	 * @param {Number} order.finished_ts
	 */
	function addOrderToTable(position, order) {
		var row = finishedOrdersTableBody.insertRow(position);
            
		var dateCell = row.insertCell(0);
		dateCell.className = 'c';
		dateCell.appendChild(document.createTextNode(
				DateProc.shortDateTime(order.finished_ts)
			));

		var priceCell = row.insertCell(1);
		priceCell.className = 'r';
		priceCell.appendChild(document.createTextNode(order.income));

		var idCell = row.insertCell(2);
		idCell.className = 'cell-id';
		idCell.appendChild(document.createTextNode(order.user_id.toString() + '-' + order.order_id) );

		var ownerCell = row.insertCell(3);
		ownerCell.className = 'c';

		var login = UserStorage.getLogin(order.user_id);
		if (login === '') {
			login = '[' + order.user_id + ']';
		}

		ownerCell.appendChild(document.createTextNode(login));
	}
	
	/**
	 * Загрузить выполненные заказы
	 */
	function loadFinishedOrders() {
		ordersListLoading = true;
		Html.addClass(ordersListLoadMore, 'loading');
		ajaxJson(
				"GET",
				"/finished_orders_load.php?ts=" + maxOrderTs,
				{},
				function(json) {
					var Response = new JsonResponse(json);
					if (Response.hasErrors()) {
						Errors.showFromResponse(Response);
					}
                    
					var loginsMap = Response.getField('users', {});
                    UserStorage.addLogins(loginsMap);
					Broadcast.userLoginsLoaded(loginsMap);
					
                    addOrdersToTable(Response.getField('orders', []));

                    if (Response.getField('orders_more', false)) {
                        Html.removeClass(ordersListLoadMore, 'hidden');
                    } else {
                        Html.addClass(ordersListLoadMore, 'hidden');
                    }
                    
                    Html.removeClass(ordersListLoadMore, 'loading');
                    ordersListLoading = false;
				},
				function(xhr) {
                    Html.removeClass(ordersListLoadMore, 'loading');
                    ordersListLoading = false;
				});	
	}
	
	loadFinishedOrders();
	
	ordersListLoadMore.onclick = function() {
		if (!ordersListLoading) {
			loadFinishedOrders();
		}
	};
	
	// вешаем обработчик на выполнение заказа в другой вкладке
	Broadcast.orderExecutedListener = function(userId, orderId, balanceDelta, finishTs) {
		addOrderToTable(0, {
			user_id:		userId,
			order_id:		orderId,
			income:			balanceDelta,
			finished_ts:	finishTs
		});
		
		Layout.updateBalance(parseFloat(balanceDelta));
	};
})();
