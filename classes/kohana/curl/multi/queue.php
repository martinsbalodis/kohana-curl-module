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
	 * Maximum request per second made
	 * Set this to 0 if you don't wan't any limitations
	 * 
	 * Setting to 0 my cause server problems for the server 
	 * where are you downloading data
	 * 
	 * @var integer
	 */
	protected $request_limit = 3;
	
	/**
	 * List of timestamps when last n jobs added. A new job can be added to
	 * execution queue only when job execution per second satisfies 
	 * $request limit.
	 * 
	 * @var array 
	 */
	protected $previous_jobs_added = array();
	
	/**
	 * Jobs that are waiting in the line to be executed.
	 * @var type 
	 */
	protected $jobs_in_queue = array();
	
	public function add_job(Curl_MultiReady $curl) {
		
		// if current job execution queue is full then store it in waiting list
		if(count($this->jobs) < $this->handle_limit) {
			
			// Calculate whether this job can be added right now.
			// If the job cannot be added right now then wait until it can be 
			// added.
			if($this->request_limit) {
				
				$req_per_sec = $this->get_jobs_added_per_second();
				
				// current request per limit speed is too high.
				// job will be reinserted back in the list and executed later
				if ($req_per_sec > $this->request_limit) {
					$this->jobs_in_queue[] = $curl;
					return;
				}
				
			}
			
			// add most recent time when job added
			$this->previous_jobs_added[] = microtime(true);
			// remove most older time when job added if needed
			if(count($this->previous_jobs_added) > $this->request_limit) {
				array_shift($this->previous_jobs_added);
			}
			
			parent::add_job($curl);
			
		} 
		// Add jobs to wait in line
		else {
			
			$this->jobs_in_queue[] = $curl;
			
		}
	}
	
	/**
	 * Returns job count added per second if an extra job was added
	 * @return float 
	 */
	protected function get_jobs_added_per_second() {
		
		$first_time_started = current($this->previous_jobs_added);
				
		$execution_time = microtime(true)-$first_time_started;

		return (count($this->previous_jobs_added)+1)/$execution_time;
		
	}
	
	/**
	 * Add a job to queue 
	 */
	protected function next_job() {
		
		// only add if there is a place for a new job
		if(count($this->jobs) < $this->handle_limit) {
			
			$job = array_shift($this->jobs_in_queue);

			if($job !== null) {
				$this->add_job($job);
			}
			// no next job found
			else {
				$this->fetch_jobs();
			}
			
		}
		
	}
	
	/**
	 * Extend this function to create new jobs for execution.
	 * If there were no jobs to add you should do a sleep because this fetch 
	 * method could get called emmidiately after this again.
	 */
	protected function fetch_jobs() {
		
	}
	
	/**
	 * Running must not stop while there are jobs in queue 
	 */
	protected function is_running() {
		
		return !empty($this->jobs_in_queue);
		
	}
	
}
