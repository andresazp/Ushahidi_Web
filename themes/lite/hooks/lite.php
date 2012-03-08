<?php defined('SYSPATH') or die('No direct script access.');
/**
 * TED Hook - Load All Events
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
			'media/js/selectToUISlider.jQuery',
			'media/js/jquery.flot'
		);
		
		foreach($data as $key => $file)
		{
			if (in_array($file, $remove_js))
			{
				unset(Event::$data[$key]);
			}
		}
	}
	
}

new lite;