<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Register Themes Hook
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module	   Register Themes Hook
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

class register_themes {
	/**
	 * Adds the register method to load after system.ready
	 */
	public function __construct()
	{
		// Hook into routing
		if (Kohana::config('config.installer_check') == FALSE OR file_exists(DOCROOT."application/config/database.php"))
		{
			Event::add('system.ready', array($this, 'register'));
		}
	}

	/**
	 * Loads ushahidi themes
	 */
	public function register()
	{
		// Array to hold all the CSS files
		$theme_css = array();
		// Array to hold all the Javascript files
		$theme_js = array();

		// 1. Load the default theme
		Kohana::config_set('core.modules', array_merge(array(THEMEPATH."default"),
			Kohana::config("core.modules")));

		//not sure how this should work
		/*$css_url = (Kohana::config("cdn.cdn_css")) ?
			Kohana::config("cdn.cdn_css") : url::base();
		$theme_css[] = $css_url."themes/default/css/style.css";*/
		
		$theme_css = $this->_get_theme_files('default','css');
		$theme_js = $this->_get_theme_files('default','js');
		
		// 2. Extend the default theme
		if ( Kohana::config("settings.site_style") != "default" )
		{
			$theme = THEMEPATH.Kohana::config("settings.site_style");
			Kohana::config_set('core.modules', array_merge(array($theme), Kohana::config("core.modules")));
			
			$theme_css = array_merge($theme_css, $this->_get_theme_files(Kohana::config("settings.site_style"),'css'));
			$theme_js = array_merge($theme_js, $this->_get_theme_files(Kohana::config("settings.site_style"),'js'));
		}

		// 3. Find and add hooks
		// We need to manually include the hook file for each theme
		if (file_exists($theme.'/hooks'))
		{
			$d = dir($theme.'/hooks'); // Load all the hooks
			while (($entry = $d->read()) !== FALSE)
				if ($entry[0] != '.')
				{
					include $theme.'/hooks/'.$entry;
				}
		}

		Kohana::config_set('settings.site_style_css',$theme_css);
		Kohana::config_set('settings.site_style_js',$theme_js);
	}

	/*
	 * Build array of theme files (css or js)
	 * @param string $style - theme name
	 * @param string $type - css or js
	 * @return Array
	 */
	function _get_theme_files($style, $type) {
		$files = array();
		$themedir = THEMEPATH.$style;
		
		if ( is_dir($themedir.'/'.$type) )
		{
			$dir = dir($themedir.'/'.$type); // Load all the themes css files
			while (($file = $dir->read()) !== FALSE)
				if (preg_match("/\.$type/i", $file))
				{
					$files[basename($file)] = url::base()."themes/$style/$type/".$file;
				}
		}
		return $files;
	}
}

new register_themes;