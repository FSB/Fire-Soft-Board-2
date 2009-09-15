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
 * Optimize la base de donnee
 *
 * @param array $tables Liste des tables à optimiser
 */
function prune_database($tables = array())
{
	switch (SQL_DBAL)
	{
		case 'mysql' :
		case 'mysqli' :
			if (!$tables)
			{
				foreach (Fsb::$db->list_tables() AS $table)
				{
					$tables[] = '`' . $table . '`';
				}
			}
			
			Fsb::$db->query('OPTIMIZE TABLE ' . implode(', ', $tables));
			Fsb::$db->query('ANALYZE TABLE ' . implode(', ', $tables));
			Fsb::$db->query('REPAIR TABLE ' . implode(', ', $tables));
		break;

		case 'pgsql' :
			if (!$tables)
			{
				$tables = Fsb::$db->list_tables();
			}

			foreach ($tables AS $table)
			{
				Fsb::$db->query('VACUUM ANALYZE ' . $table);
			}
		break;
	}
}
/* EOF */