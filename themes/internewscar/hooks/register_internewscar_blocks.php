<?php defined('SYSPATH') or die('No direct script access.');

class internewscar_reports_block {

	public function __construct()
	{
		$block = array(
			"classname" => "internewscar_reports_block",
			"name" => "Reports (RCA)",
			"description" => "List the 10 latest reports in the system"
		);

		blocks::register($block);
	}

	public function block()
	{
		$content = new View('blocks/internewscar_main_reports');

		// Get Reports
		// XXX: Might need to replace magic no. 8 with a constant
		$content->total_items = ORM::factory('incident')
			->where('incident_active', '1')
			->limit('8')->count_all();
		$content->incidents = ORM::factory('incident')
			->select('DISTINCT incident.id')->select('incident.*')->select('form_response AS source')
			->join('form_response', 'form_response.incident_id', 'incident.id', 'LEFT')
			->join('form_field', 'form_field.id', 'form_response.form_field_id', 'LEFT')
			->where('incident_active', '1')
			//->where("form_response != ''") // For testing: Only return forms with source
			->like('field_name',"Source d'Information (Media)")
			->limit('10')
			->orderby('incident_date', 'desc')
			->find_all();

		echo $content;
	}
}

new internewscar_reports_block;
