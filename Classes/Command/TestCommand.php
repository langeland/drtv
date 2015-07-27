<?php

namespace Chili\Command;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class TestCommand extends \Chili\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('test')
			->setDescription('Search dr.dk/tv');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$durationInMilliseconds = 745000;

		$bufferStorage = array(
			'frame=  121 fps=0.0 q=-1.0 size=     180kB time=00:00:04.82 bitrate= 305.8kbits/s    ',
			'frame=  139 fps=117 q=-1.0 size=     245kB time=00:00:05.52 bitrate= 363.5kbits/s    ',
			'frame=  141 fps= 81 q=-1.0 size=     263kB time=00:00:05.66 bitrate= 380.0kbits/s    ',
			'frame=  153 fps= 66 q=-1.0 size=     311kB time=00:00:06.08 bitrate= 418.9kbits/s    ',
			'frame=  159 fps= 56 q=-1.0 size=     342kB time=00:00:06.33 bitrate= 442.5kbits/s    ',
			'frame=  167 fps= 50 q=-1.0 size=     375kB time=00:00:06.64 bitrate= 462.2kbits/s    ',
			'frame=  175 fps= 44 q=-1.0 size=     427kB time=00:00:06.98 bitrate= 500.8kbits/s    ',
			'frame=  185 fps= 42 q=-1.0 size=     481kB time=00:00:07.38 bitrate= 533.4kbits/s    ',
			'frame=  195 fps= 39 q=-1.0 size=     539kB time=00:00:07.82 bitrate= 564.4kbits/s    ',
			'frame=  209 fps= 37 q=-1.0 size=     616kB time=00:00:08.33 bitrate= 605.0kbits/s    '
		);

		foreach ($bufferStorage as $buffer) {

			$pattern = '/([0-9]{2}:[0-9]{2}:[0-9]{2})/';
			preg_match_all($pattern, $buffer, $matches);

			$timeParts = explode(':', $matches[0][0]);
			$downloadedMilliseconds = ($timeParts[0] * 3600 + $timeParts[1] * 60 + $timeParts[2]) * 1000;
			$done = $downloadedMilliseconds / $durationInMilliseconds * 100;

			print_r(round($done));
			echo PHP_EOL;

		}

		// create a new progress bar (50 units)
		$progress = new \Symfony\Component\Console\Helper\ProgressBar($output, 50);

		$progress->setMessage('Task starts');

		$progress->setFormat(" %message%\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% (estimated: %estimated:-6s%)");

		// start and displays the progress bar
		$progress->start();

		$i = 0;
		while ($i++ < 50) {
			// ... do some work
			$progress->setMessage('Task nr: ' . $i);
			usleep(rand(1, 10) * 100000);

			// advance the progress bar 1 unit
			$progress->advance();
		}

		// ensure that the progress bar is at 100%
		$progress->finish();

	}
}

