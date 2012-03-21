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
		
		$active = true;
		
		// Execute while there is a job to be executed
		while($active || !empty($this->jobs) || $this->is_running()) {
			
//			Before version 7.20.0: If you receive CURLM_CALL_MULTI_PERFORM, 
//			this basically means that you should call curl_multi_perform again,
//			before you select() on more actions. You don't have to do it
//			immediately, but the return code means that libcurl may have more 
//			data available to return or that there may be more data to send off 
//			before it is "satisfied". Do note that curl_multi_perform(3) will 
//			return CURLM_CALL_MULTI_PERFORM only when it wants to be called 
//			again immediately. When things are fine and there is nothing
//			immediate it wants done, it'll return CURLM_OK and you need to wait
//			for "action" and then call this function again.
//
//			This function only returns errors etc regarding the whole multi
//			stack. Problems still might have occurred on individual transfers
//			even when this function returns CURLM_OK.
			
			// Run the sub-connections of the current cURL handle
			do {
				$mrc = curl_multi_exec($this->mch, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);

			if ($mrc !== CURLM_OK) {
				throw new Kohana_Exception("Curl ERROR-" . $mrc);
			}
			
			// Read execution statuses.
			while ($info = curl_multi_info_read($this->mch)) {

				// Find corresponding resource. 
				foreach ($this->jobs as $job) {

					/* @var $job Curl_MultiReady */
					if ($job->get_handle() === $info['handle']) {

						// handle found
						$this->job_executed($job);

						break;
					}
				}
			}
			
			// Adds new jobs for execution
			$this->next_job();
			
			// free time
			usleep(5e2);
			
		}
	}
	
	/**
	 * Extend this to add new jobs when one has executed. 
	 * @param Curl_MultiReady $job 
	 */
	protected function job_executed(Curl_MultiReady $job) {
		
		// remove job from execution stack
		$this->remove_job($job);
		
		// trigger event that job is executed
		$job->executed();
		
	}
	
	/**
	 * Override this method to add new jobs to queue when there is place
	 */
	protected function next_job() {
		
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
	
	/**
	 * Extend this function to infinite job execution loop even when there is
	 * nothing to execute.
	 */
	protected function is_running() {
		
		return false;
		
	}
}
