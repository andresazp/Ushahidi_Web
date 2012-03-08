<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Themes Library
 * These are regularly used templating functions
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com>
 * @package	   Ushahidi - http://source.ushahididev.com
 * @module	   Themes Library
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

class Themes_Core {

	public $map_enabled = false;
	public $api_url = null;
	public $main_page = false;
	public $this_page = false;
	public $treeview_enabled = false;
	public $validator_enabled = false;
	public $photoslider_enabled = false;
	public $videoslider_enabled = false;
	public $colorpicker_enabled = false;
	public $editor_enabled = false;
	public $site_style = false;
	public $js = null;

	public $css_url = null;
	public $js_url = null;

	public function __construct()
	{
		// Load cache
		$this->cache = new Cache;

		// Load Session
		$this->session = Session::instance();

		// Grab the proper URL for the css and js files
		$this->css_url = url::file_loc('css');
		$this->js_url = url::file_loc('js');
	}

	/**
	 * Header Block Contains CSS, JS and Feeds
	 * Css is loaded before JS
	 */
	public function header_block()
	{
		$content = Kohana::config("globalcode.head").
			$this->_header_css().
			$this->_header_feeds().
			$this->_header_js();

		// Filter::header_block - Modify Header Block
		Event::run('ushahidi_filter.header_block', $content);

		return $content;
	}

	/**
	* Admin Header Block
	*   The admin header has different requirements so it has a special function
	*/
	public function admin_header_block()
	{
		$content = Kohana::config("globalcode.head");

		// Filter::admin_header_block - Modify Admin Header Block
		Event::run('ushahidi_filter.admin_header_block', $content);

		return $content;
	}

	/**
	 * Css Items
	 */
	private function _header_css()
	{
		$core_css_combine = array(); $core_css = "";
		$core_css_combine[] = "media/css/jquery-ui-themeroller";

		foreach (Kohana::config("settings.site_style_css") as $theme_css)
		{
			$core_css_combine[] = $theme_css;
		}

		$core_css .= "<!--[if lte IE 7]>".html::stylesheet($this->css_url."media/css/iehacks","",true)."<![endif]-->";
		$core_css .= "<!--[if IE 7]>".html::stylesheet($this->css_url."media/css/ie7hacks","",true)."<![endif]-->";
		$core_css .= "<!--[if IE 6]>".html::stylesheet($this->css_url."media/css/ie6hacks","",true)."<![endif]-->";

		if ($this->map_enabled || Kohana::config('config.combine_css'))
		{
			$core_css_combine[] = "media/css/openlayers";
		}

		if ($this->treeview_enabled || Kohana::config('config.combine_css'))
		{
			$core_css_combine[] = "media/css/jquery.treeview";
		}

		if ($this->photoslider_enabled)
		{
			$core_css .= html::stylesheet($this->css_url."media/css/picbox/picbox","",true);
		}

		if ($this->videoslider_enabled)
		{
			$core_css .= html::stylesheet($this->css_url."media/css/videoslider","",true);
		}

		if ($this->colorpicker_enabled || Kohana::config('config.combine_css'))
		{
			$core_css_combine[] = "media/css/colorpicker";
		}

		$core_css_combine[] = "media/css/global";

		Event::run('ushahidi_filter.header_core_css', $core_css_combine);
		
		// Render CSS
		$plugin_css = plugin::render('stylesheet');

		if (Kohana::config('config.combine_css'))
		{
			$core_css = html::stylesheet($this->_combine_media($core_css_combine, 'css'),"",FALSE).$core_css;
		}
		else
		{
			foreach ($core_css_combine as $file) {
				$core_css .= html::stylesheet($this->css_url.$file,"",true);
			}
		}
		
		return $core_css.$plugin_css;
	}

	/*
	 * Combine and compress an array of css/js files
	 * @param Array - file names
	 * @param string - file type (css/js)
	 * @return string - url for combined file
	 **/
	private function _combine_media($files, $type) {
		// Check for already compressed/combined file
		$key = hash('sha256', serialize($files));
		$filename = $this->cache->get($type.'_'.$key);
		
		$file_path = Kohana::config('upload.directory', TRUE);
		// Make sure the directory ends with a slash
		$file_path = rtrim($file_path, '/')."/$type/";
		
		
		if ( empty($filename) || ! file_exists($file_path.$filename))
		{
			$combined = "";
			$minify = new Minify($type);
			foreach($files as $file) {
				$compressed_file = $minify->compress($file,True);
				// Rewrite CSS url paths
				if ($type == 'css')
				{
					$path0 = str_replace(DOCROOT,'',realpath(dirname($file).'/../../').'/');
					$path1 = str_replace(DOCROOT,'',realpath(dirname($file).'/../').'/');

					$compressed_file = preg_replace('#url\([\'"]?(..\/..\/(.*))[\'"]?\)#iU','url("'.url::base().$path0.'$2")',$compressed_file);
					$compressed_file = preg_replace('#url\([\'"]?(..\/(.*))[\'"]?\)#iU','url("'.url::base().$path1.'$2")',$compressed_file);
				}
				$combined .= $compressed_file;
			}
			
			// Generate filename
			$hash = base64_encode(hash('sha256', $combined, TRUE));
			// Modify the hash so it's safe to use in URLs.
			$filename = $type.'_'. strtr($hash, array('+' => '-', '/' => '_', '=' => '')).".$type";
			
			if ( ! is_dir($file_path))
			{
				// Create the upload directory
				mkdir($file_path, 0777, TRUE);
			}
			// Output combined file
			file_put_contents($file_path.$filename, $combined);
			$this->cache->set($type.'_'.$key, $filename);
		}
		
		$base_url = url::base().Kohana::config('upload.relative_directory')."/$type/";
		
		return $base_url.$filename;
	}

	/**
	 * Javascript Files and Inline JS
	 */
	private function _header_js()
	{
		$core_js = "";
		$core_js_combine = array();
		if ($this->map_enabled)
		{
			$core_js .= html::script($this->js_url."media/js/OpenLayers", true);
			$core_js .= "<script type=\"text/javascript\">OpenLayers.ImgPath = '".$this->js_url."media/img/openlayers/"."';</script>";
		}

		$core_js_combine[] = "media/js/jquery";
		$core_js_combine[] = "media/js/jquery.ui.min";
		//$core_js .= html::script("https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js", true);
		$core_js_combine[] = "media/js/jquery.pngFix.pack";
		$core_js_combine[] = "media/js/jquery.timeago";

		if ($this->map_enabled || Kohana::config('config.combine_js'))
		{
			$core_js .= $this->api_url;

			if ($this->main_page || $this->this_page == "alerts" || Kohana::config('config.combine_js'))
			{
				$core_js_combine[] = "media/js/selectToUISlider.jQuery";
			}

			if ($this->main_page || Kohana::config('config.combine_js'))
			{
				$core_js_combine[] = "media/js/jquery.flot";
				$core_js_combine[] = "media/js/timeline";
				$core_js .= "<!--[if IE]>".html::script($this->js_url."media/js/excanvas.min", true)."<![endif]-->";
			}
		}

		if ($this->treeview_enabled || Kohana::config('config.combine_js'))
		{
			$core_js_combine[] = "media/js/jquery.treeview";
		}


		if ($this->colorpicker_enabled)
		{
			$core_js_combine[] = "media/js/colorpicker";
		}

		$core_js_combine[] = "media/js/global";

		if ($this->editor_enabled)
		{
			$core_js .= html::script($this->js_url."media/js/jwysiwyg/jwysiwyg/jquery.wysiwyg.js");
		}

		// Javascript files from plugins
		$plugin_js = plugin::render('javascript');

		// Javascript files from themes
		foreach (Kohana::config("settings.site_style_js") as $theme_js)
		{
			$core_js_combine[] = $theme_js;
		}
		
		Event::run('ushahidi_filter.header_core_js', $core_js_combine);
		
		$inline_js_content = /*"function runScheduler(img){img.onload = null;img.src = '".url::site()."scheduler';}"
			.'$(document).ready(function(){$(document).pngFix();});'
			.*/$this->js;

		$inline_js = "<script type=\"text/javascript\"><!--//";
		$inline_js .= Kohana::config('config.compress_inline_js') ? Minify_Js_Driver::minify($inline_js_content) : $inline_js_content;
		$inline_js .= "\n//--></script>";

		// Filter::header_js - Modify Header Javascript
		Event::run('ushahidi_filter.header_js', $inline_js);
		
		if (Kohana::config('config.combine_js'))
		{
			$core_js .= html::script($this->_combine_media($core_js_combine, 'js'),"",FALSE);
		}
		else
		{
			foreach ($core_js_combine as $file) {
				$core_js .= html::script($this->js_url.$file,"",true);
			}
		}
		
		// Other js files that need to come after jquery
		if ($this->validator_enabled)
		{
			$core_js .= html::script($this->js_url."media/js/jquery.validate.min");
		}

		if ($this->photoslider_enabled)
		{
			$core_js .= html::script($this->js_url."media/js/picbox", true);
		}

		if($this->videoslider_enabled )
		{
			$core_js .= html::script($this->js_url."media/js/coda-slider.pack");
		}
		

		return $core_js.$plugin_js.$inline_js;
	}

	/**
	 * RSS/Atom
	 */
	private function _header_feeds()
	{
		$feeds = "";
		if (Kohana::config("settings.allow_feed"))
		{
			$feeds .= "<link rel=\"alternate\" type=\"application/rss+xml\" href=\"".url::site()."feed/\" title=\"RSS2\" />";
		}

		return $feeds;
	}

	/**
	 * Footer Block potentially holds tracking codes or other code that needs
	 * to run in the footer
	 */
	public function footer_block()
	{
		$content = Kohana::config("globalcode.foot").
				$this->google_analytics()."\n".
				$this->ushahidi_stats_js()."\n".
				$this->cdn_gradual_upgrade()."\n".
				$this->scheduler_js();

		// Filter::footer_block - Modify Footer Block
		Event::run('ushahidi_filter.footer_block', $content);

		return $content;
	}

	public function languages()
	{
		// *** Locales/Languages ***
		// First Get Available Locales

		$locales = $this->cache->get('locales');

		// If we didn't find any languages, we need to look them up and set the cache
		if( ! $locales)
		{
			$locales = locale::get_i18n();
			$this->cache->set('locales', $locales, array('locales'), 604800);
		}

		// Locale form submitted?
		if (isset($_GET['l']) && !empty($_GET['l']))
		{
			$this->session->set('locale', $_GET['l']);
		}
		// Has a locale session been set?
		if ($this->session->get('locale',FALSE))
		{
			// Change current locale
			Kohana::config_set('locale.language', $_SESSION['locale']);
		}

		$languages = "";
		$languages .= "<div class=\"language-box\">";
		$languages .= "<form action=\"\">";

		/**
		 * E.Kala - 05/01/2011
		 *
		 * Fix to ensure to ensure that a change in language loads the page with the same data
		 *
		 * Only fetch the $_GET data to prevent double submission of data already submitted via $_POST
		 * and create hidden form fields for each variable so that these are submitted along with the selected language
		 *
		 * The assumption is that previously submitted data had already been sanitized!
		 */
		foreach ($_GET as $name => $value)
		{
		    $languages .= form::hidden($name, $value);
		}

		// Do a case insensitive sort of locales so it comes up in a rough alphabetical order

		natcasesort($locales);

		$languages .= form::dropdown('l', $locales, Kohana::config('locale.language'),
			' onchange="this.form.submit()" ');
		$languages .= "</form>";
		$languages .= "</div>";

		return $languages;
	}

	public function search()
	{
		$search = "";
		$search .= "<div class=\"search-form\">";
		$search .= "<form method=\"get\" id=\"search\" action=\"".url::site()."search/\">";
		$search .= "<ul>";
		$search .= "<li><input type=\"text\" name=\"k\" value=\"\" class=\"text\" /></li>";
		$search .= "<li><input type=\"submit\" name=\"b\" class=\"searchbtn\" value=\"".Kohana::lang('ui_main.search')."\" /></li>";
		$search .= "</ul>";
		$search .= "</form>";
		$search .= "</div>";

		return $search;
	}

	public function submit_btn()
	{
		$btn = "";

		// Action::pre_nav_submit - Add items before the submit button
		$btn .= Event::run('ushahidi_action.pre_nav_submit');

		if (Kohana::config('settings.allow_reports'))
		{
			$btn .= "<div class=\"submit-incident clearingfix\">";
			$btn .= "<a href=\"".url::site()."reports/submit"."\">".Kohana::lang('ui_main.submit')."</a>";
			$btn .= "</div>";
		}

		// Action::post_nav_submit - Add items after the submit button
		$btn .= Event::run('ushahidi_action.post_nav_submit');

		return $btn;
	}

	/*
	* Google Analytics
	* @param text mixed	 Input google analytics web property ID.
	* @return mixed	 Return google analytics HTML code.
	*/
	public function google_analytics()
	{
		$html = "";
		if (Kohana::config('settings.google_analytics') == TRUE) {
			$html = "<script type=\"text/javascript\">

			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', '".Kohana::config('settings.google_analytics')."']);
			_gaq.push(['_trackPageview']);

			(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();

			</script>";
		}
		return $html;
	}

	/*
	* Scheduler JS Call
	*/
	public function scheduler_js()
	{
		return '<!-- Task Scheduler --><script type="text/javascript">$(document).ready(function(){$(\'#schedulerholder\').html(\'<img src="'.url::base().'scheduler" />\');});</script><div id="schedulerholder"></div><!-- End Task Scheduler -->';
	}

	/*
	* CDN Gradual Upgrade JS Call
	*   This upgrader pushes files from local server to the CDN in a gradual fashion so there doesn't need to
	*   be any downtime when a deployer makes the switch to a CDN
	*/
	public function cdn_gradual_upgrade()
	{
		if (Kohana::config('cdn.cdn_gradual_upgrade') != FALSE)
		{
			return cdn::gradual_upgrade();
		}
		return '';
	}

	/*
	* Ushahidi Stats JS Call
	*    If a deployer is using Ushahidi to track their stats, this is the JS call for that
	*/
	public function ushahidi_stats_js()
	{
		if (Kohana::config('settings.allow_stat_sharing') == 1)
		{
			return Stats_Model::get_javascript();
		}
		return '';
	}
}
