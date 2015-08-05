<?php

namespace Chili\Command;

class ListCommand extends \Chili\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('list')
			->setDescription('Search dr.dk/tv')
			//->setHelp(file_get_contents(ROOT_DIR . '/Resources/CreateCommandHelp.text'))
			->addArgument('slug', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'slug');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$programcardList = $this->call('http://www.dr.dk/mu-online/api/1.2/list/' . urlencode($input->getArgument('slug')) . '?limit=500');

		if (is_array($programcardList['Items'])) {

			$table = new \Symfony\Component\Console\Helper\Table($output);
			$table->setHeaders(array('SeriesTitle', 'Subtitle', 'StartPublish', 'EndPublish', 'Duration', 'Target', 'Slug', 'New'));

			$output->writeln('');
			$progress = new \Symfony\Component\Console\Helper\ProgressBar($output, count($programcardList['Items']));
			$progress->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% (estimated: %estimated:-6s%)");
			$progress->setMessage('Downloading video info...');
			$progress->start();

			foreach ($programcardList['Items'] as $programcard) {

				$progress->setMessage('Downloading video info for: ' . $programcard['Slug']);
				$progress->setMessage($programcard['Slug'], 'filename');

				if (is_array($programcard['PrimaryAsset']) && $programcard['PrimaryAsset']['Kind'] == 'VideoResource' && is_string($programcard['PrimaryAsset']['Uri'])) {
					$binaryAssetList = $this->call($programcard['PrimaryAsset']['Uri']);

					$video_link = NULL;
					$current_bitrate = 0;
					$video_target = 'N/A';

					foreach ($binaryAssetList['Links'] as $binaryAssetLink) {
						if ($binaryAssetLink['FileFormat'] != 'mp4' || empty($binaryAssetLink['Uri'])) {
							continue;
						}

						if ($binaryAssetLink['Target'] == 'Download' && $binaryAssetLink['Bitrate'] > $current_bitrate) {
							$video_target = 'Download';
							$video_link = $binaryAssetLink['Uri'];
							$current_bitrate = $binaryAssetLink['Bitrate'];
						} else {
							if ($binaryAssetLink['Target'] == 'HLS') {
								$video_target = 'HLS';
								// The best we can get...
								$video_link = $binaryAssetLink['Uri'];
							}
						}
					}
					$duration = \Chili\Utility\General::formatMilliseconds($programcard['PrimaryAsset']['DurationInMilliseconds']);
				} else {
					$duration = 'N/A';
				}

				$table->addRow(array(
					$programcard['SeriesTitle'],
					$programcard['Subtitle'],
					date('d.m.y', strtotime($programcard['PrimaryAsset']['StartPublish'])),
					date('d.m.y', strtotime($programcard['PrimaryAsset']['EndPublish'])),
					$duration,
					$video_target,
					$programcard['Slug'],
					(\Chili\Utility\General::isDownloaded($programcard['Slug'])) ? 'No' : 'Yes'
				));

				if (is_string($video_link)) {
					$downloadLinks[$programcard['Slug']]['videoLink'] = $video_link;
					$downloadLinks[$programcard['Slug']]['programcard'] = $programcard;
				}
				$progress->advance();
			}

			$progress->setMessage('Downloading video info. Done...');
			$progress->finish();
			$output->writeln('');
			$table->render();

			$helper = $this->getHelper('question');
			$question = new \Symfony\Component\Console\Question\ConfirmationQuestion('Download files ?? [Y/n] ', TRUE);

			if (!$helper->ask($input, $output, $question)) {
				$output->writeln('No ?? ');
				return;
			}

			foreach ($downloadLinks as $slug => $download) {
				if (!\Chili\Utility\General::isDownloaded($slug)) {
					$output->writeln('Downloading: ' . $download['programcard']['SeriesTitle'] . ' - ' . (!empty($download['programcard']['Subtitle'])) ? $download['programcard']['Subtitle'] : $download['programcard']['Title']);

					$filename = getcwd() . '/';
					$filename .= $download['programcard']['SeriesTitle'] . '/';
					$filename .= $slug;

					if (!empty($download['programcard']['Subtitle'])) {
						$filename .= ' - ' . $download['programcard']['Subtitle'];
					} else {
						$filename .= ' - ' . $download['programcard']['Title'];
					}

					$filename .= '.mp4';
					$output->writeln('    Location: ' . dirname($filename));
					$output->writeln('    Filename: ' . basename($filename));
					$output->writeln('    Download: ' . $download['videoLink']);
					$output->writeln('');
					$output->writeln('');

					if (!is_dir(dirname($filename))) {
						mkdir(dirname($filename), 0777, TRUE);
					}

					$progress = new \Symfony\Component\Console\Helper\ProgressBar($output, 100);
					$progress->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% (estimated: %estimated:-6s%)\n %buffer%");
					$progress->setMessage('', 'buffer');
					$progress->setMessage('Download in progress.');
					$progress->start();

					if (preg_match('/\.mp4$/', $download['videoLink'])) {
						$fp = fopen($filename, 'w+');
						$ch = curl_init($download['videoLink']);
						curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
						curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($output, $progress, $download) {
							if ($download_size > 0) {
								$progress->setProgress(round($downloaded / $download_size * 100));
							}
						});
						curl_setopt($ch, CURLOPT_FILE, $fp);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
						curl_exec($ch);
						curl_close($ch);
						fclose($fp);
					} else {
						$process = new \Symfony\Component\Process\Process(sprintf(
							'ffmpeg -i "%s" -y -stats -v fatal -c copy -absf aac_adtstoasc "%s"',
							$link = $download['videoLink'],
							$filename
						));
						$process->setTimeout(null);

						$process->run(function ($type, $buffer) use ($output, $progress, $download) {
							$durationInMilliseconds = $download['programcard']['PrimaryAsset']['DurationInMilliseconds'];
							$pattern = '/([0-9]{2}:[0-9]{2}:[0-9]{2})/';
							preg_match_all($pattern, $buffer, $matches);

							$timeParts = explode(':', $matches[0][0]);
							$downloadedMilliseconds = ($timeParts[0] * 3600 + $timeParts[1] * 60 + $timeParts[2]) * 1000;
							$progress->setProgress(round($downloadedMilliseconds / $durationInMilliseconds * 100));
							$progress->setMessage($buffer, 'buffer');
						});
						$progress->setMessage('Done...', 'buffer');
						$progress->finish();
						$output->writeln('');

						// executes after the command finishes
						if (!$process->isSuccessful()) {
							throw new \RuntimeException($process->getErrorOutput());
						}
					}
					\Chili\Utility\General::addDownloaded($slug);
				}
			}
		         } else {
			$output->writeln('do some debugging');
		}

	}
}