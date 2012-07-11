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
	}

	/**
	 * Header Block Contains CSS, JS and Feeds
	 * Css is loaded before JS
	 */
	public function header_block()
	{
		Requirements::customHeadTags(Kohana::config("globalcode.head"),'globalcode-head');
		
		// These just need to run now
		$this->_header_css();
		$this->_header_feeds();
		$this->_header_js();

		$content = '';
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
		Requirements::customHeadTags(Kohana::config("globalcode.head"),'globalcode-head');

		$content = '';
		// Filter::admin_header_block - Modify Admin Header Block
		Event::run('ushahidi_filter.admin_header_block', $content);

		return $content;
	}

	/**
	 * Css Items
	 */
	private function _header_css()
	{
		Requirements::css("media/css/jquery-ui-themeroller");

		Requirements::customHeadTags("<!--[if lte IE 7]>".html::stylesheet($this->css_url."media/css/iehacks","",TRUE)."<![endif]-->",'iehacks');
		Requirements::customHeadTags("<!--[if IE 7]>".html::stylesheet($this->css_url."media/css/ie7hacks","",TRUE)."<![endif]-->",'ie7hacks');
		Requirements::customHeadTags("<!--[if IE 6]>".html::stylesheet($this->css_url."media/css/ie6hacks","",TRUE)."<![endif]-->",'ie6hacks');

		if ($this->map_enabled)
		{
			Requirements::css("media/css/openlayers");
		}

		if ($this->treeview_enabled)
		{
			Requirements::css("media/css/jquery.treeview");
		}

		if ($this->photoslider_enabled)
		{
			Requirements::css("media/css/picbox/picbox");
		}

		if ($this->videoslider_enabled)
		{
			Requirements::css("media/css/videoslider");
		}

		if ($this->colorpicker_enabled)
		{
			Requirements::css("media/css/colorpicker");
		}

		if ($this->site_style AND $this->site_style != "default")
		{
			Requirements::css("themes/".$site_style."/style.css");
		}

		Requirements::css("media/css/global");
		Requirements::css("media/css/jquery.jqplot.min");
	}

	/**
	 * Javascript Files and Inline JS
	 */
	private function _header_js()
	{
		Requirements::set_write_js_to_body(FALSE);
		
		if ($this->map_enabled)
		{
			Requirements::js("media/js/OpenLayers");
			Requirements::customJS("OpenLayers.ImgPath = '".$this->js_url."media/img/openlayers/"."';",'openlayers-imgpath');
			Requirements::js("media/js/ushahidi");
		}

		Requirements::js("media/js/jquery");
		//Requirements::js("media/js/jquery.ui.min");
		Requirements::js("https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js");
		Requirements::js("media/js/jquery.pngFix.pack");
		Requirements::js("media/js/jquery.timeago");

		if ($this->map_enabled)
		{

			//Requirements::customJS($this->api_url,'api_url');
			Requirements::customHeadTags($this->api_url);

			if ($this->main_page || $this->this_page == "alerts")
			{
				Requirements::js($this->js_url."media/js/selectToUISlider.jQuery");
			}

			if ($this->main_page)
			{
				// Notes: E.Kala <emmanuel(at)ushahidi.com>
				// TODO: Only include the jqplot JS when the timeline is enabled
				Requirements::js("media/js/jquery.jqplot.min");
				Requirements::js("media/js/jqplot.dateAxisRenderer.min");

				Requirements::customHeadTags("<!--[if IE]>".html::script($this->js_url."media/js/excanvas.min", TRUE)."<![endif]-->");
			}
		}

		if ($this->treeview_enabled)
		{
			Requirements::js("media/js/jquery.treeview");
		}

		if ($this->validator_enabled)
		{
			Requirements::js("media/js/jquery.validate.min");
		}

		if ($this->photoslider_enabled)
		{
			Requirements::js("media/js/picbox");
		}

		if ($this->videoslider_enabled)
		{
			Requirements::js("media/js/coda-slider.pack");
		}

		if ($this->colorpicker_enabled)
		{
			Requirements::js("media/js/colorpicker");
		}

		Requirements::js("media/js/global");

		if ($this->editor_enabled)
		{
			Requirements::js("media/js/jwysiwyg/jwysiwyg/jquery.wysiwyg.js");
		}

		// Inline Javascript
		Requirements::customJS('function runScheduler(img){ img.onload = null;img.src = \''.url::site().'scheduler'.'\';}'.'$(document).ready(function(){$(document).pngFix();});', 'pngfix');
		Requirements::customJS($this->js,'pagejs');
	}

	/**
	 * RSS/Atom
	 */
	private function _header_feeds()
	{
		if (Kohana::config("settings.allow_feed"))
		{
			Requirements::customHeadTags("<link rel=\"alternate\" type=\"application/rss+xml\" href=\"".url::site('feed')."\" title=\"RSS2\" />",'rss-feed');
		}
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

		$locales = ush_locale::get_i18n();

		$languages = "";
		$languages .= "<div class=\"language-box\">";
		$languages .= form::open(NULL, array('method' => 'get'));

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
		$languages .= form::close();
		$languages .= "</div>";

		return $languages;
	}

	public function search()
	{
		$search = "";
		$search .= "<div class=\"search-form\">";
		$search .= form::open("search", array('method' => 'get', 'id' => 'search'));
		$search .= "<ul>";
		$search .= "<li><input type=\"text\" name=\"k\" value=\"\" class=\"text\" /></li>";
		$search .= "<li><input type=\"submit\" name=\"b\" class=\"searchbtn\" value=\"".Kohana::lang('ui_main.search')."\" /></li>";
		$search .= "</ul>";
		$search .= form::close();
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
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = TRUE;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();

			</script>";
		}

		// See if we need to disqualify showing the tag on the admin panel
		if (Kohana::config('config.google_analytics_in_admin') == FALSE
			AND isset(Router::$segments[0])
			AND Router::$segments[0] == 'admin')
		{
			// Site is configured to not use the google analytics tag in the admin panel
			//   and we are in the admin panel. Wipe out the tag.
			$html = '';
		}


		return $html;
	}

	/**
	 * Scheduler JS Call
	 *
	 * @return string
	 */
	public function scheduler_js()
	{
		if (Kohana::config('config.output_scheduler_js'))
		{
			return '<!-- Task Scheduler -->'
			    . '<script type="text/javascript">'
			    . 'jQuery(document).ready(function(){'
			    . '	jQuery(\'#schedulerholder\').html(\'<img src="'.url::base().'scheduler" />\');'
			    . '});'
                . '</script>'
                . '<div id="schedulerholder"></div>'
                . '<!-- End Task Scheduler -->';
		}
		return '';
	}

	/*
	* CDN Gradual Upgrade JS Call
	*   This upgrader pushes files from local server to the CDN in a gradual
	*   fashion so there doesn't need to be any downtime when a deployer makes
	*   the switch to a CDN
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
	*    If a deployer is using Ushahidi to track their stats, this is the JS
	*    call for that
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
