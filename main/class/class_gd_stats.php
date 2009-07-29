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
 * Genere un graphique de statistiques avec des boites
 */
class Gd_stats extends Fsb_model
{
	/**
	 * Largeur de l'image
	 *
	 * @var int
	 */
	public $width = 600;

	/**
	 * Hauteur de l'image
	 *
	 * @var int
	 */
	public $height = 300;

	/**
	 * Largeur des boites sur l'axe X
	 *
	 * @var int
	 */
	public $x_width = 20;

	/**
	 * Hauteur des boites sur l'axe Y
	 *
	 * @var int
	 */
	public $y_height = 25;

	/**
	 * Valeurs a afficher sur le graphique
	 *
	 * @var array
	 */
	protected $value = array();
	
	/**
	 * Valeur maximale
	 *
	 * @var int
	 */
	protected $max;

	/**
	 * Ressource de l'image cree
	 *
	 * @var resource
	 */
	protected $img;

	/**
	 * Couleurs predefinies
	 *
	 * @var array
	 */
	protected $color = array();

	/**
	 * Constructeur
	 *
	 * @param int $width Largeur de l'image
	 * @param int $height Hauteur de l'image
	 */
	public function __construct($width, $height)
	{
		$this->width = $width;
		$this->height = $height;
	}

	/**
	 * Assigne les valeurs
	 *
	 * @param array $value Tableau de valeur avec en indice le texte a affiche sur l'axe des abscices, et en ordonnee les valeurs
	 */
	public function values($value)
	{
		$this->value = $value;
		$this->max = null;
		foreach ($this->value AS $v)
		{
			if (is_null($this->max))
			{
				$this->max = $v['v'];
			}
			$this->max = max($this->max, $v['v']);
		}
	}

	/**
	 * Genere et affiche l'image
	 */
	public function output()
	{
		// Creation de l'image
		$this->img = imagecreatetruecolor($this->width, $this->height);
		$background = imagecolorallocate($this->img, 255, 255, 255);
		imagefill($this->img, 0, 0, $background);

		// Couleurs predefinies
		$this->color = array(
			'black' =>		imagecolorallocate($this->img, 0, 0, 0),
			'pre_axis' =>	imagecolorallocate($this->img, 190, 190, 190),
		);

		// Arriere plan de l'image
		$this->gradedRectangle(0, 0, $this->width, $this->height, array(200, 200, 200), array(245, 245, 245), 5);

		// Affichage des traits fins pour la legende
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

	/**
	 * Affiche les axes de l'image
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

			// Legende
			imagestring($this->img, 1, $abs_x - $this->x_width + 2, $this->height - $this->x_width + 2, $v['lg'], $this->color['black']);
			$iterator++;
		}

		// Axe des ordonnees
		imageline($this->img, $this->x_width + $m, $m, $this->x_width + $m, $this->height - $this->x_width, $this->color['black']);
		$s = ($this->height - $this->x_width - $m) / 10;
		$s2 = $this->max / 10;
		for ($i = 0; $i < 10; $i++)
		{
			$abs_y = $m + ($i * $s);
			imageline($this->img, $this->x_width + $m, $abs_y, $this->x_width + (($i % 3 == 0) ? 5 : 2) + $m, $abs_y, $this->color['black']);
			imageline($this->img, $this->x_width + $m, $abs_y + 1, $this->x_width + (($i % 3 == 0) ? 5 : 2) + $m, $abs_y + 1, $this->color['black']);

			// Legende
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

	/**
	 * Traits fins de la legende a afficher avant les boites
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

	/**
	 * Affiche les boites
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

			if ($this->max > 0)
			{
				$height = $v['v'] * ($this->height - $this->x_width - $m) / $this->max;
				if ($v['v'] > 0)
				{
					$this->gradedRectangle($iterator * $this->x_width + $m, ($this->height - $this->x_width) - $height, $this->x_width, $height, array(150, 150, 210), array(220, 220, 235), 5, array(0, 0, 150));
				}
			}
			$iterator++;
		}
	}

	/**
	 * Affiche un rectangle rempli en faisant un degrade de couleur de $start a $end
	 *
	 * @param int $x Position X du bord haut gauche du rectangle
	 * @param int $y Position Y du bord haut gauche du rectangle
	 * @param int $width Largeur du rectangle
	 * @param int $height Hauteur du rectangle
	 * @param array $start Tableau RGB contenant la couleur de depart
	 * @param array $end Tableau RGB contenant la couleur de fin
	 * @param int $step Precision du degrade, plus le nombre est grand moins le degrade sera precis
	 * @param array $bordercolor Couleur de la bordure
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