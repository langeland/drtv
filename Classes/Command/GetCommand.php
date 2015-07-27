<?php

namespace Chili\Command;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class GetCommand extends \Chili\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('get')
			->setDescription('Search dr.dk/tv')
			//->setHelp(file_get_contents(ROOT_DIR . '/Resources/CreateCommandHelp.text'))
			->addArgument('slug', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'slug');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$formatter = $this->getHelper('formatter');



		$programcard = $this->call('https://www.dr.dk/mu-online/api/1.2/programcard/' . urlencode($input->getArgument('slug')));


		if(is_array($programcard['PrimaryAsset'])) {
			$output->writeln('PrimaryAsset found');
			$output->writeln('Treat as ProgramcardSlug');

		} else {
			$output->writeln('PrimaryAsset found');
			$output->writeln('Treat as SeriesSlug');
		}

		$output->writeln(print_r($programcard,1));

	}
}

