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
			
			var dateCell = row.insertCell(0);
			dateCell.appendChild(document.createTextNode( currentUser.id.toString() + '-' + order['order_id']) );
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
					} else {
						addOrdersToTable(Response.getField('orders', []));
					}
					
					UserStorage.addLogins(Response.getField('users', {}));
					
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
