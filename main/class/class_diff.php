<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_diff.php
** | Begin :	07/11/2006
** | Last :		06/08/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Permet de comparer deux chaines de caractères lignes par ligne, afin de trouver les différences.
*/
class Diff extends Fsb_model
{
	// Contenu des deux chaines
	private $data1, $data2;

	// Longueur des deux chaines
	private $count1, $count2;

	// Lignes stoquées avec leurs informations
	private $stack = array();

	// Liste des différences entre les deux fichiers
	public $entries = array();

	const EQUAL = '=';
	const ADD = '+';
	const DROP = '-';
	const CHANGE = '~';

	/*
	** Charge à partir de chaines de caractères
	** -----
	** $str1 ::		Code à gauche
	** $str2 ::		Code à droite
	*/
	public function load_content($str1, $str2)
	{
		$this->data1 = preg_split('#(\r\n|\n|\r)#', $str1);
		$this->count1 = count($this->data1);
		$this->data2 = preg_split('#(\r\n|\n|\r)#', $str2);
		$this->count2 = count($this->data2);

		$this->parse();
	}

	/*
	** Charge à partir de noms de fichiers.
	** Pour cette méthode un cache implémenté, il est fortement recommandé de l'utiliser, les gains de performances
	** sont assez énormes sur les gros fichiers.
	** -----
	** $src ::			Fichier à gauche
	** $dst ::			Fichier à droite
	** $use_cache ::	Si on met les fichiers en cache
	*/
	public function load_file($src, $dst, $use_cache = FALSE)
	{
		// Date de dernière modification des fichiers, utile pour savoir si on compte faire une remise en cache
		$filemtime1 = filemtime($src);
		$filemtime2 = filemtime($dst);

		// Mise en cache
		$hash = md5($src . $dst);
		$cache = Cache::factory('diff');
		if ($cache->exists($hash))
		{
			$cache_get = $cache->get($hash);
			$this->entries = $cache_get['output'];

			$cache_put = FALSE;
			if ($use_cache && ($filemtime1 != $cache_get['filemtime1'] || $filemtime2 != $cache_get['filemtime2']))
			{
				$this->entries = array();
				$cache_put = TRUE;
			}
		}
		else
		{
			$cache_put = TRUE;
		}

		// Mise en cache
		if ($cache_put)
		{
			$this->load_content(file_get_contents($src), file_get_contents($dst));
			if ($use_cache)
			{
				$cache->put($hash, array(
					'output' =>		$this->entries,
					'filemtime1' =>	$filemtime1,
					'filemtime2' =>	$filemtime2,
				), '');
			}
		}
	}

	/*
	** Parse les deux textes, afin de récupérer les différences
	*/
	public function parse()
	{
		// Création d'une matrice schématisant les différences des fichiers
 		$matrix = array();
		$last_row = array();
		for ($i = -1; $i < $this->count2; $i++)
		{
			$last_row[$i] = 0;
		}

		for ($i = 0; $i < $this->count1; $i++)
		{
			$row = array('-1' => 0);
			$data_old_value = $this->data1[$i];

			for ($j = 0; $j < $this->count2; $j++)
			{
				if ($data_old_value == $this->data2[$j])
				{
					$row[$j] = $last_row[$j - 1] + 1;
				}
				else if ($row[$j - 1] > $last_row[$j])
				{
					$row[$j] = $row[$j - 1];
				}
				else
				{
					$row[$j] = $last_row[$j];
				}
			}

			$matrix[$i - 1] = $this->memory_put($last_row);
			$last_row = $row;
		}
		$matrix[$this->count1 - 1] = $this->memory_put($row);

		$match = $unmatch1 = $unmatch2 = array();
		$iterator1 = $this->count1 - 1;
		$iterator2 = $this->count2 - 1;
		$row = $this->memory_get($matrix[$iterator1]);
		$next_row = $this->memory_get($matrix[$iterator1 - 1]);
		while ($iterator1 >= 0 && $iterator2 >= 0)
		{
			if ($row[$iterator2] != $next_row[$iterator2 - 1] && $this->data1[$iterator1] == $this->data2[$iterator2])
			{
				$this->unmatch($unmatch1, $unmatch2);
				array_unshift($match, $this->data1[$iterator1]);

				$iterator1--;
				$iterator2--;
				$row = $next_row;
				$next_row = $this->memory_get(@$matrix[$iterator1 - 1]);
			}
			else if ($next_row[$iterator2] > $row[$iterator2 - 1])
			{
				$this->match($match);
				array_unshift($unmatch1, $this->data1[$iterator1]);

				$iterator1--;
				$row = $next_row;
				$next_row = $this->memory_get($matrix[$iterator1 - 1]);
			}
			else
			{
				$this->match($match);
				array_unshift($unmatch2, $this->data2[$iterator2]);

				$iterator2--;
			}
		}

		$this->match($match);
		if ($iterator1 > -1 || $iterator2 > -1)
		{
			while ($iterator1 > -1)
			{
				array_unshift($unmatch1, $this->data1[$iterator1]);
				$iterator1--;
			}

			while ($iterator2 > -1)
			{
				array_unshift($unmatch2, $this->data2[$iterator2]);
				$iterator2--;
			}
			$this->unmatch($unmatch1, $unmatch2);
		}
	}

	/*
	** Compresse une ligne de tableau pour éviter d'allouer trop de mémoires dans la matrice
	** -----
	** $row ::		Ligne de matrice
	*/
	private function memory_put($row)
	{
		return (implode('-', $row));
	}

	/*
	** Décompresse une ligne compressée de la matrice
	** -----
	** $row ::		Ligne de matrice
	*/
	private function memory_get($row)
	{
		$result = array();
		$i = -1;
		foreach (explode('-', $row) AS $value)
		{
			$result[$i] = $value;
			$i++;
		}
		return ($result);
	}

	/*
	** Ajoute une entrée si la ligne reste inchangée
	** -----
	** $code ::		Code inchangé
	*/
	private function match(&$code)
	{
		if (count($code) > 0)
		{
			$data = implode("\n", $code);
			array_unshift($this->entries, array(
				'file1' =>	$data,
				'file2' =>	$data,
				'state' =>	Diff::EQUAL,
			));
		}

		$code = array();
	}

	/*
	** Ajoute une entrée en cas de modifications entre les deux codes
	** -----
	** $src ::		Code du fichier original
	** $dst ::		Code du second fichier
	*/
	private function unmatch(&$src, &$dst)
	{
		$s1 = count($src);
		$s2 = count($dst);

		// Ligne supprimée
		if ($s1 > 0 && $s2 == 0)
		{
			array_unshift($this->entries, array(
				'file1' =>	implode("\n", $src),
				'file2' =>	'',
				'state' =>	Diff::DROP,
			));
		}
		// Ligne ajoutée
		else if ($s2 > 0 && $s1 == 0)
		{
			array_unshift($this->entries, array(
				'file1' =>	'',
				'file2' =>	implode("\n", $dst),
				'state' =>	Diff::ADD,
			));
		}
		// Ligne modifiée
		else if ($s1 > 0 && $s2 > 0)
		{
			array_unshift($this->entries, array(
				'file1' =>	implode("\n", $src),
				'file2' =>	implode("\n", $dst),
				'state' =>	Diff::CHANGE,
			));
		}

		$src = $dst = array();
	}

	/*
	** Affiche le résultat du diff en dur
	** -----
	** $wrap ::		Wrap automatique
	*/
	public function output($wrap = TRUE)
	{
		$style_equal = 'width: 50%; background-color: #F3F3F3;';
		$style_drop = 'width: 50%; background-color: #CFF5B8;';
		$style_add = 'width: 50%; background-color: #F5CCB8;';
		$style_change = 'width: 50%; background-color: #FCF3B5;';

		echo '<table style="width: 100%; font-size: 12px; font-family: Courier, \'Courier New\', sans-serif">';
		foreach ($this->entries AS $data)
		{
			if (!$data['file1'] && !$data['file2'])
			{
				continue;
			}
			$file1 = $this->format($data['file1'], $wrap);
			$file2 = $this->format($data['file2'], $wrap);

			echo '<tr>';
			switch ($data['state'])
			{
				case Diff::EQUAL :
					echo '<td style="' . $style_equal . '">' . $file1 . '</td><td style="' . $style_equal . '">' . $file2 . '</td>';
				break;

				case Diff::CHANGE :
					echo '<td style="' . $style_equal . '">' . $file1 . '</td><td style="' . $style_change . '">' . $file2 . '</td>';
				break;

				case Diff::DROP :
					echo '<td style="' . $style_drop . '">' . $file1 . '</td><td style="' . $style_equal . '">' . $file2 . '</td>';
				break;

				case Diff::ADD :
					echo '<td style="' . $style_equal . '">' . $file1 . '</td><td style="' . $style_add . '">' . $file2 . '</td>';
				break;
			}
			echo '</tr>';
		}
		echo '</table>';
	}

	/*
	** Formate l'affichage du diff
	** -----
	** $str ::		Chaine de caractère
	** $wrap ::		Retour à la ligne à la fin du block
	*/
	public function format($str, $wrap)
	{
		if ($wrap)
		{
			$str = nl2br(htmlspecialchars($str));
			$str = preg_replace('#( ){2}#', '&nbsp; ', $str);
			$str = str_replace("\t", '&nbsp; &nbsp; ', $str);
			return ('<code>' . $str . '</code>');
		}
		else
		{
			return ('<pre>' . htmlspecialchars($str) . '</pre>');
		}
	}
}

/* EOF */