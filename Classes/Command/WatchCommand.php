<?php

namespace Chili\Command;

class WatchCommand extends \Chili\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('watch')
			->setDescription('Manage watch list')
			->addOption('add', null, \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY | \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'Add Program slug to watch list')
			->addOption('rm', null, \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY | \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'Remove Program slug to watch list');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {

		if ($input->getOption('add') != array()) {
			$output->writeln('<info>Adding slugs:</info>');
			foreach ($input->getOption('add') as $programSlugs) {
				foreach (explode(',', $programSlugs) as $programSlug) {
					$output->writeln('  - ' . $programSlug);
					\Chili\Utility\General::addWatchListSlug($programSlug);
				}
			}
			$output->writeln('');
		}

		if ($input->getOption('rm') != array()) {
			$output->writeln('<info>Removing slugs:</info>');
			foreach ($input->getOption('rm') as $programSlugs) {
				foreach (explode(',', $programSlugs) as $programSlug) {
					$output->writeln('  - ' . $programSlug);
					\Chili\Utility\General::removeWatchListSlug($programSlug);
				}
			}
			$output->writeln('');
		}

	}
}

