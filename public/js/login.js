window.onload = function() {
	var loginErrorMessage = document.getElementById('login-error-message');
	var loginForm = document.getElementById('login-form');
	
	loginForm.onsubmit = function(event) {
		loginErrorMessage.innerHTML = '';
		elementRemoveClass(loginErrorMessage, 'error-visible');
		
		event.preventDefault();
		
		ajaxJson(
				'POST', 
				'/login_action.php',
				{
					login: document.getElementById('login-form__login').value,
					password: document.getElementById('login-form__password').value
				},
				function(json) {
					if (json.hasOwnProperty('error')) {
						elementAddClass(loginErrorMessage, 'error-visible');
						for (var i in json['error']) {
							var div = document.createElement('div');
							div.innerHTML = code2error(json['error'][i]);
							loginErrorMessage.appendChild(div);
						}
					} else {
						window.location.href = '/feed.php';
					}
				}, 
				function(xhr) {
					alert('В ходе обработки запроса возникла ошибка');
					console.log(xhr);
				});
	}
}
