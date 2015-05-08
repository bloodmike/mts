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
                    var Response = new JsonResponse(json);
					if (Response.hasErrors()) {
						elementAddClass(loginErrorMessage, 'error-visible');
                        var errors = Response.getErrors();
						for (var i in errors) {
							var div = document.createElement('div');
							div.innerHTML = Errors.code2error(errors[i]);
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
