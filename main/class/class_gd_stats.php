<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_gd_stats.php
** | Begin :	08/06/2007
** | Last :		12/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

class Gd_stats extends Fsb_model
{
	// Largeur de l'image
	public $width = 600;

	// Hauteur de l'image
	public $height = 300;

	// Largeur des boites sur l'axe X
	public $x_width = 20;

	// Hauteur des boites sur l'axe Y
	public $y_height = 25;

	// Valeurs à afficher sur le graphique
	protected $value = array();
	protected $max;

	// Ressource pour la création de l'image
	protected $img;

	// Couleurs prédéfinis
	protected $color = array();

	/*
	** Constructeur
	** -----
	** $width ::	Largeur de l'image
	** $height ::	Hauteur de l'image
	*/
	public function __construct($width, $height)
	{
		$this->width = $width;
		$this->height = $height;
	}

	/*
	** Assigne les valeurs
	** -----
	** $value ::	Tableau de valeur avec en indice le texte à affiché sur l'axe des abscices, et
	**				en ordonnée les valeurs
	*/
	public function values($value)
	{
		$this->value = $value;
		$this->max = NULL;
		foreach ($this->value AS $v)
		{
			if ($this->max === NULL)
			{
				$this->max = $v['v'];
			}
			$this->max = max($this->max, $v['v']);
		}
	}

	/*
	** Affiche l'image
	*/
	public function output()
	{
		// Création de l'image
		$this->img = imagecreatetruecolor($this->width, $this->height);
		$background = imagecolorallocate($this->img, 255, 255, 255);
		imagefill($this->img, 0, 0, $background);

		// Couleurs prédéfinies
		$this->color = array(
			'black' =>		imagecolorallocate($this->img, 0, 0, 0),
			'pre_axis' =>	imagecolorallocate($this->img, 190, 190, 190),
		);

		// Arrière plan de l'image
		$this->gradedRectangle(0, 0, $this->width, $this->height, array(200, 200, 200), array(245, 245, 245), 5);

		// Affichage des traits fins pour la légende
		$this->pre_axis();

		// Affichage des boites
		$this->box();

		// Affichage des axes
		$this->axis();

		// Affichage de l'image
		imagerectangle($this->img, 0, 0, $this->width - 1, $this->height - 1, $this->color['black']);
		Http::header('Content-type', 'image/gif');
		imagegif($this->img);
	}

	/*
	** Affiche les axes
	*/
	private function axis()
	{
		// Axe des abscices
		$m = $this->x_width / 2;
		imageline($this->img, $this->x_width + $m, $this->height - $this->x_width, $this->width, $this->height - $this->x_width, $this->color['black']);
		$iterator = 1;
		foreach ($this->value AS $v)
		{
			if ($iterator * $this->x_width > $this->width)
			{
				break;
			}

			$abs_x = ($iterator + 1) * $this->x_width + $m;
			imageline($this->img, $abs_x, $this->height - $this->x_width, $abs_x, $this->height - $this->x_width - 5, $this->color['black']);
			imageline($this->img, $abs_x + 1, $this->height - $this->x_width, $abs_x + 1, $this->height - $this->x_width - 5, $this->color['black']);

			// Légende
			imagestring($this->img, 1, $abs_x - $this->x_width + 2, $this->height - $this->x_width + 2, $v['lg'], $this->color['black']);
			$iterator++;
		}

		// Axe des ordonnées
		imageline($this->img, $this->x_width + $m, $m, $this->x_width + $m, $this->height - $this->x_width, $this->color['black']);
		$s = ($this->height - $this->x_width - $m) / 10;
		$s2 = $this->max / 10;
		for ($i = 0; $i < 10; $i++)
		{
			$abs_y = $m + ($i * $s);
			imageline($this->img, $this->x_width + $m, $abs_y, $this->x_width + (($i % 3 == 0) ? 5 : 2) + $m, $abs_y, $this->color['black']);
			imageline($this->img, $this->x_width + $m, $abs_y + 1, $this->x_width + (($i % 3 == 0) ? 5 : 2) + $m, $abs_y + 1, $this->color['black']);

			// Légende
			if ($i % 3 == 0)
			{
				$value = round($this->max - ($i * $s2), 1);
				if ($value > 100)
				{
					$value = round($value);
				}
				imagestring($this->img, 1, $this->x_width - (strlen($value) * 5) - 2 + $m, $abs_y - 2, $value, $this->color['black']);
			}
		}
	}

	/*
	** Traits fins de la légende à afficher avant les boites
	*/
	private function pre_axis()
	{
		$m = $this->x_width / 2;
		$s = ($this->height - $this->x_width - $m) / 10;
		for ($i = 0; $i < 10; $i++)
		{
			$abs_y = $m + ($i * $s);
			imageline($this->img, $this->x_width + $m, $abs_y, $this->width, $abs_y, $this->color['pre_axis']);
		}
	}

	/*
	** Affiche les boites
	*/
	private function box()
	{
		$iterator = 1;
		$m = $this->x_width / 2;
		foreach ($this->value AS $v)
		{
			if ($iterator * $this->x_width > $this->width)
			{
				break;
			}

			$height = $v['v'] * ($this->height - $this->x_width - $m) / $this->max;
			$this->gradedRectangle($iterator * $this->x_width + $m, ($this->height - $this->x_width) - $height, $this->x_width, $height, array(150, 150, 210), array(220, 220, 235), 5, array(0, 0, 150));
			$iterator++;
		}
	}

	/*
	** Affiche un rectangle rempli en faisant un dégradé de couleur de $start à $end
	** -----
	** $x ::			Position X du bord haut gauche du rectangle
	** $y ::			Position Y du bord haut gauche du rectangle
	** $width ::		Largeur du rectangle
	** $height ::		Hauteur du rectangle
	** $start ::		Tableau RGB contenant la couleur de départ
	** $end ::			Tableau RGB contenant la couleur de fin
	** $bordercolor ::	Couleur de la bordure
	*/
	private function gradedRectangle($x, $y, $width, $height, $start, $end, $step = 1, $bordercolor = array())
	{
		$step_red =		($end[0] - $start[0]) / ($height / $step);
		$step_green =	($end[1] - $start[1]) / ($height / $step);
		$step_blue =	($end[2] - $start[2]) / ($height / $step);

		$inf = 0;
		for ($i = 0; $i < $height && $inf++ < 10000; $i += $step)
		{
			if ($y + $i + $step > $y + $height)
			{
				$step = ($y + $height) - $y - $i;
				if ($step < 1)
				{
					$step = 1;
				}
			}

			$color = imagecolorallocate($this->img, floor($start[0]), floor($start[1]), floor($start[2]));
			imagefilledrectangle($this->img, $x, $y + $i, $x + $width, $y + $i + $step, $color);
			$start[0] += $step_red;
			$start[1] += $step_green;
			$start[2] += $step_blue;
		}

		if ($bordercolor)
		{
			imagerectangle($this->img, $x, $y, $x + $width, $y + $height, imagecolorallocate($this->img, $bordercolor[0], $bordercolor[1], $bordercolor[2]));
		}
	}
}
/* EOF */