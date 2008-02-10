/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/register.js
** | Begin :		26/11/2006
** | Last :			26/11/2006
** | User :			Genova
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Vérification de la validité d'un Email
*/
function ajax_check_email()
{
	var ajax = new Ajax();
	ajax.onload = function(data)
	{
		html = '';
		switch (data)
		{
			case 'invalid' :
				html = '<span class="ko">' + register_lang['email_invalid'] + '<\/span>';
			break;

			case 'used' :
				html = '<span class="ko">' + register_lang['email_used'] + '<\/span>';
			break;

			case 'valid' :
				html = '<span class="ok">' + register_lang['email_valid'] + '<\/span>';
			break;
		}
		document.getElementById('u_email_ajax_id').innerHTML = html;
	}
	ajax.set_arg(AJAX_POST, 'email', document.getElementById('u_email_id').value);
	ajax.set_arg(AJAX_GET, 'mode', 'check_email');
	ajax.send(FSB_ROOT + 'ajax.php', AJAX_MODE_TXT);
}

/*
** Vérification de la validité d'un login
*/
function ajax_check_login()
{
	var ajax = new Ajax();
	ajax.onload = function(data)
	{
		html = '';
		switch (data)
		{
			case 'used' :
				html = '<span class="ko">' + register_lang['login_used'] + '<\/span>';
			break;

			case 'valid' :
				html = '<span class="ok">' + register_lang['login_valid'] + '<\/span>';
			break;
		}
		document.getElementById('u_login_ajax_id').innerHTML = html;
	}
	ajax.set_arg(AJAX_POST, 'login', document.getElementById('u_login_id').value);
	ajax.set_arg(AJAX_GET, 'mode', 'check_login');
	ajax.send(FSB_ROOT + 'ajax.php', AJAX_MODE_TXT);
}

/*
** Vérification de la robustesse d'un mot de passe
*/
function ajax_check_password()
{
	var ajax = new Ajax();
	ajax.onload = function(data)
	{
		html = '';
		switch (data)
		{
			case 'weak' :
				html = '<span class="ko">' + register_lang['password_weak'] + '<\/span>';
			break;

			case 'normal' :
				html = '<span class="ko">' + register_lang['password_normal'] + '<\/span>';
			break;

			case 'strong' :
				html = '<span class="ok">' + register_lang['password_strong'] + '<\/span>';
			break;
		}
		document.getElementById('u_password_ajax_id').innerHTML = html;
	}
	ajax.set_arg(AJAX_POST, 'password', document.getElementById('u_password_id').value);
	ajax.set_arg(AJAX_GET, 'mode', 'check_password');
	ajax.send(FSB_ROOT + 'ajax.php', AJAX_MODE_TXT);
}

/*
** Vérification de la validité d'un pseudonyme
*/
function ajax_check_nickname()
{
	var ajax = new Ajax();
	ajax.onload = function(data)
	{
		html = '';
		switch (data)
		{
			case 'middle' :
				html = '<span class="ko">' + register_lang['nickname_middle'] + '<\/span>';
			break;

			case 'high' :
				html = '<span class="ko">' + register_lang['nickname_high'] + '<\/span>';
			break;

			case 'short' :
				html = '<span class="ko">' + register_lang['nickname_short'] + '<\/span>';
			break;

			case 'long' :
				html = '<span class="ko">' + register_lang['nickname_long'] + '<\/span>';
			break;

			case 'used' :
				html = '<span class="ko">' + register_lang['nickname_used'] + '<\/span>';
			break;

			case 'valid' :
				html = '<span class="ok">' + register_lang['nickname_valid'] + '<\/span>';
			break;
		}
		document.getElementById('u_nickname_ajax_id').innerHTML = html;
	}
	ajax.set_arg(AJAX_POST, 'nickname', document.getElementById('u_nickname_id').value);
	ajax.set_arg(AJAX_GET, 'mode', 'check_nickname');
	ajax.send(FSB_ROOT + 'ajax.php', AJAX_MODE_TXT);
}