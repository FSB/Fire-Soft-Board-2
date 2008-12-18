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
 * Permet une gestion d'abres par representation intervallaire.
 * 
 * Si vous souhaitez implementer une table gerant la representation intervallaire, vous devez disposer
 * des champs suivants :
 *	(int) f_id ::			ID de la feuille
 *	(int) f_cat_id ::		ID du parent le plus haut (categorie)
 *	(int) f_level ::		Niveau actuel dans l'arbre
 *	(int) f_parent ::		ID du parent
 *	(int) f_left ::			Borne gauche
 *	(int) f_right			Borne droite
 * 
 * @link http://sqlpro.developpez.com/cours/arborescence/
 */
class Sql_interval extends Fsb_model
{
	/**
	 * Interval de deplacement des feuilles temporaires
	 */
	const MOVE = 1000000;

	/**
	 * Ajoute un element dans l'arbre
	 *
	 * @param int $parent Element parent
	 * @param array $data Informations a inserer dans la table
	 * @param string $table Table concernee
	 * @return int ID de l'element cree
	 */
	public static function put($parent, $data, $table = 'forums')
	{		
		// On recupere la bordure droite du parent
		$sql = 'SELECT f_cat_id, f_right, f_level
			FROM ' . SQL_PREFIX . $table . '
			WHERE f_id = ' . $parent;
		$result = Fsb::$db->query($sql);
		$parent_data = Fsb::$db->row($result);
		Fsb::$db->free($result);
		
		// Si aucun parent n'a ete trouve on place la feuille a droite
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
		
		// On decale les bordures
		Fsb::$db->update($table, array(
			'f_left' =>	array('f_left + 2', 'is_field' => true),
		), 'WHERE f_left >= ' . $parent_data['f_right']);
		
		Fsb::$db->update($table, array(
			'f_right' =>	array('f_right + 2', 'is_field' => true),
		), 'WHERE f_right >= ' . $parent_data['f_right']);
		
		// Insertion du nouvel element
		Fsb::$db->insert($table, array_merge($data, array(
			'f_parent' =>	$parent,
			'f_left' =>		$parent_data['f_right'],
			'f_right' =>	$parent_data['f_right'] + 1,
			'f_level' =>	$parent_data['f_level'] + 1,
			'f_cat_id' =>	$parent_data['f_cat_id'],
		)));
		$last_id = Fsb::$db->last_id();
		
		// S'il $parent vaut 0, on met a jour son ID de categorie
		if ($parent_data['f_cat_id'] == 0)
		{
			Fsb::$db->update($table, array(
				'f_cat_id' =>	$last_id,
			), 'WHERE f_id = ' . $last_id);
		}
		
		return ($last_id);
	}

	/**
	 * Met a jour un element de l'interval
	 *
	 * @param int $f_id ID de la feuille courante
	 * @param int $parent Parent de la feuille
	 * @param array $data Donnees de la feuille
	 * @param string $table Table concernee
	 * @return bool True si le parent a ete modifie
	 */
	public static function update($f_id, $parent, $data, $table = 'forums')
	{
		// On demare une transaction SQL
		Fsb::$db->transaction('begin');
		
		// Donnees du forum actuel
		$sql = 'SELECT f_cat_id, f_parent, f_left, f_right, f_level
				FROM ' . SQL_PREFIX . $table . '
				WHERE f_id = ' . $f_id;
		$current = Fsb::$db->request($sql);

		if ($parent != $current['f_parent'])
		{
			// On change le forum actuel d'ID, c'est parti pour tout redecaler comme il faut ..
			// On commence par recuperer les donnees du nouveau parent
			$sql = 'SELECT f_left, f_right, f_cat_id, f_level
					FROM ' . SQL_PREFIX . $table . '
					WHERE f_id = ' . $parent;
			$parent_data = Fsb::$db->request($sql);
			
			// L'interval est l'ecart entre les deux bornes des forums deplaces
			$interval = $current['f_right'] - $current['f_left'] + 1;

			// On decale les bornes de la feuille actuelle de Sql_interval::MOVE, afin de le placer en zone
			// temporaire et de ne pas entrer en conflit.
			Fsb::$db->update($table, array(
				'f_left' =>	array('f_left + ' . Sql_interval::MOVE, 'is_field' => true),
				'f_right' =>	array('f_right + ' . Sql_interval::MOVE, 'is_field' => true),
			), 'WHERE f_left >= ' . $current['f_left'] . ' AND f_right <= ' . $current['f_right']);

			// Si on deplace le noeud vers la gauche ..
			if ($current['f_left'] > $parent_data['f_right'])
			{
				// On decale les feuilles situees entre la borne droite du parent - 1, et
				// la borne gauche de la feuille deplacee.
				Fsb::$db->update($table, array(
					'f_left' =>	array('f_left + ' . $interval, 'is_field' => true),
				), 'WHERE f_left > ' . ($parent_data['f_right'] - 1) . ' AND f_left < ' . $current['f_left']);
				
				Fsb::$db->update($table, array(
					'f_right' =>	array('f_right + ' . $interval, 'is_field' => true),
				), 'WHERE f_right > ' . ($parent_data['f_right'] - 1) . ' AND f_right < ' . $current['f_left']);
				$new_interval = Sql_interval::MOVE + (($current['f_left'] - $parent_data['f_right']));
			}
			// .. sinon deplacement vers la droite.
			else
			{
				// On decale les feuilles situees entre la borne droite du parent - 1, et
				// la borne gauche de la feuille deplacee.
				Fsb::$db->update($table, array(
					'f_left' =>	array('f_left - ' . $interval, 'is_field' => true),
				), 'WHERE f_left > ' . $current['f_right'] . ' AND f_left < ' . $parent_data['f_right']);
				
				Fsb::$db->update($table, array(
					'f_right' =>	array('f_right - ' . $interval, 'is_field' => true),
				), 'WHERE f_right > ' . $current['f_right'] . ' AND f_right < ' . $parent_data['f_right']);
				$new_interval = Sql_interval::MOVE - (($parent_data['f_right'] - 1 - $current['f_right']));
			}

			// On modifie l'interval et les donnees des feuilles deplacees
			Fsb::$db->update($table, array(
				'f_left' =>	array('f_left - ' . $new_interval, 'is_field' => true),
				'f_right' =>	array('f_right - ' . $new_interval, 'is_field' => true),
				'f_level' =>	array('f_level - ' . ($current['f_level'] - $parent_data['f_level'] - 1), 'is_field' => true),
				'f_cat_id' =>	$parent_data['f_cat_id'],
			), 'WHERE f_left > ' . Sql_interval::MOVE);
			$data['f_parent'] = $parent;
		}

		// Mise a jour de la feuille
		Fsb::$db->update($table, $data, 'WHERE f_id = ' . $f_id);
		
		// On termine la transaction
		Fsb::$db->transaction('commit');

		return (($parent != $current['f_parent']) ? true : false);
	}

	/**
	 * Supprime une feuille de l'arbre
	 *
	 * @param int $f_id ID de la feuille
	 * @param string $table Forum concerne
	 */
	public static function delete($f_id, $table = 'forums')
	{
		// Donnees de la feuille
		$sql = 'SELECT f_left, f_right
				FROM ' . SQL_PREFIX . $table . '
				WHERE f_id = ' . $f_id;
		if ($current = Fsb::$db->request($sql))
		{
			// Suppression de la feuille
			$sql = 'DELETE FROM ' . SQL_PREFIX . $table . '
					WHERE f_left >= ' . $current['f_left'] . ' AND f_right <= ' . $current['f_right'];
			Fsb::$db->query($sql);
			
			// On redecale les bornes correctement
			Fsb::$db->update($table, array(
				'f_left' =>	array('f_left - ' . ($current['f_right'] - $current['f_left'] + 1), 'is_field' => true),
			), 'WHERE f_left >= ' . $current['f_left']);
			
			Fsb::$db->update($table, array(
				'f_right' =>	array('f_right - ' . ($current['f_right'] - $current['f_left'] + 1), 'is_field' => true),
			), 'WHERE f_right >= ' . $current['f_right']);
		}
	}
	
	/**
	 * Deplace une feuille
	 *
	 * @param int $f_id ID de la feuille
	 * @param string $direction Direction du deplacement (left, right)
	 * @param string $table Table concernee
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
		
		// Donnees de la feuille actuelle
		$sql = 'SELECT f_left, f_right
				FROM ' . SQL_PREFIX . $table . '
				WHERE f_id = ' . $f_id;
		$current = Fsb::$db->request($sql);

		if ($current)
		{
			// Donnees de la feuille avec laquelle on va faire un echange
			$sql = 'SELECT f_left, f_right
					FROM ' . SQL_PREFIX . $table . '
					WHERE ' . $swap_side . ' = ' . ($current[$current_side] - (-1 * $current_sign));
			$swap = Fsb::$db->request($sql);

			if ($swap)
			{
				// On decale la feuille actuelle en zone temporaire
				Fsb::$db->update($table, array(
					'f_left' =>		array('f_left + ' . Sql_interval::MOVE, 'is_field' => true),
					'f_right' =>	array('f_right + ' . Sql_interval::MOVE, 'is_field' => true),
				), 'WHERE f_left >= ' . $current['f_left'] . ' AND f_right <= ' . $current['f_right']);
				
				// On decale la feuille d'echange
				Fsb::$db->update($table, array(
					'f_left' =>		array('f_left ' . $swap_operator . ' ' . ($current['f_right'] - $current['f_left'] + 1), 'is_field' => true),
					'f_right' =>	array('f_right ' . $swap_operator . ' ' . ($current['f_right'] - $current['f_left'] + 1), 'is_field' => true),
				), 'WHERE f_left >= ' . $swap['f_left'] . ' AND f_right <= ' . $swap['f_right']);
				
				// On replace la feuille courante dans sa nouvelle position
				Fsb::$db->update($table, array(
					'f_left' =>		array('f_left - ' . (Sql_interval::MOVE - (($swap['f_right'] - $swap['f_left'] + 1) * $current_sign)), 'is_field' => true),
					'f_right' =>	array('f_right - ' . (Sql_interval::MOVE - (($swap['f_right'] - $swap['f_left'] + 1) * $current_sign)), 'is_field' => true),
				), 'WHERE f_left > ' . Sql_interval::MOVE);
			}
		}
	}

	/**
	 * Retourne la liste des enfants
	 *
	 * @param int $f_id ID de la feuille
	 * @param bool $include Definit si on inclu le forum actuel dans le resultat
	 * @param string $cache Mise en cache ?
	 * @param string $table Table concernee
	 * @return array
	 */
	public static function get_childs($f_id, $include = true, $cache = null, $table = 'forums')
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

	/**
	 * Retourne la liste des parents
	 *
	 * @param int $f_id ID de la feuille
	 * @param bool $include Definit si on inclu le forum actuel dans le resultat
	 * @param string $cache Mise en cache ?
	 * @param string $table Table concernee
	 * @return array
	 */
	public static function get_parents($f_id, $include = true, $cache = null, $table = 'forums')
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

	/**
	 * Affiche une representation de l'arbre pour le debug
	 *
	 * @param string $field Champ contenant le nom de la feuille actuelle
	 * @param string $table Table concernee
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