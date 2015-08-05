<?php

namespace Chili\Command;

class FetchCommand extends \Chili\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('fetch')
			->setDescription('Fetch video from dr.dk/tv');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$watchList = \Chili\Utility\General::getWatchList();
		if (!$watchList == array()) {

			$output->writeln('Downloading program cards');
			$output->writeln('');
			// create a new progress bar
			$progress = new \Symfony\Component\Console\Helper\ProgressBar($output, count($watchList));
			$progress->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% (estimated: %estimated:-6s%)");
			$progress->setMessage('Connecting...');

			// start and displays the progress bar
			$progress->start();

			$downloadList = array();
			foreach ($watchList as $seriesSlug) {
				$programcardList = \Chili\Utility\WebService::call('http://www.dr.dk/mu-online/api/1.2/list/' . urlencode($seriesSlug) . '?limit=500');
				foreach ($programcardList['Items'] as $programcard) {
					$progress->setMessage('Downloading: ' . $programcardList['Title'] . ' - ' . $programcard['Slug']);
					$progress->display();
					if (is_array($programcard['PrimaryAsset']) && !\Chili\Utility\General::isDownloaded($programcard['Slug'])) {
						$downloadList[$programcard['Slug']] = $programcard;
					}
				}
				// advance the progress bar 1 unit
				$progress->advance();
			}
			// ensure that the progress bar is at 100%
			$progress->finish();

			/**************************************************************************************
			 * Listing what to download
			 *************************************************************************************/
			$table = new \Symfony\Component\Console\Helper\Table($output);
			$tableHeaders = array('Title', 'StartPublish', 'EndPublish', 'Duration', 'Slug');
			$table->setHeaders($tableHeaders);
			$lastProgramcard = array();

			foreach ($downloadList as $programcard) {
				if ($lastProgramcard == array() || $programcard['SeriesSlug'] != $lastProgramcard['SeriesSlug']) {

					if (!$lastProgramcard == array()) {
						$table->addRows(array(
							new \Symfony\Component\Console\Helper\TableSeparator()
						));
					}

					$table->addRows(array(
						array(
							new \Symfony\Component\Console\Helper\TableCell(
								sprintf('<info>%s (%s)</info>', $programcard['SeriesTitle'], $programcard['SeriesSlug']),
								array('colspan' => count($tableHeaders))
							)
						),
						new \Symfony\Component\Console\Helper\TableSeparator()
					));
				}

				$table->addRow(array(
					sprintf('%s - %s', $programcard['Title'], $programcard['Subtitle']),
					date('d.m.y', strtotime($programcard['PrimaryAsset']['StartPublish'])),
					date('d.m.y', strtotime($programcard['PrimaryAsset']['EndPublish'])),
					\Chili\Utility\General::formatMilliseconds($programcard['PrimaryAsset']['DurationInMilliseconds']),
					$programcard['Slug']
				));

				$finalList[$programcard['Slug']]['programcard'] = $programcard;

				$lastProgramcard = $programcard;
			}
			$output->writeln('');
			$table->render();

			/**************************************************************************************
			 *
			 *************************************************************************************/
			$helper = $this->getHelper('question');
			$question = new \Symfony\Component\Console\Question\ConfirmationQuestion('Download files ?? [Y/n] ', TRUE);

			if (!$helper->ask($input, $output, $question)) {
				$output->writeln('No ?? ');
				return;
			}

			/**************************************************************************************
			 *
			 *************************************************************************************/
			$downloader = new \Chili\Utility\Downloader($this->input, $this->output);
			foreach ($finalList as $slug => $data) {
				$downloader->get($slug, $data);
			}
		}

	}
}

