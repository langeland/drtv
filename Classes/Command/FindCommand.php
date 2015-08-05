<?php

namespace Chili\Command;

class FindCommand extends \Chili\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('find')
			->setDescription('Search dr.dk/tv')
			//->setHelp(file_get_contents(ROOT_DIR . '/Resources/CreateCommandHelp.text'))
			->addArgument('term', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Search term');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {

		$this->input = $input;
		$this->output = $output;

		$formatter = $this->getHelper('formatter');

		$data = $this->call('https://www.dr.dk/mu-online/api/1.2/list/view/quicksearch/' . urlencode($input->getArgument('term')) . '?limitprograms=75&limitepisodes=75');

		foreach ($data as $block => $blockItems) {
			if (!count($blockItems)) {
				continue;
			}

			$formattedLine = $formatter->formatSection($block, '');
			$output->writeln($formattedLine);

			$table = new \Symfony\Component\Console\Helper\Table($output);
			$table->setHeaders(array('SeriesTitle', 'SeriesSlug', 'ProgramcardTitle', 'ProgramcardSlug'));

			foreach ($blockItems as $item) {
				$table->addRow(array_values($item));
			}

			$table->render();
		}
	}
}