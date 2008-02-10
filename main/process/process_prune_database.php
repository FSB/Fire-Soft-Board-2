<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_database.php
** | Begin :	11/07/2007
** | Last :		18/10/2007
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
				$result = Fsb::$db->list_tables();
				$tables = array();
				while ($row = Fsb::$db->row($result, 'row'))
				{
					$tables[] = '`' . $row[0] . '`';
				}
			}
			
			Fsb::$db->query('OPTIMIZE TABLE ' . implode(', ', $tables));
			Fsb::$db->query('ANALYZE TABLE ' . implode(', ', $tables));
			Fsb::$db->query('REPAIR TABLE ' . implode(', ', $tables));
		break;

		case 'pgsql' :
			if ($tables)
			{
				foreach ($tables AS $table)
				{
					Fsb::$db->query('VACUUM ANALYZE ' . $table);
				}
			}
			else
			{
				$result = Fsb::$db->list_tables();
				while ($row = Fsb::$db->row($result, 'row'))
				{
					Fsb::$db->query('VACUUM ANALYZE ' . $row[0]);
				}
			}
		break;
	}
}
/* EOF */