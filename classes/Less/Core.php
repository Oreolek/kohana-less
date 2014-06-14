<?php defined('SYSPATH') or die('No direct script access.');

/**
 * LESS wrapper for Kohana.
 * @package    Kohana
 * @category   Less
 **/
class Less_Core
{
	// Default less files extension
	public static $ext = '.less';
	
	/**
	 * Get the link tag of less paths
	 *
	 * @param   mixed     array of css paths or single path
	 * @param   string    value of media css type
	 * @param   boolean   allow compression
   * @param   boolean   return HTML style tag or file paths
	 * @return  string    link tag pointing to the css paths
   * @return  array     array of css paths
	 */
	public static function compile($array = '', $media = 'screen', $html = TRUE)
	{
		if (is_string($array))
		{
			$array = array($array);
		}
		
		// return comment if array is empty
		if (empty($array)) return self::_html_comment('no less files');

		$stylesheets = array();
		$assets = array();

		// validate
		foreach ($array as $file)
		{
			if (file_exists($file))
			{
				array_push($stylesheets, $file);
			}
			elseif (file_exists($file.self::$ext))
			{
				array_push($stylesheets, $file.self::$ext);				
			}
			else
			{
				array_push($assets, self::_html_comment('could not find '.Debug::path($file).self::$ext));
			}
		}

		// all stylesheets are invalid
		if ( ! count($stylesheets)) return self::_html_comment('all less files are invalid');

		// get less config
		$config = Kohana::$config->load('less');

		// if no compression
		foreach ($stylesheets as $file)
		{
			$filename = self::_get_filename($file, $config['path']);
      if ($html)
      {
        $style = HTML::style($filename, array('media' => $media)); 
      }
      else
      {
        $style = $filename;
      }
			array_push($assets, $style);
		}

    if ($html)
    {
  		return implode("\n", $assets);
    }
    else
    {
      return $assets;
    }
	}

	/**
	 * Compress the css file
	 *
	 * @param   string   css string to compress
	 * @return  string   compressed css string
	 */
	private static function _compress($data)
	{
		$data = preg_replace('~/\*[^*]*\*+([^/][^*]*\*+)*/~', '', $data);
		$data = preg_replace('~\s+~', ' ', $data);
		$data = preg_replace('~ *+([{}+>:;,]) *~', '$1', trim($data));
		$data = str_replace(';}', '}', $data);
		$data = preg_replace('~[^{}]++\{\}~', '', $data);

		return $data;
	}

	/**
	 * Check if the asset exists already, if not generate an asset
	 *
	 * @param   string   path of the css file
	 * @return  string   path to the asset file
	 */
	protected static function _get_filename($file, $path)
	{
		// get the filename
		$filename = preg_replace('/^.+\//', '', $file);

		// get the last modified date
		$last_modified = self::_get_last_modified(array($file));

		// compose the expected filename to store in /media/css
		$compiled = $filename.'-'.$last_modified.'.css';

		// compose the expected file path
		$filename = $path.$compiled;

		// if the file exists no need to generate
		if ( ! file_exists(DOCROOT.$filename))
		{
			touch(DOCROOT.$filename, filemtime($file) - 3600);

			self::_ccompile($file, $filename);
		}

		return $filename;
	}

  /**
   * compile to $in to $out if $in is newer than $out
   * @param string $original path to original file
   * @param string $compiled path to compiled file
	 * @retval boolean 
   **/
  public static function _ccompile($original, $compiled)
  {
    $config = Kohana::$config->load('less');
    if ($config['vendor_internal'] === TRUE)
    {
      require_once '../vendor/lessphp/lessc.inc.php';
	  	return lessc::ccompile($original, $compiled);
    } else {
      if (!is_file($compiled) || filemtime($original) > filemtime($compiled)) {
        $command = 'lessc';
        if ($config['compress'])
        {
          $command .= ' --clean-css';
        }
        return (int) shell_exec($command.' '.$original.' >'.$compiled);
      } else {
        return true;
      }
    }
  }

	/**
	 * Get the most recent modified date of files
	 *
	 * @param   array    array of asset files
	 * @return  string   path to the asset file
	 */
	protected static function _get_last_modified($files)
	{
		$last_modified = 0;

		foreach ($files as $file) 
		{
			$modified = filemtime($file);
			if ($modified !== false and $modified > $last_modified) $last_modified = $modified;
		}

		return $last_modified;
	}

	/**
	 * Format string to HTML comment format
	 *
	 * @param   string   string to format
	 * @return  string   HTML comment
	 */
	protected static function _html_comment($string = '')
	{
		return '<!-- '.$string.' -->';
	}
}
