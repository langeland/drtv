<?php

namespace Chili\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class AbstractCommand extends \Symfony\Component\Console\Command\Command {

	/**
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	protected $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	public function __construct() {
		parent::__construct();
	}


	protected function call($url){

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$response = curl_exec($ch);

		// Check if any error occurred
		if(curl_errno($ch)) {
		  echo 'Curl error: ' . curl_error($ch);
		  exit;
		}

		// TODO: json_decode error check
		$data = json_decode($response, TRUE);

		curl_close($ch);

		return $data;
	}

}