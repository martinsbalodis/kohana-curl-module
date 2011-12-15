<?php defined('SYSPATH') or die('No direct script access.');

interface Kohana_Curl_MultiReady {
	
	public function executed();
	
    public function get_handle();
	
}
