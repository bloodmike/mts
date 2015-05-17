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
            
            var row = finishedOrdersTableBody.insertRow(finishedOrdersTableBody.rows.length);
            
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
            
            maxOrderTs = order.finished_ts;
        }
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
	
	loadFinishedOrders();
	
	ordersListLoadMore.onclick = function() {
		if (!ordersListLoading) {
			loadFinishedOrders();
		}
	};
})();
