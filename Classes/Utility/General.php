<?php

namespace Chili\Utility;

class General {

	public static function formatMilliseconds($milliseconds) {
		return gmdate("H:i:s", $milliseconds / 1000);
	}

	public static function isDownloaded($slug) {

		$dateFile = WORK_DIR . '/.drtv';
		if (is_file($dateFile)) {
			$data = json_decode(file_get_contents($dateFile), TRUE);
			if (array_key_exists($slug, $data['downloaded'])) {
				return true;
			}
		} else {
			return false;
		}
	}

	public static function addDownloaded($slug) {
		$dateFile = WORK_DIR . '/.drtv';
		if (is_file($dateFile)) {
			$data = json_decode(file_get_contents($dateFile), TRUE);
		} else {
			$data = array();
		}
		$data['downloaded'][$slug] = time();

		file_put_contents($dateFile, json_encode($data, JSON_PRETTY_PRINT));
	}

	public static function removeDownloaded($slug) {

	}

	public static function getWatchList() {
		$dateFile = WORK_DIR . '/.drtv';
		if (is_file($dateFile)) {
			$data = json_decode(file_get_contents($dateFile), TRUE);
			if (array_key_exists('watch', $data)) {
				return $data['watch'];
			}
		} else {
			return array();
		}
	}

	public static function addWatchListSlug($slug) {
		$dateFile = WORK_DIR . '/.drtv';
		if (is_file($dateFile)) {
			$data = json_decode(file_get_contents($dateFile), TRUE);
		} else {
			$data = array();
		}
		array_push($data['watch'], $slug);

		file_put_contents($dateFile, json_encode($data, JSON_PRETTY_PRINT));
	}

	public static function removeWatchListSlug($slug) {
		$dateFile = WORK_DIR . '/.drtv';
		if (is_file($dateFile)) {
			$data = json_decode(file_get_contents($dateFile), TRUE);
		} else {
			return;
		}

		foreach ($data['watch'] as $key => $value) {
			if ($value == $slug) {
				unset($data['watch'][$key]);
				break;
			}
		}

		file_put_contents($dateFile, json_encode($data, JSON_PRETTY_PRINT));
	}

}
