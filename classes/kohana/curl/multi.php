<?php defined('SYSPATH') or die('No direct script access.');

/**
 * php multi curl class.
 * @author   Martins Balodis <martins256@gmail.com>
 * @category Curl_Base_Class
 * @package  Curl
 * @license  http://www.opensource.org/licenses/mit-license.php MIT License
 * @link     https://github.com/martinsbalodis/php-curl-multi-oop
 */
Class Kohana_Curl_Multi {
	
	/**
	 * Multi Curl Handle
	 * @var resource
	 */
	protected $mch;

	/**
	 * Curl jobs to execute or being executed
	 * @var Curl[]
	 */
	protected $jobs = array();

	/**
	 * Add new job
	 * @param Curl $curl
	 */
	public function add_job(Curl_MultiReady $curl) {
		
		$success = curl_multi_add_handle($this->mch, $curl->get_handle());

		if($success!==0) {
			throw new Kohana_Exception("Failed to add cURL handle 
				to multi cURL. error code ".$success);
		}
		
		$this->jobs[] = $curl;
		
	}
	
	/**
	 * Constructor. Initializes multi curl handle
	 */
	public function __construct() {
		$this->mch = curl_multi_init();
		curl_multi_select($this->mch);
	}

	/**
	 * Execute the handles
	 */
	public function exec() {
		
		// Multi curl is active
		$active = null;
		
		// execute the handles
		do {
			$mrc = curl_multi_exec($this->mch, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		
		if($mrc != CURLM_OK) {
			throw new Exception('Something went wrong!');
		}
		
		// Execute the handles
		while ($active && $mrc == CURLM_OK) {
			
			// Wait for activity on any curl_multi connection
			if (curl_multi_select($this->mch) != -1) {
				do {
					
					// Run the sub-connections of the current cURL handle
					$mrc = curl_multi_exec($this->mch, $active);
					
					// Read execution statuses.
					while ($info = curl_multi_info_read($this->mch, $msgs_in_queue)) {

						// Find corresponding resource. 
						foreach($this->jobs as $job) {

							/* @var $job Curl_MultiReady */
							if($job->get_handle() === $info['handle']) {

								// handle found
								$this->job_executed($job);

								break;
							}
						}
					}
					
					// @TODO add new jobs from queue

				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
			else {
				throw new Exception('cURL select failure or timeout.');
			}
		}
	}
	
	/**
	 * Extend this to add new jobs when one has executed. 
	 * @param Curl_MultiReady $job 
	 */
	protected function job_executed(Curl_MultiReady $job) {
		
		$job->executed();
		
	}
	
	/**
	 * Remove job from current job execution queue
	 * @param Curl_MultiReady $job 
	 */
	protected function remove_job(Curl_MultiReady $job) {
		
		$job_key = array_search($job, $this->jobs, true);
		
		if($job_key === false) {
			throw new Kohana_Exception("Job not found it current execution list");
		}
		
		unset($this->jobs[$job_key]);
		
		curl_multi_remove_handle($this->mch, $job->get_handle());
		
	}
}
