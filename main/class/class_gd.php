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
 * Manipulation de la librairie GD
 */
class Gd extends Fsb_model
{
	/**
	 * True si l'extension GD est chargee
	 *
	 * @var bool
	 */
	public $loaded = true;

	/**
	 * Constructeur, verifie si l'extension GD est chargee
	 */
	public function __construct()
	{
		$this->loaded = (PHP_EXTENSION_GD) ? true : false;
	}

	/**
	 * Verifie si une image est trop grande
	 *
	 * @param string $path Chemin de l'image
	 * @param int $max_width Largeur maximale
	 * @param int $max_height Hauteur maximale
	 * @return bool
	 */
	public function need_resize($path, $max_width, $max_height)
	{
		// Taille de l'image actuelle
		$img_size = @getimagesize($path);
		if (!$img_size)
		{
			return (false);
		}
		$file_height = $img_size[1];
		$file_width = $img_size[0];

		if ($file_width > $max_width || $file_height > $max_height)
		{
			return (true);
		}
		return (false);
	}

	/**
	 * Redimensionne une image
	 *
	 * @param string $path Chemin de l'image
	 * @param int $max_width Largeur maximale
	 * @param int $max_height Hauteur maximale
	 * @return bool|string False si la redimension echoue, sinon le contenu de la nouvelle image
	 */
	public function resize($path, $max_width, $max_height)
	{
		// Taille de l'image actuelle
		$img_size = @getimagesize($path);
		if (!$img_size)
		{
			return (false);
		}
		$file_height = $img_size[1];
		$file_width = $img_size[0];

		// Extension de l'image
		$ext = get_file_data($path, 'extension');
		if (!in_array($ext, Upload::$img))
		{
			return (false);
		}

		// Tout d'abord on calcul la nouvelle taille de limage
		$width_handler = $file_width - $max_width;
		$height_handler = $file_height - $max_height;
		$size_handler = ($width_handler > $height_handler) ? $file_width : $file_height;
		$max_handler = ($width_handler > $height_handler) ? $max_width : $max_height;
		$new_width = ($file_width / $size_handler) * $max_handler;
		$new_height = ($file_height / $size_handler) * $max_handler;

		// Type d'image
		switch ($ext)
		{
			case 'jpg' :
			case 'jpeg' :
				$open = 'imagecreatefromjpeg';
				$write = 'imagejpeg';
			break;

			case 'png' :
				$open = 'imagecreatefrompng';
				$write = 'imagepng';
			break;

			case 'gif' :
				$open = 'imagecreatefromgif';
				$write = 'imagegif';
			break;

			case 'bmp' :
				$open = 'imagecreatefromwbmp';
				$write = 'imagewbmp';
			break; 
		}

		// Redimensionnement de l'image
		$src = $open($path);
		$thumb = $this->resize_alpha($src, $new_width, $new_height, $file_width, $file_height);

		// Affichage
		ob_start();
		$write($thumb);
		$content = ob_get_contents();
		ob_end_clean();

		return ($content);
	}
 
	/**
	 * Redimensionement de l'image, avec gestion de la transparence.
	 * Un grand merci a Shekral (http://www.fire-soft-board.com/fsb/membre-828.html) pour son aide.
	 *
	 * @param resource $src Ressource GD de l'image source
	 * @param int $new_width Nouvelle largeur de l'image
	 * @param int $new_height Nouvelle hauteur de l'image
	 * @param int $old_width Ancienne largeur de l'image
	 * @param int $old_height Ancienne hauteur de l'image
	 * @return resource
	 */
	private function resize_alpha(&$src, $new_width, $new_height, $old_width, $old_height)
	{
		$thumb = imagecreatetruecolor($new_width, $new_height);
		imagealphablending($thumb, false);
		imagecopyresampled($thumb, $src, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);
		imagesavealpha($thumb, true);

		return ($thumb);
	}
}

/* EOF */