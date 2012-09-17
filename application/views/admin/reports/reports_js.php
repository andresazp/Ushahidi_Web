/**
 * Main reports js file.
 * 
 * Handles javascript stuff related to reports function.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     API Controller
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

<?php require SYSPATH.'../application/views/admin/utils_js.php' ?>

		// Ajax Submission
		function reportAction ( action, confirmAction, incident_id )
		{
			var statusMessage;
			if( !isChecked( "incident" ) && incident_id=='' )
			{ 
				alert('Please select at least one report.');
			} else {
				var answer = confirm('<?php echo Kohana::lang('ui_admin.are_you_sure_you_want_to'); ?> ' + confirmAction + '?')
				if (answer){
					
					// Set Submit Type
					$("#action").attr("value", action);
					
					if (incident_id != '') 
					{
						// Submit Form For Single Item
						$("#incident_single").attr("value", incident_id);
						$("#reportMain").submit();
					}
					else
					{
						// Set Hidden form item to 000 so that it doesn't return server side error for blank value
						$("#incident_single").attr("value", "000");
						
						// Submit Form For Multiple Items
						$("#reportMain").submit();
					}
				
				} else {
					return false;
				}
			}
		}
		
		function showLog(id)
		{
			$('#' + id).toggle(400);
		}

$(function () {
	$(".tabset .search").click(function() {
		if ($('.search-tab').hasClass('active'))
		{
			$(".search-tab").removeClass('active').slideUp(300, function() { $(".action-tab").slideDown().addClass('active'); });
		}
		else
		{
			$(".action-tab").removeClass('active').slideUp(300, function() { $(".search-tab").slideDown().addClass('active'); });
		}
		
		return false;
	});
	
	// Category treeview
	$(".category-column").treeview({
	  persist: "location",
	  collapsed: true,
	  unique: false
	});
});
	
	
// Map reference
var map = null;
var latitude = <?php echo Kohana::config('settings.default_lat') ?>;
var longitude = <?php echo Kohana::config('settings.default_lon'); ?>;
var zoom = <?php echo Kohana::config('settings.default_zoom'); ?>;

jQuery(window).load(function(){
		
		// OpenLayers uses IE's VML for vector graphics
		// We need to wait for IE's engine to finish loading all namespaces (document.namespaces) for VML.
		// jQuery.ready is executing too soon for IE to complete it's loading process.
		
		<?php echo map::layers_js(FALSE); ?>
		var mapConfig = {

			// Map center
			center: {
				latitude: latitude,
				longitude: longitude,
			},

			// Zoom level
			zoom: zoom,

			// Base layers
			baseLayers: <?php echo map::layers_array(FALSE); ?>
		};

		map = new Ushahidi.Map('divMap', mapConfig);
		map.addRadiusLayer({
			latitude: latitude,
			longitude: longitude
		});

		// Subscribe to makerpositionchanged event
		map.register("markerpositionchanged", function(coords){
			$("#alert_lat").val(coords.latitude);
			$("#alert_lon").val(coords.longitude);
		});

		$('.btn_find').on('click', function () {
			geoCode();
		});

		$('#location_find').bind('keypress', function(e) {
			var code = (e.keyCode ? e.keyCode : e.which);
			if(code == 13) { //Enter keycode
				geoCode();
				return false;
			}
		});
});

