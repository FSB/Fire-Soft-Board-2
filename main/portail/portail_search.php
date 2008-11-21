<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

/**
 * Module de portail permettant de faire des recherches rapides
 */
class Page_portail_search extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function main()
	{
		if (Http::request('pm_submit_search', 'post'))
		{
			$this->submit_search();
		}

		// Liste des moteurs de recherche disponibles pour ce module
		$list_search_motor = Html::make_list('search_motor', 'pm_search_on_forum', array(
			'forum' =>		Fsb::$session->lang('pm_search_on_forum'),
			'google' =>		'Google',
			'yahoo' =>		'Yahoo',
			'wiki' =>		'Wikipedia',
		));

		Fsb::$tpl->set_vars(array(
			'LIST_SEARCH_MOTOR' =>		$list_search_motor,

			'U_PM_SEARCH_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=portail'),
		));
	}

	/**
	 * Soumet la recherche en redirigeant vers la bonne page
	 */
	public function submit_search()
	{
		// On recupere si possible la langue pour le moteur
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$exp = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			if (isset($exp[0]))
			{
				$lang = $exp[0];
			}
			else
			{
				$lang = 'fr';
			}
		}
		else
		{
			$lang = 'fr';
		}

		// On redirige vers le bon moteur de recherche
		$search_motor =		Http::request('search_motor', 'post');
		$search_word =		urlencode(Http::request('search_word', 'post'));
		switch ($search_motor)
		{
			case 'google' :
				Http::redirect('http://www.google.com/search?hl=' . $lang . '&q=' . $search_word);
			break;

			case 'wiki' :
				Http::redirect('http://' . $lang . '.wikipedia.org/wiki/' . $search_word);
			break;

			case 'yahoo' :
				Http::redirect('http://' . $lang . '.search.yahoo.com/search?p=' . $search_word);
			break;

			case 'forum' :
			default :
				Http::redirect(ROOT . 'index.' . PHPEXT . '?p=search&mode=result&keywords=' . $search_word . '&in=post&print=topic&submit=true');
			break;
		}
	}
}

/* EOF */