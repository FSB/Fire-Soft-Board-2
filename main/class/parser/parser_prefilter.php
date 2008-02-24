<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/parser/parser_prefilter.php
** | Begin :	10/10/2007
** | Last :		10/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

class Parser_prefilter extends Fsb_model
{
	/*
	** Transformation des URL locales en mettant le nom du sujet / forum
	** -----
	** $str ::		Chaine de caractere
	*/
	public static function filter_forum_url($str)
	{
		$str = preg_replace_callback('/(?<=^|[\s])((((http:\/\/|https:\/\/|ftp:\/\/|ftps:\/\/|www\.)([^ \"\t\n\r<]{3,}))))/i', array('self', '_filter_forum_url'), $str);
		return ($str);
	}

	public function _filter_forum_url($m)
	{
		$path = preg_quote(Fsb::$cfg->get('fsb_path'), '#');
		if (preg_match('#^' . $path . '#i', $m[0]))
		{
			if (preg_match('#^' . $path . '/index\.' . PHPEXT . '\?p=(topic)&t_id=([0-9]+)#i', $m[0], $sub) || preg_match('#^' . $path . '/(topic|sujet)-([0-9]+)-([0-9]+)\.#i', $m[0], $sub))
			{
				$sql = 'SELECT t_title
						FROM ' . SQL_PREFIX . 'topics
						WHERE t_id = ' . intval($sub[2]);
				if ($t_title = Fsb::$db->get($sql, 't_title'))
				{
					return ('[url=' . $m[0] . ']' . $t_title . '[/url]');
				}
			}
			else if (preg_match('#^' . $path . '/index\.' . PHPEXT . '\?p=topic&p_id=([0-9]+)#i', $m[0], $sub))
			{
				$sql = 'SELECT t.t_title
						FROM ' . SQL_PREFIX . 'posts p
						INNER JOIN ' . SQL_PREFIX . 'topics t
							ON t.t_id = p.t_id
						WHERE p.p_id = ' . intval($sub[1]);
				if ($t_title = Fsb::$db->get($sql, 't_title'))
				{
					return ('[url=' . $m[0] . ']' . $t_title . '[/url]');
				}
			}
			else if (preg_match('#^' . $path . '/index\.' . PHPEXT . '\?p=(userprofile)&id=([0-9]+)#i', $m[0], $sub) || preg_match('#^' . $path . '/(profile|membre)-([0-9]+)\.#i', $m[0], $sub))
			{
				$sql = 'SELECT u_nickname
						FROM ' . SQL_PREFIX . 'users
						WHERE u_id = ' . intval($sub[2]);
				if ($u_nickname = Fsb::$db->get($sql, 'u_nickname'))
				{
					return ('[url=' . $m[0] . ']' . $u_nickname . '[/url]');
				}
			}
			else if (preg_match('#^' . $path . '/index\.' . PHPEXT . '\?p=forum&f_id=([0-9]+)#i', $m[0], $sub) || preg_match('#^' . $path . '/forum-([0-9]+)-([0-9]+)\.#i', $m[0], $sub))
			{
				$sql = 'SELECT f_name
						FROM ' . SQL_PREFIX . 'forums
						WHERE f_id = ' . intval($sub[1]);
				if ($f_name = Fsb::$db->get($sql, 'f_name'))
				{
					return ('[url=' . $m[0] . ']' . $f_name . '[/url]');
				}
			}
		}
		return ($m[0]);
	}
}

/* EOF */