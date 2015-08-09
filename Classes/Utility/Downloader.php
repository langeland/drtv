<?php

namespace Chili\Utility;

class Downloader {

	/**
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	protected $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	/**
	 * Downloader constructor.
	 */
	public function __construct(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
	}

	public function get($slug, $data = array()) {

		$this->output->writeln(sprintf('<info>Downloading slug: %s</info>', $slug));

		/**************************************************************************************
		 * Fetch missing data
		 *************************************************************************************/
		if (!array_key_exists('programcard', $data)) {
			$this->output->writeln('  Downloading programcard.');
			$data['programcard'] = \Chili\Utility\WebService::call('https://www.dr.dk/mu-online/api/1.2/programcard/' . urlencode($slug));
		}

		if (!array_key_exists('bar', $data)) {
			$this->output->writeln('  Downloading binary asset.');
			$data['bar'] = \Chili\Utility\WebService::call($data['programcard']['PrimaryAsset']['Uri']);
		}

		/**************************************************************************************
		 * Resolving best video target
		 *************************************************************************************/
		$this->output->writeln('  Resolving best video target..');
		$video_link = NULL;
		$current_bitrate = 0;

		$video_target = 'N/A';

		foreach ($data['bar']['Links'] as $binaryAssetLink) {
			if ($binaryAssetLink['FileFormat'] != 'mp4' || empty($binaryAssetLink['Uri'])) {
				continue;
			}

			if ($binaryAssetLink['Target'] == 'Download' && $binaryAssetLink['Bitrate'] > $current_bitrate) {
				$video_target = 'Download';
				$video_link = $binaryAssetLink['Uri'];
				$current_bitrate = $binaryAssetLink['Bitrate'];
			} elseif ($binaryAssetLink['Target'] == 'HLS' && $video_target != 'Download') {
				$video_target = 'HLS';
				// The best we can get...
				$video_link = $binaryAssetLink['Uri'];
			}
		}

		if (is_null($video_link)) {
			throw new \Exception('Unable to resolve useble video target for ' . $slug);
		}

		/**************************************************************************************
		 * Resolving file name
		 *************************************************************************************/

		$filename = getcwd() . '/';
		$filename .= $data['programcard']['SeriesTitle'] . '/';
		$filename .= $slug;

		if (!empty($data['programcard']['Subtitle'])) {
			$filename .= ' - ' . $data['programcard']['Subtitle'];
		} else {
			$filename .= ' - ' . $data['programcard']['Title'];
		}
		$filename .= '.mp4';

		/**************************************************************************************
		 *
		 *************************************************************************************/
		$table = new \Symfony\Component\Console\Helper\Table($this->output);
		$table->addRows(array(
			array('Location', dirname($filename)),
			array('Filename', basename($filename)),
			array('Download', $video_link),
			array('Tideo target', $video_target)
		));
		$table->render();
		$this->output->writeln('');
		$this->output->writeln('');

		/**************************************************************************************
		 *
		 *************************************************************************************/
		if (!is_dir(dirname($filename))) {
			mkdir(dirname($filename), 0777, TRUE);
		}

		$progress = new \Symfony\Component\Console\Helper\ProgressBar($this->output, 100);
		$progress->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% (estimated: %estimated:-6s%)\n %buffer%");
		$progress->setMessage('', 'buffer');
		$progress->setMessage('Download in progress.');
		$progress->start();

		try {

			if (preg_match('/\.mp4$/', $video_link)) {
				$fp = fopen($filename, 'w+');
				$ch = curl_init($video_link);
				curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
				curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($progress) {
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
					$video_link,
					$filename
				));
				$process->setTimeout(null);

				$process->run(function ($type, $buffer) use ($progress, $data) {
					$durationInMilliseconds = $data['programcard']['PrimaryAsset']['DurationInMilliseconds'];
					$pattern = '/([0-9]{2}:[0-9]{2}:[0-9]{2})/';
					preg_match_all($pattern, $buffer, $matches);

					$timeParts = explode(':', $matches[0][0]);
					$downloadedMilliseconds = ($timeParts[0] * 3600 + $timeParts[1] * 60 + $timeParts[2]) * 1000;
					$progress->setProgress(round($downloadedMilliseconds / $durationInMilliseconds * 100));
					$progress->setMessage($buffer, 'buffer');
				});

				// executes after the command finishes
				if (!$process->isSuccessful()) {
					throw new \RuntimeException($process->getErrorOutput());
				}
			}
			$progress->setMessage('Done...', 'buffer');
			$progress->finish();
			$this->output->writeln('');
			$this->output->writeln('');
			\Chili\Utility\General::addDownloaded($slug);

		} catch (\Exception $e) {
			$progress->setMessage('Failed...', 'buffer');
			$progress->finish();
			$this->output->writeln('');

			$this->output->writeln($e->getMessage());
			$this->output->writeln('');
			$this->output->writeln('');
		}
	}
}
