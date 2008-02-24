<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/captcha/captcha_gd.php
** | Begin :	10/07/2006
** | Last :		17/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Extension de la classe Captcha, utilisant la librairie GD
*/
class Captcha_gd extends Captcha
{
	// Liste des methodes
	public $methods = array(
		'a' =>		'method_classic',
		'b' =>		'method_color',
		'c' =>		'method_maths',
	);

	// Hauteur suplementaire, non prise en compte pour l'affichage des lettres
	private $extra_height = 40;

	// Couleur du fond de l'image
	private $background_color = 0xFFFFFF;

	// Espace entre chaque caractere
	protected $space = 40;

	// Handler de l'image
	private $img;

	/*
	** Constructeur
	*/
	public function __construct()
	{
		$this->font_path = ROOT . 'main/class/captcha/fonts/';
	}

	/*
	** Creation de l'image
	*/
	protected function open_image()
	{
		$this->img = imagecreatetruecolor($this->width, $this->height + $this->extra_height);
		$background = $this->color($this->background_color);
		imagefill($this->img, 0, 0, $background);
	}

	/*
	** Affiche l'image
	*/
	protected function close_image()
	{
		$this->add_footer($this->explain);

		Http::header('Content-type', 'image/gif');
		imagegif($this->img);
	}

	/*
	** Alloue une couleur a partir d'un code hexadecimal
	** -----
	** $hexa ::		Couleur en hexadecimal
	** $callback ::	Callback de couleur
	*/
	private function color($hexa, $callback = 'imagecolorallocate')
	{
		$red = ($hexa & 0xFF0000) >> 16;
		$green = ($hexa & 0x00FF00) >> 8;
		$blue = $hexa & 0x0000FF;
		return ($callback($this->img, $red, $green, $blue));
	}

	/*
	** Ecrit un caractere sur l'image
	*/
	protected function write_char($size, $angle, $x, $vertical, $fontcolor, $font, $char)
	{
		if ($fontcolor === NULL)
		{
			list($red, $green, $blue) = $this->generate_color();
		}
		else
		{
			list($red, $green, $blue) = $fontcolor;
		}

		if ($char == '+')
		{
			$angle = 0;
		}

		$fontcolor = imagecolorallocatealpha($this->img, $red, $green, $blue, 0);
		imagettftext($this->img, $size, $angle, $x, $vertical, $fontcolor, $font, $char);
	}

	/*
	** Ajouts de bruits sur l'image (lignes, points aleatoires)
	*/
	protected function add_noise()
	{
		$noise_color = $this->color(0xBBBBBB);
		$total_pixel = rand($this->total_pixel_min, $this->total_pixel_max);
		for ($i = 1; $i < $total_pixel; $i++)
		{
			imagesetpixel($this->img, rand(0, $this->width - 1), rand(0, $this->height - 1), $noise_color);
		}

		$total_line = rand($this->total_line_min, $this->total_line_max);
		for ($i = 1; $i <= $total_line; $i++)
		{
			imageline($this->img, rand(0, $this->width - 1), rand(0, $this->height - 1), rand(0, $this->width - 1), rand(0, $this->height - 1), $noise_color);
		}

		// Ajout d'un effet de blur (PHP5)
		if (function_exists('imagefilter'))
		{
			imagefilter($this->img, IMG_FILTER_GAUSSIAN_BLUR);
		}
	}

	/*
	** Affiche du texte au pied de l'image
	** -----
	** $text ::		Texte a afficher
	*/
	private function add_footer($text)
	{
		$text = wordwrap($text, $this->width / 8);
		imagefilledrectangle($this->img, 0, $this->height, $this->width, $this->height + $this->extra_height, imagecolorallocatealpha($this->img, 240, 240, 240, 0));
		imagerectangle($this->img, 0, $this->height, $this->width - 1, $this->height + $this->extra_height - 1, imagecolorallocatealpha($this->img, 200, 200, 200, 0));
		imagettftext($this->img, 12, 0, 10, $this->height + 14, imagecolorallocatealpha($this->img, 0, 0, 0, 0), $this->font_path . 'PRINC___.TTF', $text);
		imagettftext($this->img, 12, 0, 11, $this->height + 14, imagecolorallocatealpha($this->img, 0, 0, 0, 0), $this->font_path . 'PRINC___.TTF', $text);
	}
}


/* EOF */
