<?php

use Yosymfony\Toml\Toml;
use Gregwar\Cache\Cache;

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

/* Fake cache that doesn't really do anything */
class FakeCache extends Cache {
	public function set($filename, $contents = '') { return $this; }
	protected function checkConditions($cacheFile, array $conditions = array()) { return false; }
}


// See http://php.net/manual/en/reserved.variables.files.php
function diverse_array($vector) {
   $result = [];
   foreach($vector as $part => $val) {
   		foreach($val as $index => $ival) {
   			foreach($ival as $name => $info) {
   				$result[$index][$name][$part] = $info;
   			}
   		}
   }
   return $result;
}


function midpoint($a, $b) {
	return $a + (($b - $a) / 2);
}