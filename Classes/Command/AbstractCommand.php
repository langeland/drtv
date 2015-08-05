<?php

namespace Chili\Command;

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

}