<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/captcha/captcha.php
** | Begin :	10/07/2006
** | Last :		17/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Permet la generation de Captcha (images avec un texte deforme, destine a bloquer les robots)
*/
abstract class Captcha extends Fsb_model
{
	// Tons du texte, de "tres sombre" a "tres clair"
	const VERY_SINK = 1;
	const SINK = 2;
	const CLEAR = 3;
	const VERY_CLEAR = 4;

	// Largeur / hauteur de l'image
	public $width = 250;
	public $height = 60;

	// Minimum et maximum de la longueur du code a creer
	public $word_min = 6;
	public $word_max = 6;

	// Minimum et maximum de la taille du caractere
	public $size_min = 30;
	public $size_max = 30;

	// Liste des polices
	public $fonts = array('Alanden_.ttf', 'luggerbu.ttf', 'wavy.ttf', 'scrawl.ttf');

	// Angle maximal d'inclinaison des caracteres
	public $angle_max = 20;

	// Liste des caracteres autorises (sans la lettre O et le chiffre 0 pour eviter l'eternelle confusion)
	// On supprime aussi les caractere C et I qui pretent apparament a confusion avec le E et L de certaines polices
	public $caracters = 'ABDEFGHJKLMNPQRSTUVWXYZ12346789';

	// Dossier des polices
	public $font_path = './';

	// Nombre de pixel et lignes, minimum et maximum pour le bruit
	public $total_pixel_min = 1000;
	public $total_pixel_max = 5000;
	public $total_line_min = 0;
	public $total_line_max = 5;

	// Niveau de couleur
	public $color_level = Captcha::VERY_SINK;

	// Tableau contenant les informations sur les caracteres
	protected $data = array();

	// Chaine de caractere du captcha
	protected $str = '';

	// Chaine creee ?
	protected $str_created = FALSE;

	// Explications potentielles
	protected $explain = '';

	// Methodes abstraites
	abstract protected function open_image();
	abstract protected function write_char($size, $angle, $x, $vertical, $fontcolor, $font, $char);
	abstract protected function close_image();
	abstract protected function add_noise();

	/*
	** Retourne une instance de Captcha en fonction de la configuration du serveur
	*/
	public static function factory()
	{
		if (PHP_EXTENSION_GD)
		{
			return (new Captcha_gd());
		}
		else
		{
			return (new Captcha_png());
		}
	}

	/*
	** Cree la chaine de caractere
	*/
	public function create_str()
	{
		$this->set_str($this->random_str());
		$this->str_created = TRUE;
	}

	/*
	** Chaine de caractere du Captcha
	** -----
	** $str ::	Chaine de caractere
	*/
	public function set_str($str)
	{
		$this->str = $str;
	}

	/*
	** Cree une chaine de caractere aleatoire
	*/
	protected function random_str()
	{
		$str = '';
		$max = rand($this->word_min, $this->word_max);
		for ($i = 1; $i <= $max; $i++)
		{
			$str .= $this->caracters{rand(0, strlen($this->caracters) - 1)};
		}
		return ($str);
	}

	/*
	** Calcul les informations sur les caracteres
	** -----
	** $str ::	Chaine par defaut
	*/
	protected function fill_data($str = NULL)
	{
		if ($str === NULL)
		{
			$str = $this->str;
		}
	
		$len = strlen($str);
		for ($i = 0; $i < $len; $i++)
		{
			if (!isset($this->data[$i]))
			{
				$this->data[$i] = array();
			}

			// Selection du caractere
			$this->data[$i]['caracter'] = $str[$i];

			// Police du caractere
			$this->data[$i]['font'] = $this->font_path . $this->fonts[array_rand($this->fonts, 1)];

			// Angle du caractere
			$this->data[$i]['angle'] = (rand(1, 2) === 1) ? rand(0, $this->angle_max) : rand(360 - $this->angle_max, 360);

			// Taille du caractere
			$this->data[$i]['size'] = rand($this->size_min, $this->size_max);

			// Position verticale du caractere
			$this->data[$i]['vertical'] = ($this->height / 2) + rand(10, ($this->height / 5));

			// Couleur
			if (!isset($this->data[$i]['fontcolor']))
			{
				$this->data[$i]['fontcolor'] = NULL;
			}
		}
	}

	/*
	** Generation du Captcha
	*/
	public function output()
	{
		$method = $this->get_method();
		$this->$method();

		$this->open_image();
		$this->write_image();
		$this->add_noise();
		$this->close_image();
	}

	/*
	** Determine la methode a utiliser
	*/
	protected function get_method()
	{
		if (!$this->str_created)
		{
			$index = $this->str{0};
			$this->str = substr($this->str, 2);
			$method = $this->methods[$index];
		}
		else
		{
			$method = $this->methods[array_rand($this->methods, 1)];
			$index_flip = array_flip($this->methods);
			$index = $index_flip[$method];
		}

		$this->store_str = $index . ':' . $this->str;
		return ($method);
	}

	/*
	** Ecrit le texte sur l'image
	*/
	protected function write_image()
	{
		$x = 10;
		foreach ($this->data AS $caracter)
		{
			// Placement du caractere sur l'image
			$this->write_char($caracter['size'], $caracter['angle'], $x, $caracter['vertical'], $caracter['fontcolor'], $caracter['font'], $caracter['caracter']);
			$x += $this->space;
		}
	}

	/*
	** Genere une couleur aleatoire
	*/
	protected function generate_color()
	{
		$check_color = array(
			Captcha::VERY_SINK =>	array(200, '<'),
			Captcha::SINK =>		array(400, '<'),
			Captcha::CLEAR =>		array(500, '>'),
			Captcha::VERY_CLEAR =>	array(650, '>'),
		);

		$ok = false;
		do
		{
			$red =		rand(0, 255);
			$green =	rand(0, 255);
			$blue =		rand(0, 255);
			$color =	$red + $green + $blue;

			// On verifie si le ton de couleur corespond a l'arriere plan
			if (($check_color[$this->color_level][1] == '<' && $color < $check_color[$this->color_level][0]) 
				|| ($check_color[$this->color_level][1] == '>' && $color > $check_color[$this->color_level][0]))
			{
				$ok = TRUE;
			}
		}
		while (!$ok);

		return (array($red, $green, $blue));
	}

	/*
	** Generation d'un Captcha classique
	*/
	protected function method_classic()
	{
		$this->fill_data();
		$this->explain = Fsb::$session->lang('captcha_method_classic');
	}

	/*
	** Affiche une operation arithmetique basique
	*/
	protected function method_maths()
	{	
		// Generation de l'operation mathematique
		if (!$this->str_created)
		{
			$result = $this->str;
		}

		if (rand(0, time()) % 2)
		{
			if (!isset($result))
			{
				$length = rand(1, 2);
				do
				{
					$result = rand(pow(10, $length - 1), pow(10, $length) - 1);
				}
				while ($result < 3);
			}

			$left = rand(1, round($result / 2));
			$middle = rand(1, $result - $left);
			$right = $result - ($left + $middle);
			$this->str = $left . '+' . $middle . '+' . $right;
		}
		else
		{
			$length = 2;
			$result = (!isset($result)) ? rand(pow(10, $length - 1), pow(10, $length) - 1) : $result;
			$left = rand(1, $result - 1);
			$right = $result - $left;
			$this->str = $left . '+' . $right;
		}
		$this->store_str = 'c:' . $result;

		// Longueur de l'image
		$this->width = (strlen($this->str) < 8) ? 10 + (8 * 40) : 10 + (strlen($this->str) * 40);

		// Modification des parametres du bruit, pour eviter de rendre l'operation illisible
		$this->total_pixel_min =	500;
		$this->total_pixel_max =	3000;
		$this->total_line_min =		0;
		$this->total_line_max =		3;

		$this->fill_data();

		$this->explain = Fsb::$session->lang('captcha_method_maths');
	}

	/*
	** Genere un Captcha dont on doit trouver les caracteres uniquement d'une certaine couleur
	*/
	protected function method_color($word = NULL, $info = NULL)
	{
		$secret_word = '';
		$secret_word_data = array();
		for ($i = 0, $k = 0; $i < strlen($this->str); $i++)
		{
			$letter = $this->str{$i};
			$rand = rand(0, 2);
			$j = 0;
			do
			{
				$secret_word .= $letter;
				$secret_word_data[$k++] = array(
					'letter' =>		$letter,
					'true' =>		($j) ? FALSE : TRUE,
				);
				$letter = $this->caracters{rand(0, strlen($this->caracters) - 1)};
				$j++;
			}
			while ($j < $rand);
		}

		// On determine la taille de l'image en fonction du nombre de lettres
		$this->width = 10 + (strlen($secret_word) * 40);

		// Generation des couleurs pour les caracteres
		$list_color = array(
			'blue' =>		array('00', '6C', 'FF'),
			'cyan' =>		array('14', 'E3', 'C3'),
			'pink' =>		array('FF', '58', 'F1'),
			'purple' =>		array('7E', '17', 'B9'),
			'red' =>		array('FF', '00', '00'),
			'green' =>		array('30', 'FF', '00'),
			'orange' =>		array('FF', 'B4', '00'),
			'darkgreay' =>	array('54', '4F', '54'),
		);

		$selected_color = array_rand($list_color, 1);
		foreach (str_split($secret_word) AS $key => $value)
		{
			if ($secret_word_data[$key]['true'])
			{
				list($red, $green, $blue) = $list_color[$selected_color];
			}
			else
			{
				do
				{
					$index = array_rand($list_color, 1);
				}
				while ($index == $selected_color);
				list($red, $green, $blue) = $list_color[$index];
			}

			$this->data[$key]['fontcolor'] = array(hexdec($red), hexdec($green), hexdec($blue));
		}

		$this->explain = sprintf(Fsb::$session->lang('captcha_method_color'), Fsb::$session->lang('captcha_color_' . $selected_color));
		$this->fill_data($secret_word);
	}
}

/* EOF */
