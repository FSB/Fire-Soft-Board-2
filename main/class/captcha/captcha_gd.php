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
 * Extension de la classe Captcha, utilisant la librairie GD
 *
 */
class Captcha_gd extends Captcha
{
	/**
	 * Liste des methodes de calcul de Captcha supportees
	 *
	 * @var array
	 */
	public $methods = array(
		'a' =>		'method_classic',
		'b' =>		'method_color',
		'c' =>		'method_maths',
	);

	/**
	 * Hauteur suplementaire, non prise en compte pour l'affichage des lettres
	 *
	 * @var int
	 */
	private $extra_height = 40;

	/**
	 * Couleur du fond de l'image
	 *
	 * @var int
	 */
	private $background_color = 0xFFFFFF;

	/**
	 * Espaces entre chaque charactere
	 * 
	 * @var int
	 */
	protected $space = 40;

	/**
	 * Pointe sur l'image courante
	 *
	 * @var resource
	 */
	private $img;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		$this->font_path = ROOT . 'main/class/captcha/fonts/';
	}

	/**
	 * @see Captcha::open_image()
	 */
	protected function open_image()
	{
		$this->img = imagecreatetruecolor($this->width, $this->height + $this->extra_height);
		$background = $this->color($this->background_color);
		imagefill($this->img, 0, 0, $background);
	}

	/**
	 * @see Captcha::close_image()
	 */
	protected function close_image()
	{
		$this->add_footer($this->explain);

		Http::header('Content-type', 'image/gif');
		imagegif($this->img);
	}

	/**
	 * Alloue une couleur
	 *
	 * @param int $hexa Hexadecimal de la couleur, par exemple 0xCCCCCC
	 * @param string $callback Callback pour le type de couleur
	 * @return mixed
	 */
	private function color($hexa, $callback = 'imagecolorallocate')
	{
		$red = ($hexa & 0xFF0000) >> 16;
		$green = ($hexa & 0x00FF00) >> 8;
		$blue = $hexa & 0x0000FF;
		return ($callback($this->img, $red, $green, $blue));
	}

	/**
	 * @see Captcha::write_char()
	 */
	protected function write_char($size, $angle, $x, $vertical, $fontcolor, $font, $char)
	{
		if (is_null($fontcolor))
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

	/**
	 * @see Captcha::add_noise()
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

	/**
	 * Affiche du texte au pied de l'image
	 *
	 * @param string $text Texte a afficher
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
