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
	protected function checkConditions($cacheFile, array $conditions = []) { return false; }
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

class Hashes {
	private static $data = null;
	private static $config = null;
	static function getData() {
		self::$config = Config::get();

		if(!self::$config['cache-hashes']) {
			self::$data = [];
			self::write();
		} else if(file_exists('cache/hashes.json')) {
			self::$data = (array) json_decode(file_get_contents('cache/hashes.json'));
		} else {
			self::$data = [];
		}
	}
	static function write() {
		file_put_contents('cache/hashes.json', json_encode(self::$data));
	}
	static function get($key) {
		if(self::$data === null) {
			self::getData();
		}
		if(isset(self::$data[$key])) {
			// Allow disabling this...
			return self::$data[$key];
		} else {
			$hash = sha1_file($key);
			self::$data[$key] = $hash;
			self::write();
			return $hash;
		}
	}
}

function fixAssets($html) {
	return preg_replace_callback('/____\{\{asset (.*?)\}\}____/', function($matches) {
		return preg_replace_callback('/^(.*)\.(.*)$/', function($parts) use($matches) {
			return Config::get()['asset-prefix'] . $parts[1] . '.hash-' . Hashes::get($matches[1]) . '.' . $parts[2];
		}, $matches[1]);
	}, $html);
}