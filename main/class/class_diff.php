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
 * Permet de comparer deux chaines de caracteres lignes par ligne, afin de trouver les differences
 */
class Diff extends Fsb_model
{
	/**
	 * Contenu de la premiere chaine
	 *
	 * @var string
	 */
	private $data1;
	
	/**
	 * Contenu de la seconde chaine
	 *
	 * @var string
	 */
	private $data2;

	/**
	 * Longueur de la premiere chaine
	 *
	 * @var int
	 */
	private $count1;
	
	/**
	 * Longueur de la seconde chaine
	 *
	 * @var int
	 */
	private $count2;

	/**
	 * Informations sur les lignes du DIFF
	 *
	 * @var array
	 */
	private $stack = array();

	/**
	 * Liste des differences entre les deux chaines
	 *
	 * @var array
	 */
	public $entries = array();

	/**
	 * Aucune difference entre les deux chaine
	 */
	const EQUAL = '=';
	
	/**
	 * Ajout de code
	 */
	const ADD = '+';
	
	/**
	 * Suppression de code
	 */
	const DROP = '-';
	
	/**
	 * Remplacement de code
	 */
	const CHANGE = '~';

	/**
	 * Charge en memoire les deux chaines et lance la creation du diff
	 *
	 * @param string $str1 Premiere chaine
	 * @param string $str2 Seconde chaine
	 */
	public function load_content($str1, $str2)
	{
		$this->data1 = preg_split('#(\r\n|\n|\r)#', $str1);
		$this->count1 = count($this->data1);
		$this->data2 = preg_split('#(\r\n|\n|\r)#', $str2);
		$this->count2 = count($this->data2);

		$this->parse();
	}

	/**
	 * Charge en memoire le contenu de deux fichiers et lance la creation du diff.
	 * Cette methode permet la mise en cache des donnees (chose conseille puisque le calcul du DIFF est assez lourd)
	 *
	 * @param string $src Premier fichier
	 * @param string $dst Second fichier
	 * @param bool $use_cache Utilisation du cache
	 */
	public function load_file($src, $dst, $use_cache = false)
	{
		// Date de derniere modification des fichiers, utile pour savoir si on compte faire une remise en cache
		$filemtime1 = filemtime($src);
		$filemtime2 = filemtime($dst);

		// Mise en cache
		$hash = md5($src . $dst);
		$cache = Cache::factory('diff');
		if ($cache->exists($hash))
		{
			$cache_get = $cache->get($hash);
			$this->entries = $cache_get['output'];

			$cache_put = false;
			if ($use_cache && ($filemtime1 != $cache_get['filemtime1'] || $filemtime2 != $cache_get['filemtime2']))
			{
				$this->entries = array();
				$cache_put = true;
			}
		}
		else
		{
			$cache_put = true;
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

	/**
	 * Generation du DIFF
	 */
	private function parse()
	{
		// Creation d'une matrice schematisant les differences des fichiers
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

	/**
	 * Compresse une ligne de tableau pour eviter d'allouer trop de memoires dans la matrice
	 *
	 * @param array $row Ligne de matrice
	 * @return string Ligne de matrice compressee
	 */
	private function memory_put($row)
	{
		return (implode('-', $row));
	}

	/**
	 * Decompresse une ligne compressee de la matrice
	 *
	 * @param string $row Ligne de matrice
	 * @return array Ligne de matrice decompressee
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

	/**
	 * Ajoute une entree si la ligne reste inchangee
	 *
	 * @param string $code Code inchange
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

	/**
	 * Ajoute une entree en cas de modifications entre les deux codes
	 *
	 * @param string $src Code original
	 * @param string $dst Code modifie
	 */
	private function unmatch(&$src, &$dst)
	{
		$s1 = count($src);
		$s2 = count($dst);

		// Ligne supprimee
		if ($s1 > 0 && $s2 == 0)
		{
			array_unshift($this->entries, array(
				'file1' =>	implode("\n", $src),
				'file2' =>	'',
				'state' =>	Diff::DROP,
			));
		}
		// Ligne ajoutee
		else if ($s2 > 0 && $s1 == 0)
		{
			array_unshift($this->entries, array(
				'file1' =>	'',
				'file2' =>	implode("\n", $dst),
				'state' =>	Diff::ADD,
			));
		}
		// Ligne modifiee
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

	/**
	 * Affiche le resultat du diff en dur
	 *
	 * @param bool $wrap Si true, revient a la ligne en cas de ligne trop longue
	 */
	public function output($wrap = true)
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

	/**
	 * Formate l'affichage du diff
	 *
	 * @param string $str
	 * @param bool $wrap Retour a la ligne a la fin du block
	 * @return string
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