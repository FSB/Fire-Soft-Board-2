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
** Vérification de la validite d'un Email
*/
function ajax_check_email()
{
    var ajax = new Request(
    {
        url: FSB_ROOT + 'ajax.' + FSB_PHPEXT + '?mode=check_email',
        onSuccess: function(txt, xml)
        {
			html = '';
			switch (txt)
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
			$('u_email_ajax_id').innerHTML = html;
        }
    });
    
    ajax.send({
        mode: 'post',
        data: 'email=' + $('u_email_id').value
    });
}

/*
** Vérification de la validité d'un login
*/
function ajax_check_login()
{
    var ajax = new Request(
    {
        url: FSB_ROOT + 'ajax.' + FSB_PHPEXT + '?mode=check_login',
        onSuccess: function(txt, xml)
        {
			html = '';
			switch (txt)
            {
				case 'used' :
					html = '<span class="ko">' + register_lang['login_used'] + '<\/span>';
				break;
	
				case 'valid' :
					html = '<span class="ok">' + register_lang['login_valid'] + '<\/span>';
				break;
			}
			$('u_login_ajax_id').innerHTML = html;
        }
    });
    
    ajax.send({
        mode: 'post',
        data: 'login=' + $('u_login_id').value
    });
}

/*
** Vérification de la robustesse d'un mot de passe
*/
function ajax_check_password()
{
    var ajax = new Request(
    {
        url: FSB_ROOT + 'ajax.' + FSB_PHPEXT + '?mode=check_password',
        onSuccess: function(txt, xml)
        {
			html = '';
			switch (txt)
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
			$('u_password_ajax_id').innerHTML = html;
        }
    });
    
    ajax.send({
        mode: 'post',
        data: 'password=' + $('u_password_id').value
    });  
}

/*
** Vérification de la validité d'un pseudonyme
*/
function ajax_check_nickname()
{
    var ajax = new Request(
    {
        url: FSB_ROOT + 'ajax.' + FSB_PHPEXT + '?mode=check_nickname',
        onSuccess: function(txt, xml)
        {
			html = '';
			switch (txt)
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
			$('u_nickname_ajax_id').innerHTML = html;
        }
    });
    
    ajax.send({
        mode: 'post',
        data: 'nickname=' + $('u_nickname_id').value
    });    
}
