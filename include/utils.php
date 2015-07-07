<?php

use Yosymfony\Toml\Toml;

/* Based on https://github.com/ArtBIT/isget/blob/master/src/isget.php */
function isget(&$value, $default = null) {
	return isset($value) ? $value : $default;
}

/* Recursively process data from MongoDB to fix date/time info */
function fixMongoDates($value) {
	$config = Config::get();
	if(is_array($value)) {
		return array_map('fixMongoDates', $value);
	} else if($value instanceof MongoDate) {
		return DateTimeImmutable::createFromFormat('U', $value->sec)->setTimezone(new DateTimeZone($config['time-zone']));
	} else {
		return $value;
	}
}

/* Manage config data */
class Config {
	private static $data = null;
	static function get() {
		if(self::$data === null) {
			self::$data = Toml::Parse('config/config.toml');
		}
		return self::$data;
	}
}