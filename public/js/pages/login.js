window.onload = function() {
	var loginErrorMessage = document.getElementById('login-error-message');
	var loginForm = document.getElementById('login-form');
	var loginProcessing = false;
	
	var disableProcessing = function() {
		loginProcessing = false;
		Html.removeClass(loginForm, 'processing');
	}
	
	loginForm.onsubmit = function(event) {
		loginErrorMessage.innerHTML = '';
		Html.removeClass(loginErrorMessage, 'error-visible');
		
		event.preventDefault();
		if (loginProcessing) {
			return;
		}
		
		Html.addClass(loginForm, 'processing');
		loginProcessing = true;
		ajaxJson(
				'POST', 
				'/login_action.php',
				{
					login: document.getElementById('login-form__login').value,
					password: document.getElementById('login-form__password').value
				},
				function(json) {
					disableProcessing();
                    var Response = new JsonResponse(json);
					if (Response.hasErrors()) {
						Html.addClass(loginErrorMessage, 'error-visible');
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
					disableProcessing();
					alert('В ходе обработки запроса возникла ошибка');
					console.log(xhr);
				});
	}
}
