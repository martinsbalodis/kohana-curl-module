<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Class for executing jobs sequentaly
 * @author   Martins Balodis <martins256@gmail.com>
 * @category Curl_Base_Class
 * @package  Curl
 * @license  http://www.opensource.org/licenses/mit-license.php MIT License
 * @link     https://github.com/martinsbalodis/php-curl-multi-oop
 */
class Kohana_Curl_Multi_Queue extends Curl_Multi {
	
	/**
	 * Multi Curl Handle
	 * @var resource
	 */
	protected $mch;
	
	/**
	 * Limit of handles being executed at the same time
	 * @var integer
	 */
	protected $handle_limit = 5;
	
	/**
	 * Jobs that are waiting in the line to be executed.
	 * @var type 
	 */
	protected $jobs_in_queue = array();
	
	public function add_job(Curl_MultiReady $curl) {
		
		// if current job execution queue is full then store it in waiting list
		if(count($this->jobs) < $this->handle_limit) {
			
			parent::add_job($curl);
			
		} 
		// Add jobs to wait in line
		else {
			
			$this->jobs_in_queue[] = $curl;
			
		}
	}
	
	protected function next_job() {
		
		$job = array_shift($this->jobs_in_queue);
		
		if($job !== null) {
			$this->add_job($job);
		}
		// no next job found
		else {
			$this->fetch_jobs();
		}
		
	}
	
	/**
	 * Extend this function to create new jobs for execution 
	 */
	protected function fetch_jobs() {
		
	}
	
	/**
	 * This function is called when a job is executed by MCH
	 * @param Curl_MultiReady $job 
	 */
	protected function job_executed(Curl_MultiReady $job) {
		
		// remove job from execution stack
		$this->remove_job($job);
		
		// trigger execution event on curl object
		// curl object can reinsert itself in job execution queue
		parent::job_executed($job);
		
		// add next job to execution stack
		$this->next_job();
	}
	
}
