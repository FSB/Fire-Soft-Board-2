<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

die('Pour pouvoir utiliser ce fichier veuillez commenter cette ligne. <b>Ce fichier est une faille potentielle de sécurité</b>, ne l\'utilisez qu\'en local, ou si vous êtes certain de ce que vous faites.');

/**
 * Ce fichier converti une exportation MySQL en exportation PostGreSQL ou bien SQLite
 */

$gpc = array('_GET', '_POST', '_COOKIE');
$magic_quote = (get_magic_quotes_gpc()) ? true : false;
$register_globals = (ini_get('register_globals')) ? true : false;

if ($register_globals || $magic_quote)
{
	foreach ($gpc AS $value)
	{
		if ($register_globals)
		{
			foreach ($$value AS $k => $v)
			{
				unset($$k);
			}
		}

		if ($magic_quote)
		{
			$$value = array_map('stripslashes', $$value);
		}
	}
}

function is_escaped($pos, $str)
{
	if (($pos - 1) >= 0 && $str[$pos - 1] != '\\')
	{
		return (false);
	}
	else if (($pos - 1) >= 0 && ($pos - 2) >= 0 && $str[$pos - 1] == '\\' && $str[$pos - 2] != '\\')
	{
		return (true);
	}
	else if (($pos - 1) >= 0 && ($pos - 2) >= 0 && $str[$pos - 1] == '\\' && $str[$pos - 2] == '\\')
	{
		return (is_escaped($pos - 2, $str));
	}
	return (false);
}

function explode_query($delimiter, $str)
{
	if (!is_array($delimiter))
	{
		$delimiter = array($delimiter);
	}

	$len = strlen($str);
	for ($i = 0, $in_delimiter = true, $quote_is_begin = false, $tab = array(), $tmp = ''; $i < $len; $i++)
	{
		if (!in_array($str[$i], $delimiter) || ($quote_is_begin && in_array($str[$i], $delimiter)))
		{
			if ($str[$i] == "'" && !is_escaped($i, $str) && !$quote_is_begin)
			{
				$quote_is_begin = true;
			}
			else if ($str[$i] == "'" && !is_escaped($i, $str) && $quote_is_begin)
			{
				$quote_is_begin = false;
			}
			$tmp .= $str[$i];
			$in_delimiter = false;
		}
		else if (in_array($str[$i], $delimiter) && !$in_delimiter && !$quote_is_begin)
		{
			$in_delimiter = true;
			$tmp = trim($tmp);
			if (strlen($tmp) > 0)
			{
				$tab[] = (($tmp[0] == '\'' && $tmp[strlen($tmp) - 1] == '\'') ? substr($tmp, 1, -1) : $tmp);
			}
			$tmp = '';
		}
	}

	$tmp = trim($tmp);
	if (strlen($tmp) > 0)
	{
		$tab[] = (($tmp[0] == '\'' && $tmp[strlen($tmp) - 1] == '\'') ? substr($tmp, 1, -1) : $tmp);
	}
	return (array_map('stripslashes', $tab));
}

if (isset($_POST['submit']))
{
	$content = $_POST['content'];
	$type = $_POST['type'];
	$content = str_replace('`', '', $content);
	$new_query = '';
	$insert = $_POST['insert'];
	if ($insert)
	{
		$new_query = str_replace(array("\\r\\n", "\\n", "#"), array("\r\n", "\n", "\\#"), $content);
	}
	else
	{
		$ary = explode_query(';', $content);
		$setval = '';
		foreach ($ary AS $table)
		{
			$table = preg_replace("#(--|\#) ?.*?\n#i", '', $table);
			preg_match('#CREATE TABLE ([a-zA-Z0-9_]*?) \(#i', $table, $match);
			$table = preg_replace('# (Type|ENGINE)=.*?$#i', '', $table);
			$table = preg_replace('#,(\r)?\n  FULLTEXT KEY\s*.*?\n#i', "\n", $table);
			$table = preg_replace('#DEFAULT CHARSET=latin1#i', '', $table);
			$tablename = $match[1];

			switch ($type)
			{
				case 'pgsql' :
					// AUTO_INCREMENT ?
					if (preg_match("#\n  ([a-zA-Z0-9_]*?) (int\(11\)|mediumint\(9\)|smallint\(5\)) NOT null auto_increment,#i", $table, $match))
					{
						$new_query .= "CREATE SEQUENCE ${tablename}_seq;\n";
						$field = $match[1];
						$table = preg_replace("#\n  ([a-zA-Z0-9_]*?) (int\(11\)|mediumint\(9\)|smallint\(5\)) NOT null auto_increment,#i", "\n\\1 INT DEFAULT nextval('${tablename}_seq'),", $table);
						$table = preg_replace('# not null#i', '', $table);
						$setval .= "SELECT SETVAL('${tablename}_seq',(select case when max(${field})>0 then max(${field})+1 else 1 end from ${tablename}));\n";
					}

					// KEYS ?
					preg_match_all('#\n  KEY ([a-zA-Z0-9_]*?) \(([a-zA-Z0-9_, ]*?)\)(,)?#i', $table, $matches);
					$index = '';
					for ($i = 0; $i < count($matches[0]); $i++)
					{
						$table = preg_replace('#,(\r)?\n  KEY ([a-zA-Z0-9_]*?) \(([a-zA-Z0-9_, ]*?)\)(,)?#i', '\\4', $table);
						$index .= "CREATE INDEX ${tablename}_" . $matches[1][$i] . "_index ON ${tablename} (" . $matches[2][$i] . ");\n";
					}

					// UNIQUE ?
					preg_match_all('#\n  UNIQUE ([a-zA-Z0-9_]*?) \(([a-zA-Z0-9_, ]*?)\)(,)?#i', $table, $matches);
					for ($i = 0; $i < count($matches[0]); $i++)
					{
						$table = preg_replace('#,(\r)?\n  UNIQUE ([a-zA-Z0-9_]*?) \(([a-zA-Z0-9_, ]*?)\)(,)?#i', '\\4', $table);
						$index .= "CREATE UNIQUE INDEX ${tablename}_" . $matches[1][$i] . "_index ON ${tablename} (" . $matches[2][$i] . ");\n";
					}

					// AUTRES
					$table = preg_replace('#PRIMARY KEY ([a-zA-Z0-9_]*?) \(([a-zA-Z0-9_, ]*?)\)#i', 'PRIMARY KEY (\\2)', $table);
					$table = preg_replace('#tinyint\([0-9]+\)#i', 'INT2', $table);
					$table = preg_replace('# tinyint #i', ' INT2 ', $table);
					$table = preg_replace('#smallint\([0-9]+\)#i', 'INT4', $table);
					$table = preg_replace('#mediumint\([0-9]+\)#i', 'INT4', $table);
					$table = preg_replace('#int\([0-9]+\)#i', 'INT4', $table);
					$table = preg_replace('#longtext#i', 'text
					', $table);
					$new_query .= $table . ";\n" . $index;
				break;

				case 'sqlite' :
					if (preg_match('#DROP TABLE IF EXISTS #i', $table))
					{
						continue ;
					}

					// AUTO_INCREMENT ?
					$auto_field = null;
					$index = '';
					if (preg_match("#\n  ([a-zA-Z0-9_]*?) int\(11\) NOT null auto_increment,#i", $table, $match))
					{
						$field = $match[1];
						$table = preg_replace("#\n  ([a-zA-Z0-9_]*?) int\(11\) NOT null auto_increment,#i", "\n  \\1 INTEGER PRIMARY KEY NOT null,", $table);
						$table = preg_replace('#,(\r)?\n  PRIMARY KEY\s*([a-zA-Z0-9_]*?)?\s*\(.*?\)#i', '', $table);
					}
					$table = preg_replace('#,(\r)?\n  UNIQUE\s*([a-zA-Z0-9_]*?)\s*\(.*?\)#i', '', $table);
					$table = preg_replace("#INTEGER PRIMARY KEY NOT null#i", "{SQLITE_SAVE}", $table);
					$table = preg_replace("# NOT null#i", "", $table);
					$table = preg_replace("#\{SQLITE_SAVE\}#i", "INTEGER PRIMARY KEY NOT null", $table);


					// KEYS ?
					preg_match_all('#\n  (PRIMARY )?KEY ([a-zA-Z0-9_]*?) \(([a-zA-Z0-9_, ]*?)\)(,)?#i', $table, $matches);
					$index = '';
					for ($i = 0; $i < count($matches[0]); $i++)
					{
						$table = preg_replace('#,(\r)?\n  (PRIMARY )?KEY ([a-zA-Z0-9_]*?) \(([a-zA-Z0-9_, ]*?)\)(,)?#i', '\\5', $table);
						$split = explode(',', $matches[3][$i]);
						foreach ($split AS $key)
						{
							$key = trim($key);
							$index .= "CREATE INDEX ${tablename}_${key}_index ON ${tablename} (${key});\n";
						}
					}
					$new_query .= $table . ";\n" . $index;
				break;
			}
		}

		if (isset($setval))
		{
			$new_query .= "\n$setval";
		}
	}
	echo '<pre>' . htmlspecialchars($new_query) . '</pre>';
}
else
{
?>
<html>
<head>
</head>
<body>
<form action="" method="post">
	<select name="type">
		<option value="pgsql">PostGreSQL</option>
		<option value="sqlite">SQLite</option>
	</select>
	<input type="checkbox" name="insert" value="1" /> INSERT
	<br />
	<textarea rows="20" cols="100" name="content"></textarea>
	<br />
	<input type="submit" name="submit" value="Convertir" />
</form>
</body>
</html>
<?php
}

?>
