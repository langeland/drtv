<?php

namespace Chili\Utility;

class General {

	public static function formatMilliseconds($milliseconds) {
		return gmdate("H:i:s", $milliseconds / 1000);
	}

	public static function isDownloaded($slug) {

		$dateFile = $_SERVER['HOME'] . '/.drtv';
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
		$dateFile = $_SERVER['HOME'] . '/.drtv';
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

	public static function download($slug, $filename = '') {

	}

}
