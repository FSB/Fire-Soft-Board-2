<?php
/*
** +---------------------------------------------------+
** |
** | Name :		~/main/class/class_gd.php
** | Begin :	14/09/2006
** | Last :		07/09/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Manipulation de la librairie GD
*/
class Gd extends Fsb_model
{
	// Si l'extension GD est chargee
	public $loaded = TRUE;

	/*
	** CONSTRUCTEUR
	*/
	public function __construct()
	{
		$this->loaded = (PHP_EXTENSION_GD) ? TRUE : FALSE;
	}

	/*
	** Verifie si une image est trop grande
	** -----
	** $path ::			Chemin de l'image
	** $max_width ::	Largeur maximale
	** $max_height ::	Hauteur maximale
	*/
	public function need_resize($path, $max_width, $max_height)
	{
		// Taille de l'image actuelle
		$img_size = @getimagesize($path);
		if (!$img_size)
		{
			return (FALSE);
		}
		$file_height = $img_size[1];
		$file_width = $img_size[0];

		if ($file_width > $max_width || $file_height > $max_height)
		{
			return (TRUE);
		}
		return (FALSE);
	}

	/*
	** Redimensionne une image
	** -----
	** $path ::			Chemin de l'image
	** $max_width ::	Largeur maximale
	** $max_height ::	Hauteur maximale
	*/
	public function resize($path, $max_width, $max_height)
	{
		// Taille de l'image actuelle
		$img_size = @getimagesize($path);
		if (!$img_size)
		{
			return (FALSE);
		}
		$file_height = $img_size[1];
		$file_width = $img_size[0];

		// Extension de l'image
		$ext = get_file_data($path, 'extension');
		if (!in_array($ext, Upload::$img))
		{
			return (FALSE);
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
 
	/*
	** Redimensionement de l'image, avec gestion de la transparence
	** Un grand merci a Shekral (http://www.fire-soft-board.com/fsb/membre-828.html) pour son aide.
	** -----
	** $src ::			Ressource GD de l'image source
	** $new_width ::	Nouvelle largeur de l'image
	** $new_height ::	Nouvelle hauteur de l'image
	** $old_width ::	Ancienne largeur de l'image
	** $old_height ::	Ancienne hauteur de l'image
	*/
	private function resize_alpha(&$src, $new_width, $new_height, $old_width, $old_height)
	{
		$thumb = imagecreatetruecolor($new_width, $new_height);
		imagealphablending($thumb, FALSE);
		imagecopyresampled($thumb, $src, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);
		imagesavealpha($thumb, TRUE);

		return ($thumb);
	}
}

/* EOF */