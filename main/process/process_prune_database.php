<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_database.php
** | Begin :	11/07/2007
** | Last :		10/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Optimize la base de donnée
** -----
** $tables ::		Liste des tables à optimiser
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