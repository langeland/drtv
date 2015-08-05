<?php

namespace Chili\Utility;

class WebService {
	static $cache = array();

	public static function call($url) {
		$cacheKey = md5($url);
		if (array_key_exists($cacheKey, self::$cache)) {
			return self::$cache[$cacheKey];
		} else {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$response = curl_exec($ch);

			// Check if any error occurred
			if (curl_errno($ch)) {
				throw new \Exception('Curl error: ' . curl_error($ch));
			}

			// TODO: json_decode error check
			$data = json_decode($response, TRUE);
			curl_close($ch);
			self::$cache[$cacheKey] = $data;
			return $data;
		}
	}

}
