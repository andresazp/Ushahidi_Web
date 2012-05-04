<?php defined('SYSPATH') or die('No direct script access.');
/**
 * This class handles GET request for KML via the API.
 *
 * @version 25 - Emmanuel Kala 2010-10-25
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

require_once Kohana::find_file('libraries/api', Kohana::config('config.extension_prefix').'Incidents_Api_Object');

class Admin_Reports_Api_Object extends Incidents_Api_Object {

	public function __construct($api_service)
	{
		parent::__construct($api_service);
	}

	/**
	 *  Handles admin report task requests submitted via the API service
	 */
	public function perform_task()
	{
		// Authenticate the user
		if (!$this->api_service->_login(TRUE))
		{
			$this->set_error_message($this->response(2));
			return;
		}

		// by request
		if ($this->api_service->verify_array_index($this->request, 'by'))
		{
			$this->_check_optional_parameters();
			
			$this->by = $this->request['by'];

			switch ($this->by)
			{
				case "approved" :
					$this->response_data = $this->_get_approved_reports();
					break;

				case "unapproved" :
					$this->response_data = $this->_get_unapproved_reports();
					break;

				case "verified" :
					$this->response_data = $this->_get_verified_reports();
					break;

				case "unverified" :
					$this->response_data = $this->_get_unverified_reports();
					break;
				
				case "incidentid":
					if ( ! $this->api_service->verify_array_index($this->request, 'id'))
					{
						$this->set_error_message(array(
							"error" => $this->api_service->get_error_msg(001, 'id')
						));
					}
					
					$where = array('i.id = '.$this->check_id_value($this->request['id']));
					$where['all_reports'] = TRUE;
					$this->response_data = $this->_get_incidents($where);
				break;
				
				case "all" :
					$this->response_data = $this->_get_all_reports();
					break;

				default :
					$this->set_error_message(array("error" => $this->api_service->get_error_msg(002)));
			}
			return;
		}

		//action request
		else if ($this->api_service->verify_array_index($this->request, 'action'))
		{
			$this->report_action();
			return;
		}
		else
		{
			$this->set_error_message(array("error" => $this->api_service->get_error_msg(001, 'by or action')));
			return;
		}
	}

	/**
	 * Handles report actions performed via the API service
	 */
	public function report_action()
	{
		$action = '';
		// Will hold the report action
		$incident_id = -1;
		// Will hold the ID of the incident/report to be acted upon

		// Authenticate the user
		if (!$this->api_service->_login())
		{
			$this->set_error_message($this->response(2));
			return;
		}

		// Check if the action has been specified
		if (!$this->api_service->verify_array_index($this->request, 'action'))
		{
			$this->set_error_message(array("error" => $this->api_service->get_error_msg(001, 'action')));

			return;
		}
		else
		{
			$action = $this->request['action'];
		}

		// Route report actions to their various handlers
		switch ($action)
		{
			// Delete report
			case "delete" :
				$this->_delete_report();
				break;

			// Approve report
			case "approve" :
				$this->_approve_report();
				break;

			// Verify report
			case "verify" :
				$this->_verify_report();
				break;

			// Edit report
			case "edit" :
				$this->_edit_report();
				break;

			default :
				$this->set_error_message(array("error" => $this->api_service->get_error_msg(002)));
		}
	}

	/**
	 * List unapproved reports
	 *
	 * @param string response - The response to return.XML or JSON
	 *
	 * @return array
	 */
	private function _get_unapproved_reports()
	{
		$where = array();
		$where['all_reports'] = TRUE;
		$where[] = "i.incident_active = 0";
		return $this->_get_incidents($where);
	}

	/**
	 * List first 15 reports
	 *
	 * @return array
	 */
	private function _get_all_reports()
	{
		$where = array();
		$where['all_reports'] = TRUE;
		return $this->_get_incidents($where);
	}

	/**
	 * List first 15 approved reports
	 *
	 * @return array
	 */
	private function _get_approved_reports()
	{
		return $this->_get_incidents();
	}

	/**
	 * List first 15 approved reports
	 *
	 * @return array
	 */
	private function _get_verified_reports()
	{
		$where = array();
		$where['all_reports'] = TRUE;
		$where[] = 'i.incident_verified = 1';
		return $this->_get_incidents($where);
	}

	/**
	 * List first 15 approved reports
	 *
	 * @param string response_type - The response type to return XML or JSON
	 *
	 * @return array
	 */
	private function _get_unverified_reports()
	{
		$where = array();
		$where['all_reports'] = TRUE;
		$where[] = 'i.incident_verified = 0';
		return $this->_get_incidents($where);
	}

	/**
	 * Edit existing report
	 *
	 * @return array
	 */
	public function _edit_report()
	{
		print $this->_submit_report();

	}

	/**
	 * Delete existing report
	 *
	 * @param int incident_id - the id of the report to be deleted.
	 */
	private function _delete_report()
	{
		$form = array('incident_id' => '', );

		$ret_value = 0;
		// Return error value; start with no error

		$errors = $form;

		if ($_POST)
		{
			$post = Validation::factory($_POST);

			//  Add some filters
			$post->pre_filter('trim', TRUE);

			// Add some rules, the input field, followed by a list
			// of checks, carried out in order
			$post->add_rules('incident_id', 'required', 'numeric');

			if ($post->validate())
			{
				$incident_id = $post->incident_id;
				$update = new Incident_Model($incident_id);

				if ($update->loaded == true)
				{
					//$incident_id = $update->id;
					$location_id = $update->location_id;
					$update->delete();

					// Delete Location
					ORM::factory('location')->where('id', $location_id)->delete_all();

					// Delete Categories
					ORM::factory('incident_category')->where('incident_id', $incident_id)->delete_all();

					// Delete Translations
					ORM::factory('incident_lang')->where('incident_id', $incident_id)->delete_all();

					// Delete Photos From Directory
					foreach (ORM::factory('media')->where('incident_id',
					$incident_id)->where('media_type', 1) as $photo)
					{
						$this->delete_photo($photo->id);
					}

					// Delete Media
					ORM::factory('media')->where('incident_id', $incident_id)->delete_all();

					// Delete Sender
					ORM::factory('incident_person')->where('incident_id', $incident_id)->delete_all();

					// Delete relationship to SMS message
					$updatemessage = ORM::factory('message')->where('incident_id', $incident_id)->find();

					if ($updatemessage->loaded == true)
					{
						$updatemessage->incident_id = 0;
						$updatemessage->save();
					}

					// Delete Comments
					ORM::factory('comment')->where('incident_id', $incident_id)->delete_all();

				}
			}
			else
			{
				//TODO i18nize the string
				$this->error_messages .= "Incident ID is required.";
				$ret_value = 1;
			}
		}
		else
		{
			$ret_value = 3;
		}

		// Set the reponse info to be sent back to client
		$this->response_data = $this->response($ret_value, $this->error_messages);

	}

	/**
	 * Approve / unapprove an existing report
	 *
	 * @param int report_id - the id of the report to be approved.
	 *
	 * @return
	 */
	private function _approve_report()
	{
		$form = array('incident_id' => '', );

		$errors = $form;

		$ret_value = 0;
		// will hold the return value

		if ($_POST)
		{
			$post = Validation::factory($_POST);

			//  Add some filters
			$post->pre_filter('trim', TRUE);

			// Add some rules, the input field, followed by a list
			// of checks, carried out in order
			$post->add_rules('incident_id', 'required', 'numeric');

			if ($post->validate())
			{
				$incident_id = $post->incident_id;
				$update = new Incident_Model($incident_id);

				if ($update->loaded == true)
				{
					if ($update->incident_active == 0)
					{
						$update->incident_active = '1';
					}
					else
					{
						$update->incident_active = '0';
					}

					// Tag this as a report that needs to be sent
					// out as an alert
					if ($update->incident_alert_status != '2')
					{
						// 2 = report that has had an alert sent
						$update->incident_alert_status = '1';
					}

					$update->save();
					$verify = new Verify_Model();
					$verify->incident_id = $incident_id;
					$verify->verified_status = '0';
					$verify->user_id = $_SESSION['auth_user']->id;
					// Record 'Verified By' Action
					$verify->verified_date = date("Y-m-d H:i:s", time());
					$verify->save();

				}
				else
				{
					//TODO i18nize the string
					//couldin't approve the report
					$this->error_messages .= "Couldn't approve the report id " . $post->incident_id;
					$ret_value = 1;
				}

			}
			else
			{
				//TODO i18nize the string
				$this->error_messages .= "Incident ID is required.";
				$ret_value = 1;
			}

		}
		else
		{
			$ret_value = 3;
		}

		// Set the response data
		$this->response_data = $this->response($ret_value, $this->error_messages);

	}

	/**
	 * Verify or unverify an existing report
	 * @param int report_id - the id of the report to be verified
	 * unverified.
	 */
	private function _verify_report()
	{
		$form = array('incident_id' => '', );

		$ret_value = 0;
		// Will hold the return value; start off with a "no error" value

		if ($_POST)
		{
			$post = Validation::factory($_POST);

			//  Add some filters
			$post->pre_filter('trim', TRUE);

			// Add some rules, the input field, followed by a list of
			//checks, carried out in order
			$post->add_rules('incident_id', 'required', 'numeric');

			if ($post->validate())
			{
				$incident_id = $post->incident_id;
				$update = new Incident_Model($incident_id);

				if ($update->loaded == true)
				{
					if ($update->incident_verified == '1')
					{
						$update->incident_verified = '0';
						$verify->verified_status = '0';
					}
					else
					{
						$update->incident_verified = '1';
						$verify->verified_status = '2';
					}
					$update->save();

					$verify = new Verify_Model();
					$verify->incident_id = $incident_id;
					$verify->user_id = $_SESSION['auth_user']->id;
					// Record 'Verified By' Action
					$verify->verified_date = date("Y-m-d H:i:s", time());
					$verify->save();

				}
				else
				{
					//TODO i18nize the string
					$this->error_messages .= "Could not verify this report " . $post->incident_id;
					$ret_value = 1;
				}

			}
			else
			{
				//TODO i18nize the string
				$this->error_messages .= "Incident ID is required.";
				$ret_value = 1;
			}

		}
		else
		{
			$ret_value = 3;
		}

		$this->response_data = $this->response($ret_value, $this->error_messages);

	}

	/**
	 * The actual reporting -
	 *
	 * @return int
	 */
	private function _submit_report()
	{
		// setup and initialize form field names
		$form = array('location_id' => '', 'incident_id' => '', 'incident_title' => '', 'incident_description' => '', 'incident_date' => '', 'incident_hour' => '', 'incident_minute' => '', 'incident_ampm' => '', 'latitude' => '', 'longitude' => '', 'location_name' => '', 'country_id' => '', 'incident_category' => '', 'incident_news' => array(), 'incident_video' => array(), 'incident_photo' => array(), 'person_first' => '', 'person_last' => '', 'person_email' => '', 'incident_active ' => '', 'incident_verified' => '');

		$errors = $form;

		// check, has the form been submitted, if so, setup validation
		if ($_POST)
		{
			// Instantiate Validation, use $post, so we don't overwrite $_POST fields with our own things
			$post = Validation::factory(array_merge($_POST, $_FILES));

			//  Add some filters
			$post->pre_filter('trim', TRUE);

			// Add some rules, the input field, followed by a list of
			//checks, carried out in order
			$post->add_rules('location_id', 'numeric');
			$post->add_rules('incident_id', 'required', 'numeric');
			$post->add_rules('incident_title', 'required', 'length[3,200]');
			$post->add_rules('incident_description', 'required');
			$post->add_rules('incident_date', 'required', 'date_mmddyyyy');
			$post->add_rules('incident_hour', 'required', 'between[0,23]');

			if ($this->api_service->verify_array_index($_POST, 'incident_ampm'))
			{
				if ($_POST['incident_ampm'] != "am" && $_POST['incident_ampm'] != "pm")
				{
					$post->add_error('incident_ampm', 'values');
				}
			}

			$post->add_rules('latitude', 'required', 'between[-90,90]');
			$post->add_rules('longitude', 'required', 'between[-180,180]');
			$post->add_rules('location_name', 'required', 'length[3,200]');
			$post->add_rules('incident_category', 'required', 'length[1,100]');

			// Validate Personal Information
			if (!empty($post->person_first))
			{
				$post->add_rules('person_first', 'length[3,100]');
			}

			if (!empty($post->person_last))
			{
				$post->add_rules('person_last', 'length[3,100]');
			}

			if (!empty($post->person_email))
			{
				$post->add_rules('person_email', 'email', 'length[3,100]');
			}

			$post->add_rules('incident_active', 'required', 'between[0,1]');
			$post->add_rules('incident_verified', 'required', 'length[0,1]');

			// Test to see if things passed the rule checks
			if ($post->validate())
			{
				$incident_id = $post->incident_id;
				$location_id = $post->location_id;
				// SAVE INCIDENT

				// SAVE LOCATION (***IF IT DOES NOT EXIST***)
				$location = new Location_Model($location_id);
				$location->location_name = $post->location_name;
				$location->latitude = $post->latitude;
				$location->longitude = $post->longitude;
				$location->location_date = date("Y-m-d H:i:s", time());
				$location->save();

				$incident = new Incident_Model($incident_id);
				$incident->location_id = $location->id;
				$incident->user_id = 0;
				$incident->incident_title = $post->incident_title;
				$incident->incident_description = $post->incident_description;

				$incident_date = explode("/", $post->incident_date);
				/**
				 * where the $_POST['date'] is a value posted by form in
				 * mm/dd/yyyy format
				 */
				$incident_date = $incident_date[2] . "-" . $incident_date[0] . "-" . $incident_date[1];

				$incident_time = $post->incident_hour . ":" . $post->incident_minute . ":00 " . $post->incident_ampm;
				$incident->incident_date = date("Y-m-d H:i:s", strtotime($incident_date . " " . $incident_time));
				$incident->incident_datemodify = date("Y-m-d H:i:s", time());
				// Incident Evaluation Info
				$incident->incident_active = $post->incident_active;
				$incident->incident_verified = $post->incident_verified;

				$incident->save();

				// Record Approval/Verification Action
				$verify = new Verify_Model();
				$verify->incident_id = $incident->id;
				$verify->user_id = $_SESSION['auth_user']->id;
				// Record 'Verified By' Action
				$verify->verified_date = date("Y-m-d H:i:s", time());

				if ($post->incident_active == 1)
				{
					$verify->verified_status = '1';
				}
				elseif ($post->incident_verified == 1)
				{
					$verify->verified_status = '2';
				}
				elseif ($post->incident_active == 1 && $post->incident_verified == 1)
				{
					$verify->verified_status = '3';
				}
				else
				{
					$verify->verified_status = '0';
				}
				$verify->save();

				// SAVE CATEGORIES
				//check if data is csv or a single value.
				$pos = strpos($post->incident_category, ",");
				if ($pos === false)
				{
					//for backward compactibility. will drop support for it in the future.
					if (@unserialize($post->incident_category))
					{
						$categories = unserialize($post->incident_category);
					}
					else
					{
						$categories = array($post->incident_category);
					}
				}
				else
				{
					$categories = explode(",", $post->incident_category);
				}

				if (!empty($categories) AND is_array($categories))
				{
					// STEP 3: SAVE CATEGORIES
					ORM::factory('Incident_Category')->where('incident_id', $incident->id)->delete_all();
					// Delete Previous Entries
					foreach ($categories as $item)
					{
						$incident_category = new Incident_Category_Model();
						$incident_category->incident_id = $incident->id;
						$incident_category->category_id = $item;
						$incident_category->save();
					}
				}

				// STEP 4: SAVE MEDIA
				// a. News
				if (!empty($post->incident_news) && is_array($post->incident_news))
				{
					ORM::factory('Media')->where('incident_id', $incident->id)->where('media_type <> 1')->delete_all();
					// Delete Previous Entries

					foreach ($post->incident_news as $item)
					{
						if (!empty($item))
						{
							$news = new Media_Model();
							$news->location_id = $location->id;
							$news->incident_id = $incident->id;
							$news->media_type = 4;
							// News
							$news->media_link = $item;
							$news->media_date = date("Y-m-d H:i:s", time());
							$news->save();
						}
					}
				}

				// b. Video
				if (!empty($post->incident_video) && is_array($post->incident_video))
				{

					foreach ($post->incident_video as $item)
					{
						if (!empty($item))
						{
							$video = new Media_Model();
							$video->location_id = $location->id;
							$video->incident_id = $incident->id;
							$video->media_type = 2;
							// Video
							$video->media_link = $item;
							$video->media_date = date("Y-m-d H:i:s", time());
							$video->save();
						}
					}
				}

				// c. Photos
				if (!empty($post->incident_photo))
				{
					$filenames = upload::save('incident_photo');
					$i = 1;
					foreach ($filenames as $filename)
					{
						$new_filename = $incident->id . "_" . $i . "_" . time();

						// Resize original file... make sure its max 408px wide
						Image::factory($filename)->resize(408, 248, Image::AUTO)->save(Kohana::config('upload.directory', TRUE) . $new_filename . ".jpg");

						// Create thumbnail
						Image::factory($filename)->resize(70, 41, Image::HEIGHT)->save(Kohana::config('upload.directory', TRUE) . $new_filename . "_t.jpg");

						// Remove the temporary file
						unlink($filename);

						// Save to DB
						$photo = new Media_Model();
						$photo->location_id = $location->id;
						$photo->incident_id = $incident->id;
						$photo->media_type = 1;
						// Images
						$photo->media_link = $new_filename . ".jpg";
						$photo->media_thumb = $new_filename . "_t.jpg";
						$photo->media_date = date("Y-m-d H:i:s", time());
						$photo->save();
						$i++;
					}
				}

				// SAVE PERSONAL INFORMATION IF ITS FILLED UP
				if (!empty($post->person_first) OR !empty($post->person_last))
				{
					ORM::factory('Incident_Person')->where('incident_id', $incident->id)->delete_all();
					$person = new Incident_Person_Model();
					$person->incident_id = $incident->id;
					$person->person_first = $post->person_first;
					$person->person_last = $post->person_last;
					$person->person_email = $post->person_email;
					$person->person_date = date("Y-m-d H:i:s", time());
					$person->save();
				}

				return $this->response(0);
				//success

			}
			else
			{
				// populate the error fields, if any
				$errors = arr::overwrite($errors, $post->errors('report'));

				foreach ($errors as $error_item => $error_description)
				{
					if (!is_array($error_description))
					{
						$this->error_messages .= $error_description;
						if ($error_description != end($errors))
						{
							$this->error_messages .= " - ";
						}
					}
				}

				//FAILED!!! //validation error
				return $this->response(1, $this->error_messages);
			}
		}
		else
		{
			// Not sent by post method.
			return $this->response(3);

		}
	}

	/**
	 * Delete Photo
	 * @param int $id The unique id of the photo to be deleted
	 */
	private function delete_photo($id)
	{
		$auto_render = FALSE;
		$template = "";

		if ($id)
		{
			$photo = ORM::factory('media', $id);
			$photo_large = $photo->media_link;
			$photo_thumb = $photo->media_thumb;

			// Delete Files from Directory
			if (!empty($photo_large))
			{
				unlink(Kohana::config('upload.directory', TRUE) . $photo_large);
			}

			if (!empty($photo_thumb))
			{
				unlink(Kohana::config('upload.directory', TRUE) . $photo_thumb);
			}

			// Finally Remove from DB
			$photo->delete();
		}
	}

}
