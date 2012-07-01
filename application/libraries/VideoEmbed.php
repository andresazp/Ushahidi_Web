<?php
/**
 * Video embedding libary
 * Provides a feature for embedding videos (YouTube, Google Video, Revver, Metacafe, LiveLeak, 
 * Dostub and Vimeo) in a report
 * 
 * @package	   VideoEmbed
 * @author	   Ushahidi Team
 * @copyright  (c) 2008 Ushahidi Team
 * @license	   http://www.ushahidi.com/license.html
 */
class VideoEmbed 
{
	/*
	 * Get the services supported by VideoEmbed
	 * 
	 */
	public function services()
	{
		$services = array(
			"youtube" => array(
				'baseurl' => "http://www.youtube.com/watch?v=",
				'searchstring' => 'youtube.com'
			),
			// May now be defunct
			"google" => array(
				'baseurl' => "http://video.google.com/videoplay?docid=-",
				'searchstring' => 'google.com'
			),
			"metacafe" => array(
				'baseurl' => "http://www.metacafe.com/watch/", 
				'searchstring' => 'metacafe.com'
			),
			"dotsub" => array(
				'baseurl' => "http://dotsub.com/view/",
				'searchstring' => 'dotsub.com'
			),
			"vimeo" => array(
				'baseurl' => "http://vimeo.com/",
				'searchstring' => 'vimeo.com'
			),
		);
		
		Event::run('ushahidi_filter.video_embed_services',$services);
		
		return $services;
	}
	
	public function detect_service($raw)
	{
		// To hold the name of the video service
		$service_name = "";
		
		// Trim whitespaces from the raw data
		$raw = trim($raw);
		
		// Array of the supportted video services
		$services = $this->services();
		
		// Determine the video service to use
		$service_name = false;
		foreach ($services as $key => $value)
		{
			// Match raw url against service search string
			if (strpos($raw, $value['searchstring']))
			{
				$service_name = $key;
				break;
			}
		}
		
		return $service_name;
	}
	
	/**
	 * Generates the HTML for embedding a video
	 *
	 * @param string $raw URL of the video to be embedded
	 * @param boolean $auto Autoplays the video as soon as its loaded
	 * @param boolean $echo Should we echo the embed code or just return it
	 */
	public function embed($raw, $auto, $echo = true)
	{
		$service_name = $this->detect_service($raw);
		
		// Get video code from url.
		$code = str_replace($services[$service_name]['baseurl'], "", $raw);
		
		switch($service_name)
		{
			case "youtube":
				// Check for autoplay
				$you_auto = ($auto) ? "&autoplay=1" : "";
				
				$output = '<iframe id="ytplayer" type="text/html" width="320" height="265" '
					. 'src="http://www.youtube.com/embed/'.$code.'?origin='.url::base().$you_auto.' '
					. 'frameborder="0"/>';
			break;
			
			case "google":
				// Check for autoplay
				$google_auto = ($auto) ? "&autoPlay=true" : "";
				
				$output = "<embed style='width:320px; height:265px;' id='VideoPlayback' type='application/x-shockwave-flash'"
					. "	src='http://video.google.com/googleplayer.swf?docId=-$code$google_auto&hl=en' flashvars=''>"
					. "</embed>";
			break;
			
			case "metacafe":
				// Sanitize input
				$code = strrev(trim(strrev($code), "/"));
				
				$output = "<embed src='http://www.metacafe.com/fplayer/$code.swf'"
					. "	width='320' height='265' wmode='transparent' pluginspage='http://get.adobe.com/flashplayer/'"
					. "	type='application/x-shockwave-flash'> "
					. "</embed>";
			break;
			
			case "dotsub":
				$output = "<iframe src='http://dotsub.com/media/$code' frameborder='0' width='320' height='500'></iframe>";
			
			break;
			
			case "vimeo":
				$vimeo_auto = ($auto) ? "?autoplay=1" : "";
				
				$output = "<iframe src=\"http://player.vimeo.com/video/$code$vimeo_auto\" width=\"100%\" height=\"300\" frameborder=\"0\">"
					. "</iframe>";
			break;
			
			case 'default':
				$output = '<a href="'.$raw.'" target="_blank">'.Kohana::lang('ui_main.view_view').'</a>';
			break;
		}
		
		if ($echo) echo $output;
		
		return $output;
	}
	
	/**
	 * Generates the thumbnail a video
	 *
	 * @param string $raw URL of the video
	 */
	public function thumbnail($raw)
	{
		$service_name = $this->detect_service($raw);
		
		// Get video code from url.
		$code = str_replace($services[$service_name]['baseurl'], "", $raw);
		
		switch($service_name)
		{
			case "youtube":
				$oembed = @json_decode(file_get_contents("http://www.youtube.com/oembed?url=".urlencode($raw)));
				if (!empty($oembed) AND empty($oembed['thumbnail_url']))
				{
					return $oembed['thumbnail_url'];
				}
				
				return FALSE;
			break;
			
			case "google":
				return FALSE;
			break;
			
			case "metacafe":
				return FALSE;
			break;
			
			case "dotsub":
				$oembed = @json_decode(file_get_contents("http://dotsub.com/services/oembed?url=".urlencode($raw)));
				if (!empty($oembed) AND empty($oembed['thumbnail_url']))
				{
					return $oembed['thumbnail_url'];
				}
				
				return FALSE;
			break;
			
			case "vimeo":
				$oembed = @json_decode(file_get_contents("http://vimeo.com/api/oembed.json?url=".urlencode($raw)));
				if (!empty($oembed) AND empty($oembed['thumbnail_url']))
				{
					return $oembed['thumbnail_url'];
				}
				
				return FALSE;
			break;
			
			case 'default':
				return FALSE;
			break;
		}
		
		if ($echo) echo $output;
		
		return $output;
	}
}
?>
