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
			elementRemoveClass(addOrderErrorMessage, 'error-visible');
			createOrder(
					price, 
					function(json) {
						if (json.hasOwnProperty('error')) {
							addOrderErrorMessage.innerHTML = '';
							elementAddClass(addOrderErrorMessage, 'error-visible');
							for (var i in json['error']) {
								var div = document.createElement('div');
								div.innerHTML = code2error(json['error'][i]);
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
			elementAddClass(priceInput, 'error-box');
		}
	};
	
	document.getElementById('add-order-form').onsubmit = addOrderFormListener;
	
	var ordersList = document.getElementById('orders-list');
	
	
	/**
	 * Вызывает загрузку ленты с последующим добавлением её в конец списка
	 * 
	 * @param {number} time
	 */
	var loadFeed = function(time) {
		
		ajaxJson(
				"GET", 
				"/feed_load.php?ts=" + time,
				null,
				function(json) {
					console.log(json);
				},
				function(xhr) {
					alert('Ошибка обработки запроса');
				});
	};
	
	loadFeed(Math.ceil((new Date()).getTime() / 1000));
})();
