<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/captcha/captcha_png.php
** | Begin :	16/05/2007
** | Last :		17/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Extension de la classe Captcha, generant directement des fichiers PNG basiques sans GD
*/
class Captcha_png extends Captcha
{
	// Liste des methodes
	protected $methods = array(
		'a' =>		'method_classic',
	);

	// Espace entre chaque caractere
	protected $space = 34;

	// Objet PNG
	private $png;

	// Liste des caracteres
	private $char_list = array();

	/*
	** Constructeur
	*/
	public function __construct()
	{
		$this->font_path = ROOT . 'main/class/captcha/fonts/';
		$this->png = new Png($this->width, $this->height, TRUE);
	}

	/*
	** Creation de l'image
	*/
	protected function open_image()
	{
	}

	/*
	** Affiche l'image
	*/
	protected function close_image()
	{
		$this->png->close();
	}

	/*
	** Ecrit un caractere sur l'image
	*/
	protected function write_char($size, $angle, $x, $y, $fontcolor, $font, $char)
	{
		if ($fontcolor === NULL)
		{
			list($red, $green, $blue) = $this->generate_color();
		}
		else
		{
			list($red, $green, $blue) = $fontcolor;
		}
		$this->png->write($char, $x, $y - 20, $this->font_path . 'chars.txt', $size, Png_color::rgb($red, $green, $blue), TRUE, 5);
	}

	/*
	** Ajouts de bruits sur l'image (lignes, points aleatoires)
	*/
	protected function add_noise()
	{
		$noise_color = Png_color::hexa(0xBBBBBB);
		$total_pixel = rand($this->total_pixel_min, $this->total_pixel_max);
		for ($i = 1; $i < $total_pixel; $i++)
		{
			$this->png->set_pixel(rand(0, $this->width - 1), rand(0, $this->height - 1), $noise_color);
		}

		$total_line = rand($this->total_line_min, $this->total_line_max);
		for ($i = 1; $i <= $total_line; $i++)
		{
			$this->png->set_line(rand(0, $this->width - 1), rand(0, $this->height - 1), rand(0, $this->width - 1), rand(0, $this->height - 1), array(
				'border-color' =>	$noise_color,
				'border-width' =>	1
			));
		}

		// Effet de blur (trop lent)
		// $this->png->filter_blur();
	}
}

define('MYPNG_FILTER_NONE', 0);
define('MYPNG_FILTER_HORIZONTAL', 1);
define('MYPNG_FILTER_VERTICAL', 2);
define('MYPNG_FILTER_AVG', 3);
define('MYPNG_FILTER_PAETH', 4);

class Png extends Image
{
	// Largeur du PNG
	protected $width = 0;

	// Hauteur du PNG
	protected $height = 0;

	// Image a vraie couleurs ?
	protected $truecolor = FALSE;

	// Contenu du PNG
	protected $content = '';

	// Contenu de l'image
	protected $image = '';

	// Filtre
	protected $filter = 0;

	/*
	** Constructeur d'une image PNG
	** -----
	** $width ::		Largeur de l'image
	** $height ::		Hauteur de l'image
	** $truecolor ::	Mode vraies couleurs (si FALSE, on passe en niveau de gris)
	*/
	public function __construct($width, $height, $truecolor = TRUE)
	{
		// Parametres
		$this->truecolor = $truecolor;
		$this->width = $width;
		$this->height = $height;
		$this->filter = MYPNG_FILTER_NONE;

		// Initialisation de l'image
		$this->init();
	}

	/*
	** Initialise tous les pixels de l'image, ainsi que les filtres
	*/
	private function init()
	{
		$repeat = $this->width;
		if ($this->truecolor)
		{
			$repeat *= 3;
		}

		$this->image = '';
		for ($i = 0; $i < $this->height; $i++)
		{
			$this->image .= chr(0) . str_repeat(chr(255), $repeat);
		}
	}

	/*
	** Calcul de l'index d'un pixel
	** -----
	** $x ::	Coordonnee $x du pixel
	** $y ::	Coordonnee $y du pixel
	*/
	public function find_index($x, $y)
	{
		if (!$this->truecolor)
		{
			return (($y * $this->width) + $x + $y);
		}
		else
		{
			return (($y * $this->width * 3) + (3 * $x) + $y + 1);
		}
	}

	/*
	** Ajoute un pixel sur l'image
	** -----
	** $x ::		Coordonnee $x du pixel
	** $y ::		Coordonnee $y du pixel
	** $color ::	Couleur du pixel
	*/
	public function set_pixel($x, $y, $color)
	{
		if ($x >= $this->width || $y >= $this->height)
		{
			return ;
		}

		$index = $this->find_index($x, $y);
		if (!$this->truecolor)
		{
			$this->image{$index} = $color;
		}
		else
		{
			$this->image{$index} = $color[0];
			$this->image{$index + 1} = $color[1];
			$this->image{$index + 2} = $color[2];
		}
	}

	/*
	** Retourne la couleur d'un pixel
	** -----
	** $x ::		Coordonnee $x du pixel
	** $y ::		Coordonnee $y du pixel
	*/
	public function colorat($x, $y)
	{
		$index = $this->find_index($x, $y);
		if (!$this->truecolor)
		{
			$color = array(
				'red' =>	$this->image{$index},
				'green' =>	$this->image{$index},
				'blue' =>	$this->image{$index},
			);
		}
		else
		{
			$color = array(
				'red' =>	(isset($this->image{$index})) ? $this->image{$index} : pack('c', 255),
				'green' =>	(isset($this->image{$index + 1})) ? $this->image{$index + 1} : pack('c', 255),
				'blue' =>	(isset($this->image{$index + 2})) ? $this->image{$index + 2} : pack('c', 255),
			);
		}
		return ($color);
	}

	/*
	** Fin du fichier PNG
	*/
	public function close($print = TRUE)
	{
		$this->add_signature();
		$this->add_ihdr();
		$this->add_idat();
		$this->add_iend();
		if ($print)
		{
			Http::header('Content-Type', 'image/png');
			Http::header('Cache-control', 'no-cache, no-store');
			echo $this->content;
		}

		return ($this->content);
	}

	/*
	** Un PNG est constitue de plusieurs block contenant : la longueur,
	** le type, les donnees && un CRC
	** -----
	** $length ::	Longueur du block
	** $type ::		Type du block
	** $data ::		Contenu du block
	*/
	private function add_block($length, $type, $data)
	{
		// Signature
		$crc = crc32($type . $data);

		// Generation du block
		return (pack('N', $length) . $type . $data . pack('N', $crc));
	}

	/*
	** Signature du PNG
	*/
	private function add_signature()
	{
		$this->content = pack('C8', 137, 80, 78, 71, 13, 10, 26, 10);
	}

	/*
	** Ajout du block contenant les informations de l'image
	*/
	private function add_ihdr()
	{
		// Largeur
		$data = pack('N', $this->width);

		// hauteur
		$data .= pack('N', $this->height);

		// Echantillonage
		$data .= pack('c', 8);

		// Couleurs
		$data .= pack('c', ($this->truecolor) ? 2 : 0);

		// Compression
		$data .= pack('c', 0);

		// Filtrage
		$data .= pack('c', 0);

		// Entrelacement
		$data .= pack('c', 0);

		$this->content .= $this->add_block(13, 'IHDR', $data);
	}

	/*
	** Ajout du block de fin d'image
	*/
	private function add_iend()
	{
		$this->content .= $this->add_block(0, 'IEND', '');
	}

	/*
	** Ajout du block IDAT
	*/
	private function add_idat()
	{
		$this->image{599} = chr(150);
		if (extension_loaded('zlib'))
		{
			$this->image = gzcompress($this->image);
			$length = strlen($this->image);
		}
		else
		{
			$length = ($this->width + 1) * $height;
			if (extension_loaded('hash'))
			{
				$adler_hash = strrev(hash('adler32', $this->image, true));
			}
			else if (extension_loaded('mhash'))
			{
				$adler_hash = strrev(mhash(MHASH_ADLER32, $this->image));
			}
			else
			{
				$adler_hash = $this->adler32($this->image, $length);
			}

			$this->image = pack('C3v2', 0x78, 0x01, 0x01, $length, ~$length) . $this->image . $adler_hash;
			$length += 11;
		}
		$this->content .= $this->add_block($length, 'IDAT', $this->image);
	}

	/*
	** Implementation de l'algorithme de hash Adler-32
	** -----
	** $raw_image ::	Chaine de caractere a hasher
	** $length ::		Longueur de la chaine
	*/
	private function adler32($str, $length)
	{
		$temp_length = $length;
		$s1 = 1;
		$s2 = $index = 0;

		while ($temp_length > 0)
		{
			$substract_value = ($temp_length < 3800) ? $temp_length : 3800;
			$temp_length -= $substract_value;

			while (--$substract_value >= 0)
			{
				$s1 += @ord($str[$index]);
				$s2 += $s1;

				$index++;
			}

			$s1 %= 65521;
			$s2 %= 65521;
		}
		return (pack('N', ($s2 << 16) | $s1));
	}
}

/*
** Retourne des couleurs valides pour la class Png
*/
class Png_color extends Fsb_model
{
	/*
	** Retourne une couleur a partir d'un entier.
	** Exemple : Png_color::hexa(0xff00cc)
	*/
	public static function hexa($hexa)
	{
		$red = ($hexa & 0xFF0000) >> 16;
		$green = ($hexa & 0x00FF00) >> 8;
		$blue = $hexa & 0x0000FF;
		return (Png_color::rgb($red, $green, $blue));
	}

	/*
	** Retourne une couleur a partir d'une chaine de caractere simulant l'hexadecimal
	** Exemple : Png_color('ff00cc');
	*/
	public static function hexa_str($str)
	{
		$red = hexdec(substr($str, 0, 2));
		$green = hexdec(substr($str, 2, 2));
		$blue = hexdec(substr($str, 4, 2));
		return (Png_color::rgb($red, $green, $blue));
	}

	/*
	** Retourne une couleur a partir de son code couleur
	** Exemple : Png_color::str('red')
	*/
	public static function str($color)
	{
		$color = strtolower($color);
		$exists = array(
			'white' =>		'ffffff',
			'black' =>		'000000',
			'grey' =>		'ccccc',
			'darkgrey' =>	'666666',
			'brown' =>		'793b16',
			'red' =>		'ff0000',
			'green' =>		'00ff00',
			'blue' =>		'0000ff',
			'skyblue' =>	'0000ff',
			'cyan' =>		'00fff0',
			'purple' =>		'ff00ff',
			'pink' =>		'faa3f5',
			'yellow' =>		'ffff00',
			'orange' =>		'ff9c00',
		);

		$index = (isset($exists[$color])) ? $exists[$color] : $exists['black'];
		return (Png_color::hexa_str($index));
	}

	/*
	** Retourne une couleur a partir des composantes RGB
	** Exemple : Png_color::rgb(255, 0, 0)
	*/
	public static function rgb($red, $green, $blue)
	{
		return (pack('c3', $red, $green, $blue));
	}
}

/*
** Classe permettant l'ajout de formes sur des images.
** Elle est prevue pour etre etendue par des classes implementants
** les specifications de formats d'images (GIF, PNG, etc ..)
*/
abstract class Image extends Fsb_model
{
	// Polices chargees en memoire
	private $fonts = array();

	// Methode abstraite pour ajouter un pixel sur l'image
	abstract public function set_pixel($x, $y, $color);

	/*
	** Permet la creation de pixel avec taille de bordure
	** -----
	** $x ::		Coordonnee X
	** $y ::		Coordonnee Y
	** $color ::	Couleur
	** $size ::		Taille du pixel
	*/
	public function set_real_pixel($x, $y, $color, $size = 1)
	{
		if ($size <= 0)
		{
			return ;
		}

		if ($size == 1)
		{
			$this->set_pixel($x, $y, $color);
		}
		else
		{
			$start_x = max(0, $x - floor($size / 2));
			$start_y = max(0, $y - floor($size / 2));
			$end_x = min($this->width, $x + floor($size / 2));
			$end_y = min($this->height, $y + floor($size / 2));

			for ($i = $start_x; $i < $end_x; $i++)
			{
				for ($j = $start_y; $j < $end_y; $j++)
				{
					$this->set_pixel($i, $j, $color);
				}
			}
		}
	}

	/*
	** Ajoute une ligne sur l'image
	** -----
	** $x1 ::		Position X du premier point
	** $y1 ::		Position Y du premier point
	** $x2 ::		Position X du second point
	** $y2 ::		Position Y du second point
	** $style ::	Style
	*/
	public function set_line($x1, $y1, $x2, $y2, $style)
	{
		// Information sur le style
		$border_color =		(isset($style['border-color'])) ? $style['border-color'] : Png_color::str('black');
		$border_width =		(isset($style['border-width'])) ? $style['border-width'] : 1;

		// Initialisation de variables
		$x = $x1;
		$y = $y1;
		$dx = abs($x2 - $x1);
		$dy = abs($y2 - $y1);
		$this->set_real_pixel($x, $y, $border_color, $border_width);

		// Algorithme de calcul de la droite
		if ($x1 <= $x2 && $y1 <= $y2)
		{
			if ($x2 - $x1 >= $y2 - $y1)
			{
				$this->_set_line($dy, $dx, $x, $x2, $y, 'iterator', 'iterator2', '+', '+', $style);
			}
			else
			{
				$this->_set_line($dx, $dy, $y, $y2, $x, 'iterator2', 'iterator', '+', '+', $style);
			}
		}
		else if ($x1 > $x2 && $y1 < $y2)
		{
			if ($x1 - $x2 >= $y2 - $y1)
			{
				$this->_set_line($dy, $dx, $x, $x2, $y, 'iterator', 'iterator2', '-', '+', $style);
			}
			else
			{
				$this->_set_line($dx, $dy, $y, $y2, $x, 'iterator2', 'iterator', '+', '-', $style);
			}
		}
		else if ($x1 >= $x2 && $y1 >= $y2)
		{
			if ($x1 - $x2 >= $y1 - $y2)
			{
				$this->_set_line($dy, $dx, $x, $x2, $y, 'iterator', 'iterator2', '-', '-', $style);
			}
			else
			{
				$this->_set_line($dx, $dy, $y, $y2, $x, 'iterator2', 'iterator', '-', '-', $style);
			}
		}
		else if ($x1 < $x2 && $y1 > $y2)
		{
			if ($x2 - $x1 >= $y1 - $y2)
			{
				$this->_set_line($dy, $dx, $x, $x2, $y, 'iterator', 'iterator2', '+', '-', $style);
			}
			else
			{
				$this->_set_line($dx, $dy, $y, $y2, $x, 'iterator2', 'iterator', '-', '+', $style);
			}
		}
	}

	public function _set_line($d1, $d2, $iterator, $max, $iterator2, $str1, $str2, $inc1, $inc2, $style)
	{
		// Information sur le style
		$border_color =		(isset($style['border-color'])) ? $style['border-color'] : Png_color::str('black');
		$border_width =		(isset($style['border-width'])) ? $style['border-width'] : 1;

		$d = 2 * $d1 - $d2;
		while ($iterator != $max)
		{
			$iterator = ($inc1 == '+') ? $iterator + 1 : $iterator - 1;
			if ($d > 0)
			{
				$iterator2 = ($inc2 == '+') ? $iterator2 + 1 : $iterator2 - 1;
				$d = $d - 2 * $d2;
			}
			$d = $d + 2 * $d1;
			$this->set_real_pixel($$str1, $$str2, $border_color, $border_width);
		}
	}

	/*
	** Ajoute un rectangle sur l'image
	** -----
	** $x ::		Coordonnee $x du coin superieur gauche du rectangle
	** $y ::		Coordonnee $y du coin superieur gauche du rectangle
	** $width ::	Largeur du rectangle
	** $height ::	Hauteur du rectangle
	** $style ::	Style
	*/
	public function set_rectangle($x, $y, $width, $height, $style = array())
	{
		// Information sur le style
		$border_color =		(isset($style['border-color'])) ? $style['border-color'] : Png_color::str('black');
		$border_width =		(isset($style['border-width'])) ? $style['border-width'] : 1;
		$background_color = (isset($style['background-color'])) ? $style['background-color'] : NULL;

		// Arriere plan ?
		if ($background_color !== NULL)
		{
			$max_width = min($x + $width, $this->width);
			$max_height = min($y + $height, $this->height);
			for ($i = $x; $i < $max_width; $i++)
			{
				for ($j = $y; $j < $max_height; $j++)
				{
					$this->set_real_pixel($i, $j, $background_color);
				}
			}
		}

		// Bordures ?
		if ($border_width >= 1)
		{
			$check = array(
				array($x, $y, $x + $width, $y),
				array($x, $y, $x, $y + $height),
				array($x + $width, $y, $x + $width, $y + $height),
				array($x, $y + $height, $x + $width, $y + $height),
			);

			foreach ($check AS $row)
			{
				$this->set_line($row[0], $row[1], $row[2], $row[3], array(
					'border-color' =>		$border_color,
					'border-width' =>		$border_width,
				));
			}
		}
	}

	/*
	** Ajoute un cercle sur l'image
	** -----
	** $x ::		Coordonnee X du centre
	** $y ::		Coordonnee Y du centre
	** $r ::		Rayon du cercle
	** $style ::	Style
	*/
	public function set_circle($x, $y, $r, $style = array())
	{
		// Information sur le style
		$border_color =		(isset($style['border-color'])) ? $style['border-color'] : Png_color::str('black');
		$border_width =		(isset($style['border-width'])) ? $style['border-width'] : 1;

		// Contour du cercle
		if ($border_width >= 1)
		{
			$tmp_x = 0;
			$tmp_y = $r;
			$m = 5 - 4 * $r;
			while ($tmp_x < $tmp_y)
			{
				$this->set_real_pixel($tmp_x + $x, $tmp_y + $y, $border_color, $border_width);
				$this->set_real_pixel($tmp_y + $x, $tmp_x + $y, $border_color, $border_width);
				$this->set_real_pixel(-$tmp_x + $x, $tmp_y + $y, $border_color, $border_width);
				$this->set_real_pixel(-$tmp_y + $x, $tmp_x + $y, $border_color, $border_width);
				$this->set_real_pixel($tmp_x + $x, -$tmp_y + $y, $border_color, $border_width);
				$this->set_real_pixel($tmp_y + $x, -$tmp_x + $y, $border_color, $border_width);
				$this->set_real_pixel(-$tmp_x + $x, -$tmp_y + $y, $border_color, $border_width);
				$this->set_real_pixel(-$tmp_y + $x, -$tmp_x + $y, $border_color, $border_width);

				if ($m > 0)
				{
					$tmp_y--;
					$m = $m - 8 * $tmp_y;
				}
				$tmp_x++;
				$m = $m + 8 * $tmp_x + 4;
			}
		}
	}

	/*
	** Ecrit du texte sur l'image
	** -----
	** $str ::		Chaine de caractere
	** $x ::		Position X du texte
	** $y ::		Position Y du texte
	** $font ::		Police utilisee
	** $size ::		Taille du texte
	** $color ::	Couleur du texte
	** $italic ::	Texte en italique
	** $random_h ::	Coefficient pour modifier aleatoirement la hauteur
	*/
	public function write($str, $x, $y, $font, $size, $color, $italic = FALSE, $random_h = 0)
	{
		// Informations sur la police
		$str = strtoupper($str);
		$base = basename($font);
		if (!isset($this->font[$base]))
		{
			$this->load_font($font);
		}

		// Affichage caractere par caractere
		$pixel_size = floor($size / 6);
		$spacer = 0;
		foreach (str_split($str) AS $char)
		{
			if (isset($this->fonts[$base][$char]))
			{
				$this->_write($this->fonts[$base][$char], $x + $spacer, rand($y - $random_h, $y + $random_h), $pixel_size, $color, $italic);
				$spacer += $size;
			}
		}
	}

	public function _write($info, $x, $y, $pixel_size, $color, $italic = FALSE)
	{
		// Affichage du caractere
		$offset_y = $y;$k = 0;
		foreach ($info AS $r => $row)
		{
			$offset_x = $x;
			foreach ($row AS $pixel)
			{
				if ($pixel == '1')
				{
					for ($current_y = $offset_y; $current_y < $offset_y + $pixel_size; $current_y++)
					{
						for ($current_x = $offset_x; $current_x < $offset_x + $pixel_size; $current_x++)
						{
							$this->set_real_pixel($current_x + (($italic) ? $r : 0), $current_y, $color);
						}
					}
				}
				$offset_x += $pixel_size;
			}
			$offset_y += $pixel_size;
		}
	}

	/*
	** Ajoute un effet de blur sur l'image (fonction assez lente)
	** Tire du tutorial http://www.xgarreau.org/aide/devel/gd/libgd4.php
	** ----
	** $index ::		Coefficient du blur
	*/
	public function filter_blur($index = 1)
	{
		$coeffs = array (
			array(1),
			array(1, 1), 
			array(1, 2, 1),
			array(1, 3, 3, 1),
			array(1, 4, 6, 4, 1),
			array(1, 5, 10, 10, 5, 1),
			array(1, 6, 15, 20, 15, 6, 1),
			array(1, 7, 21, 35, 35, 21, 7, 1),
			array(1, 8, 28, 56, 70, 56, 28, 8, 1),
			array(1, 9, 36, 84, 126, 126, 84, 36, 9, 1),
			array(1, 10, 45, 120, 210, 252, 210, 120, 45, 10, 1),
			array(1, 11, 55, 165, 330, 462, 462, 330, 165, 55, 11, 1)
		);

		$sum = pow(2, $index);
		for ($i = 0 ; $i < $this->width; $i++)
		{
			for ($j = 0 ; $j < $this->height ; $j++)
			{
				$sumr = 0;
				$sumg = 0;
				$sumb = 0;
				for ($k = 0; $k <= $index ; ++$k)
				{
					$color = $this->colorat(floor($i - ($index / 2) + $k), $j);
					$sumr += ord($color['red']) * $coeffs[$index][$k];
					$sumg += ord($color['green']) * $coeffs[$index][$k];
					$sumb += ord($color['blue']) * $coeffs[$index][$k];
					
				}
				$color = Png_color::rgb($sumr / $sum, $sumg / $sum, $sumb / $sum);
				$this->set_pixel($i, $j, $color);
			} 
		}
	}

	/*
	** Charge la police en memoire
	** -----
	** $path ::		Chemin vers la police
	*/
	private function load_font($path)
	{
		$i = 0;
		$char_list = array();
		foreach (file($path) AS $line)
		{
			if ($i == 0)
			{
				$c = trim($line);
				$i++;
			}
			else if ($i == 6)
			{
				$i = 0;
				$c = '';
			}
			else
			{
				$char_list[$c][] = str_split(substr($line, 0, -1));
				$i++;
			}
		}

		$this->fonts[basename($path)] = $char_list;
	}
}


/* EOF */
