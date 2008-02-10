<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_sql_interval.php
** | Begin :	05/10/2006
** | Last :		05/11/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Permet une gestion d'abres par représentation intervallaire. Cette représentation se base sur le tutorial
** de SQLpro, disponible à cette adresse : http://sqlpro.developpez.com/cours/arborescence/.
**
** Si vous souhaitez implémenter une table gérant la représentation intervallaire, vous devez disposer
** des champs suivants :
**	(int) f_id ::			ID de la feuille
**	(int) f_cat_id ::		ID du parent le plus haut (catégorie)
**	(int) f_level ::		Niveau actuel dans l'arbre
**	(int) f_parent ::		ID du parent
**	(int) f_left ::			Borne gauche
**	(int) f_right			Borne droite
*/
class Sql_interval extends Fsb_model
{
	// Interval de déplacement des feuilles temporaires
	const MOVE = 1000000;

	/*
	** Ajoute un élément dans l'arbre
	** -----
	** $parent ::		Element parent
	** $data ::		Données
	*/
	public static function put($parent, $data, $table = 'forums')
	{		
		// On récupère la bordure droite du parent
		$sql = 'SELECT f_cat_id, f_right, f_level
			FROM ' . SQL_PREFIX . $table . '
			WHERE f_id = ' . $parent;
		$result = Fsb::$db->query($sql);
		$parent_data = Fsb::$db->row($result);
		Fsb::$db->free($result);
		
		// Si aucun parent n'a été trouvé on place la feuille a droite
		if (!$parent_data)
		{
			$sql = 'SELECT MAX(f_right) AS max
					FROM ' . SQL_PREFIX . $table;
			$max = Fsb::$db->get($sql, 'max');
			$parent_data = array(
				'f_right' =>	$max + 1,
				'f_parent' =>	0,
				'f_level' =>	-1,
				'f_cat_id' =>	0,
			);
		}
		
		// On décale les bordures
		Fsb::$db->update($table, array(
			'f_left' =>	array('f_left + 2', 'is_field' => TRUE),
		), 'WHERE f_left >= ' . $parent_data['f_right']);
		
		Fsb::$db->update($table, array(
			'f_right' =>	array('f_right + 2', 'is_field' => TRUE),
		), 'WHERE f_right >= ' . $parent_data['f_right']);
		
		// Insertion du nouvel élément
		Fsb::$db->insert($table, array_merge($data, array(
			'f_parent' =>	$parent,
			'f_left' =>		$parent_data['f_right'],
			'f_right' =>	$parent_data['f_right'] + 1,
			'f_level' =>	$parent_data['f_level'] + 1,
			'f_cat_id' =>	$parent_data['f_cat_id'],
		)));
		$last_id = Fsb::$db->last_id();
		
		// S'il $parent vaut 0, on met à jour son ID de catégorie
		if ($parent_data['f_cat_id'] == 0)
		{
			Fsb::$db->update($table, array(
				'f_cat_id' =>	$last_id,
			), 'WHERE f_id = ' . $last_id);
		}
		
		return ($last_id);
	}
	
	/*
	** Met à jour un élément de l'interval
	** -----
	** $f_id ::	ID de la feuille courante
	** $parent ::	Parent de la feuille
	** $data ::	Données de la feuille
	*/
	public static function update($f_id, $parent, $data, $table = 'forums')
	{
		// On démare une transaction SQL
		Fsb::$db->transaction('begin');
		
		// Données du forum actuel
		$sql = 'SELECT f_cat_id, f_parent, f_left, f_right, f_level
				FROM ' . SQL_PREFIX . $table . '
				WHERE f_id = ' . $f_id;
		$current = Fsb::$db->request($sql);

		if ($parent != $current['f_parent'])
		{
			// On change le forum actuel d'ID, c'est parti pour tout redécaler comme il faut ..
			// On commence par récupérer les données du nouveau parent
			$sql = 'SELECT f_left, f_right, f_cat_id, f_level
					FROM ' . SQL_PREFIX . $table . '
					WHERE f_id = ' . $parent;
			$parent_data = Fsb::$db->request($sql);
			
			// L'interval est l'écart entre les deux bornes des forums déplacés
			$interval = $current['f_right'] - $current['f_left'] + 1;

			// On décale les bornes de la feuille actuelle de Sql_interval::MOVE, afin de le placer en zone
			// temporaire et de ne pas entrer en conflit.
			Fsb::$db->update($table, array(
				'f_left' =>	array('f_left + ' . Sql_interval::MOVE, 'is_field' => TRUE),
				'f_right' =>	array('f_right + ' . Sql_interval::MOVE, 'is_field' => TRUE),
			), 'WHERE f_left >= ' . $current['f_left'] . ' AND f_right <= ' . $current['f_right']);

			// Si on déplace le noeud vers la gauche ..
			if ($current['f_left'] > $parent_data['f_right'])
			{
				// On décale les feuilles situées entre la borne droite du parent - 1, et
				// la borne gauche de la feuille déplacée.
				Fsb::$db->update($table, array(
					'f_left' =>	array('f_left + ' . $interval, 'is_field' => TRUE),
				), 'WHERE f_left > ' . ($parent_data['f_right'] - 1) . ' AND f_left < ' . $current['f_left']);
				
				Fsb::$db->update($table, array(
					'f_right' =>	array('f_right + ' . $interval, 'is_field' => TRUE),
				), 'WHERE f_right > ' . ($parent_data['f_right'] - 1) . ' AND f_right < ' . $current['f_left']);
				$new_interval = Sql_interval::MOVE + (($current['f_left'] - $parent_data['f_right']));
			}
			// .. sinon déplacement vers la droite.
			else
			{
				// On décale les feuilles situées entre la borne droite du parent - 1, et
				// la borne gauche de la feuille déplacée.
				Fsb::$db->update($table, array(
					'f_left' =>	array('f_left - ' . $interval, 'is_field' => TRUE),
				), 'WHERE f_left > ' . $current['f_right'] . ' AND f_left < ' . $parent_data['f_right']);
				
				Fsb::$db->update($table, array(
					'f_right' =>	array('f_right - ' . $interval, 'is_field' => TRUE),
				), 'WHERE f_right > ' . $current['f_right'] . ' AND f_right < ' . $parent_data['f_right']);
				$new_interval = Sql_interval::MOVE - (($parent_data['f_right'] - 1 - $current['f_right']));
			}

			// On modifie l'interval et les données des feuilles déplacées
			Fsb::$db->update($table, array(
				'f_left' =>	array('f_left - ' . $new_interval, 'is_field' => TRUE),
				'f_right' =>	array('f_right - ' . $new_interval, 'is_field' => TRUE),
				'f_level' =>	array('f_level - ' . ($current['f_level'] - $parent_data['f_level'] - 1), 'is_field' => TRUE),
				'f_cat_id' =>	$parent_data['f_cat_id'],
			), 'WHERE f_left > ' . Sql_interval::MOVE);
			$data['f_parent'] = $parent;
		}

		// Mise à jour de la feuille
		Fsb::$db->update($table, $data, 'WHERE f_id = ' . $f_id);
		
		// On termine la transaction
		Fsb::$db->transaction('commit');

		return (($parent != $current['f_parent']) ? TRUE : FALSE);
	}
	
	/*
	** Supprime une feuille de l'arbre
	** -----
	** $f_id ::	ID de la feuille
	*/
	public static function delete($f_id, $table = 'forums')
	{
		// Données de la feuille
		$sql = 'SELECT f_left, f_right
				FROM ' . SQL_PREFIX . $table . '
				WHERE f_id = ' . $f_id;
		if ($current = Fsb::$db->request($sql))
		{
			// Suppression de la feuille
			$sql = 'DELETE FROM ' . SQL_PREFIX . $table . '
					WHERE f_left >= ' . $current['f_left'] . ' AND f_right <= ' . $current['f_right'];
			Fsb::$db->query($sql);
			
			// On redécale les bornes correctement
			Fsb::$db->update($table, array(
				'f_left' =>	array('f_left - ' . ($current['f_right'] - $current['f_left'] + 1), 'is_field' => TRUE),
			), 'WHERE f_left >= ' . $current['f_left']);
			
			Fsb::$db->update($table, array(
				'f_right' =>	array('f_right - ' . ($current['f_right'] - $current['f_left'] + 1), 'is_field' => TRUE),
			), 'WHERE f_right >= ' . $current['f_right']);
		}
	}
	
	/*
	** Déplace une feuille
	** -----
	** $f_id ::		ID de la feuille
	** $direction ::	Direction du déplacement (left, right)
	*/
	public static function move($f_id, $direction, $table = 'forums')
	{
		if ($direction == 'left')
		{
			$current_side = 'f_left';
			$swap_side = 'f_right';
			$swap_operator = '+';
			$current_sign = -1;
		}
		else
		{
			$current_side = 'f_right';
			$swap_side = 'f_left';
			$swap_operator = '-';
			$current_sign = 1;
		}
		
		// Données de la feuille actuelle
		$sql = 'SELECT f_left, f_right
				FROM ' . SQL_PREFIX . $table . '
				WHERE f_id = ' . $f_id;
		$current = Fsb::$db->request($sql);

		if ($current)
		{
			// Données de la feuille avec laquelle on va faire un échange
			$sql = 'SELECT f_left, f_right
					FROM ' . SQL_PREFIX . $table . '
					WHERE ' . $swap_side . ' = ' . ($current[$current_side] - (-1 * $current_sign));
			$swap = Fsb::$db->request($sql);

			if ($swap)
			{
				// On décale la feuille actuelle en zone temporaire
				Fsb::$db->update($table, array(
					'f_left' =>		array('f_left + ' . Sql_interval::MOVE, 'is_field' => TRUE),
					'f_right' =>	array('f_right + ' . Sql_interval::MOVE, 'is_field' => TRUE),
				), 'WHERE f_left >= ' . $current['f_left'] . ' AND f_right <= ' . $current['f_right']);
				
				// On décale la feuille d'échange
				Fsb::$db->update($table, array(
					'f_left' =>		array('f_left ' . $swap_operator . ' ' . ($current['f_right'] - $current['f_left'] + 1), 'is_field' => TRUE),
					'f_right' =>	array('f_right ' . $swap_operator . ' ' . ($current['f_right'] - $current['f_left'] + 1), 'is_field' => TRUE),
				), 'WHERE f_left >= ' . $swap['f_left'] . ' AND f_right <= ' . $swap['f_right']);
				
				// On replace la feuille courante dans sa nouvelle position
				Fsb::$db->update($table, array(
					'f_left' =>		array('f_left - ' . (Sql_interval::MOVE - (($swap['f_right'] - $swap['f_left'] + 1) * $current_sign)), 'is_field' => TRUE),
					'f_right' =>	array('f_right - ' . (Sql_interval::MOVE - (($swap['f_right'] - $swap['f_left'] + 1) * $current_sign)), 'is_field' => TRUE),
				), 'WHERE f_left > ' . Sql_interval::MOVE);
			}
		}
	}
	
	/*
	** Retourne la liste des enfants
	** -----
	** $f_id ::		ID de la feuille
	** $include ::	Définit si on inclu le forum actuel dans le résultat
	** $cache ::	Mise en cache ?
	*/
	public static function get_childs($f_id, $include = TRUE, $cache = NULL, $table = 'forums')
	{
		if (!is_array($f_id))
		{
			$f_id = array($f_id);
		}

		$childs = array();
		if ($include)
		{
			$childs = $f_id;
		}

		$sql = 'SELECT child.f_id
				FROM ' . SQL_PREFIX . $table . ' f
				INNER JOIN ' . SQL_PREFIX . $table . ' child
					ON f.f_left < child.f_left
						AND f.f_right > child.f_right
				WHERE f.f_id IN (' . implode(', ', $f_id) . ')';
		$result = Fsb::$db->query($sql, $cache);
		while ($row = Fsb::$db->row($result))
		{
			$childs[] = $row['f_id'];
		}
		Fsb::$db->free($result);

		return (array_unique($childs));
	}
	
	/*
	** Retourne la liste des parents
	** -----
	** $f_id ::		ID de la feuille
	** $include ::	Définit si on inclu le forum actuel dans le résultat
	** $cache ::	Mise en cache ?
	*/
	public static function get_parents($f_id, $include = TRUE, $cache = NULL, $table = 'forums')
	{
		if (!is_array($f_id))
		{
			$f_id = array($f_id);
		}
		
		$parents = array();
		if ($include)
		{
			$parents = $f_id;
		}

		$sql = 'SELECT child.f_id
				FROM ' . SQL_PREFIX . $table . ' f
				INNER JOIN ' . SQL_PREFIX . $table . ' child
					ON f.f_left > child.f_left
						AND f.f_right < child.f_right
				WHERE f.f_id IN (' . implode(', ', $f_id) . ')';
		$result = Fsb::$db->query($sql, $cache);
		while ($row = Fsb::$db->row($result))
		{
			$parents[] = $row['f_id'];
		}
		Fsb::$db->free($result);
		
		return (array_unique($parents));
	}
	
	/*
	** Affiche une représentation de l'arbre
	** -----
	** $field ::	Champ contenant le nom de la feuille actuelle
	*/
	public static function debug($field = 'f_name', $table = 'forums')
	{		
		echo '<pre>';
		$sql = 'SELECT f_id, f_level, f_left, f_right, ' . $field . '
				FROM ' . SQL_PREFIX . $table . '
				ORDER BY f_left';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			echo $row['f_id'] . ' -' . str_repeat("\t", $row['f_level']) . ' [' . $row['f_left'] . '] ' . $row[$field] . ' [' . $row['f_right'] . "]\n";
		}
		Fsb::$db->free($result);
		echo '</pre>';
	}
}

/* EOF */