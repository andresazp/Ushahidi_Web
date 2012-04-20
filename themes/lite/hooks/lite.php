<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Lite Hooks - Load All Events
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @package	   Ushahidi - http://source.ushahididev.com
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class lite {
	
	/**
	 * Register events
	 */
	public function __construct()
	{
		// Hook into routing
		Event::add('ushahidi_filter.header_core_js', array($this, '_core_js'));
		Event::add('ushahidi_filter.footer_block', array($this, '_footer_block'));
	}
	
	/**
	 * Modify core js files
	 */
	public function _core_js()
	{
		$data = Event::$data;
		
		$remove_js = array(
			'media/js/jquery.ui.min',
			'media/js/jquery.pngFix.pack',
			'media/js/jquery.timeago',
			'media/js/jquery.flot'
		);
		
		$replace_js = array(
			//'media/js/jquery.ui.min' => 'themes/lite/js/jquery-ui-1.8.18.custom.min'
		);
		
		foreach($data as $key => $file)
		{
			if (in_array($file, $remove_js))
			{
				unset(Event::$data[$key]);
			}
			if (isset($replace_js[$file]))
			{
				Event::$data[$key] = $replace_js[$file];
			}
		}
	}

	public function _footer_block() {
		$themes = new Themes();
		Event::$data = str_replace($themes->scheduler_js(),'',Event::$data);
	}
	
}

new lite;
