/** 
 * Страница заказов пользователя
 * 
 * @author mkoshkin
 */

(function() {
	/**
	 * @type Number ID последнего загруженного заказа
	 */
	var maxOrderId = 0;
	
	/**
	 * @type {Element} Список заказов на странице
	 */
	var ordersList = document.getElementById('orders-list');
    
	/**
	 * 
	 * @type {Element} Таблица заказов на странице
	 */
	var ordersTable = document.getElementById('orders-table');
	
	/**
	 * @type {Element}
	 */
	var ordersTableBody = ordersTable.getElementsByTagName('tbody')[0];
	
	/**
     * @type {Element} Кнопка "Загрузить еще" внизу списка заказов
     */
    var ordersListLoadMore = document.getElementById('orders-list__load-more');
	
    /**
     * @type {Boolean} выполняется ли в настоящий момент загрузка заказов
     */
	var ordersListLoading = false;
	
	/**
	 * Добавить массив заказов в таблицу
	 * 
	 * @param Object[] orders список заказов
	 */
	function addOrdersToTable(orders) {
		for (var i in orders) {
			var order = orders[i];
			var row = ordersTableBody.insertRow(ordersTableBody.rows.length);
            
			var idCell = row.insertCell(0);
            idCell.className = 'cell-id';
			idCell.appendChild(document.createTextNode( currentUser.id.toString() + '-' + order['order_id']) );
            
            var dateCell = row.insertCell(1);
            dateCell.className = 'c';
            dateCell.appendChild(document.createTextNode(
                    DateProc.shortDateTime(order.ts)
                    ));
            
            var priceCell = row.insertCell(2);
            priceCell.className = 'r';
            priceCell.appendChild(document.createTextNode(order.price));
            
            var statusCell = row.insertCell(3);
            
            if (order.finished_ts == 0) {
                Html.addClass(row, 'status-active');
                statusCell.appendChild(document.createTextNode('Не выполнен'));
            } else {
                Html.addClass(row, 'status-finished');
                statusCell.appendChild(document.createTextNode('Выполнен пользователем '));
                
                var spanUser = document.createElement('b');
                spanUser.innerHTML = UserStorage.getLogin(order.finished_user_id);
                if (spanUser.innerHTML === '') {
                    spanUser.innerHTML = '[' + order.finished_user_id + ']';
                }
                
                statusCell.appendChild(spanUser);
            }
            
            
            
            maxOrderId = order['order_id'];
		}
	}
	
	/**
	 * Загрузить заказы
	 */
	function loadOrders() {
		ordersListLoading = true;
		Html.addClass(ordersListLoadMore, 'loading');
		ajaxJson(
				"GET",
				"/my_orders_load.php?status=" + loadStatusId + "&max_order_id=" + maxOrderId,
				{},
				function(json) {
					var Response = new JsonResponse(json);
					if (Response.hasErrors()) {
						Error.showFromResponse(Response);
					}
                    
                    UserStorage.addLogins(Response.getField('users', {}));
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
	
	loadOrders();
	
	ordersListLoadMore.onclick = function() {
		if (!ordersListLoading) {
			loadOrders();
		}
	};
})();
