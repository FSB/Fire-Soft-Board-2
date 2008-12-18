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
 * Classe de compression / decompression en tar et tar.gz.
 * Tiree du fichier includes/functions_compress.php de phpBB3, et modifiee pour les besoins de la classe Compress() de FSB2.
 * @link http://www.phpbb.com
 *
 * Tar/tar.gz compression routine
 * Header/checksum creation derived from tarfile.pl, (c) Tom Horsley, 1994
 */
class Compress_tar extends Fsb_model
{
	/**
	 * Encodage GZIP
	 *
	 * @var bool
	 */
	private $isgz = false;
	
	/**
	 * Nom du fichier
	 *
	 * @var string
	 */
	private $filename = '';
	
	/**
	 * @var bool
	 */
	private $wrote = false;

	/**
	 * Donnees pour l'extraction
	 *
	 * @var array
	 */
	public $Entries = array();

	/**
	 * Constructor
	 */
	public function __construct($file, $type = '')
	{
		$type = (!$type) ? $file : $type;
		$this->isgz = (strpos($type, '.tar.gz') !== false || strpos($type, '.tgz') !== false) ?  true  : false;

		$this->file = &$file;
	}

	/**
	 * Extract archive
	 */
	public function extract($filename)
	{
		// Fonctions de lecture des fichiers
		if ($this->isgz && @extension_loaded('zlib'))
		{
			$fzopen = 'gzopen';
			$fzread = 'gzread';
			$fzclose = 'gzclose';
		}
		else
		{
			$fzopen = 'fopen';
			$fzread = 'fread';
			$fzclose = 'fclose';
		}


		// Run through the file and grab directory entries
		$fd = $fzopen(ROOT . $filename, 'r');
		while ($buffer = $fzread($fd, 512))
		{
			$tmp = unpack("A6magic", substr($buffer, 257, 6));

			if (trim($tmp['magic']) == 'ustar')
			{
				$tmp = unpack("A100name", $buffer);
				$filename = trim($tmp['name']);

				$tmp = unpack("Atype", substr($buffer, 156, 1));
				$filetype = (int) trim($tmp['type']);

				$tmp = unpack("A12size", substr($buffer, 124, 12));
				$filesize = octdec((int) trim($tmp['size']));

				if ($filetype == 5)
				{
					// Creation des dossiers inutile puisque geree plus haut
				}
				else if($filesize != 0 && ($filetype == 0 || $filetype == "\0"))
				{
					$this->Entries[] = array(
						'filename' =>	$filename,
						'filetype' =>	$filetype,
						'filesize' =>	$filesize,
						'data' =>		trim($fzread($fd, $filesize + 512 - ($filesize % 512))),
					);
				}
			}
		}
		$fzclose($fd);
	}

	/**
	 * Create the structures
	 */
	public function data($name, $falsepath = '', $is_dir = false)
	{
		if (!$falsepath)
		{
			$falsepath = '';
		}

		$data = file_get_contents(ROOT . $name);
		$stat = stat(ROOT . $name);
		$this->wrote =  true ;

		$typeflag = ($is_dir) ? '5' : '';

		// This is the header data, it contains all the info we know about the file or folder that we are about to archive
		$header = '';
		$header .= pack("a100", $falsepath);				// file name
		$header .= pack("a8", sprintf("%07o", $stat[2]));	// file mode
		$header .= pack("a8", sprintf("%07o", $stat[4]));	// owner id
		$header .= pack("a8", sprintf("%07o", $stat[5]));	// group id
		$header .= pack("a12", sprintf("%011o", $stat[7]));	// file size
		$header .= pack("a12", sprintf("%011o", $stat[9]));	// last mod time

		// Checksum
		$checksum = 0;
		for ($i = 0; $i < 148; $i++)
		{
			$checksum += ord(substr($header, $i, 1));
		}

		// We precompute the rest of the hash, this saves us time in the loop and allows us to insert our hash without resorting to string functions
		$checksum += 2415 + (($is_dir) ? 53 : 0);

		$header .= pack("a8", sprintf("%07o", $checksum));	// checksum
		$header .= pack("a1", $typeflag);					// link indicator
		$header .= pack("a100", '');						// name of linked file
		$header .= pack("a6", 'ustar');						// ustar indicator
		$header .= pack("a2", '00');						// ustar version
		$header .= pack("a32", 'Unknown');					// owner name
		$header .= pack("a32", 'Unknown');					// group name
		$header .= pack("a8", '');							// device major number
		$header .= pack("a8", '');							// device minor number
		$header .= pack("a155", '');						// filename prefix
		$header .= pack("a12", '');							// end

		// Compression, puis retour des donnees
		if ($this->isgz && @extension_loaded('zlib'))
		{
			$fzopen = 'gzopen';
			$fzwrite = 'gzwrite';
			$fzclose = 'gzclose';

			// Fichier temporaire pour compresser les donnees
			$tmp = ROOT . 'upload/compress_' . md5($name) . '.tar.gz';
			$fd = $fzopen($tmp, 'w');
			$fzwrite($fd, $header . (($stat[7] !== 0 && !$is_dir) ? $data . (($stat[7] % 512 > 0) ? str_repeat("\0", 512 - $stat[7] % 512) : '') : ''));
			$fzclose($fd);

			// Retour des donnees compresses
			$content = file_get_contents($tmp);
			unlink($tmp);
			return ($content);
		}
		else
		{
			return ($header . (($stat[7] !== 0 && !$is_dir) ? $data . (($stat[7] % 512 > 0) ? str_repeat("\0", 512 - $stat[7] % 512) : '') : ''));
		}
	}
}

/* EOF */