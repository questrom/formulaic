<?php

use Yosymfony\Toml\Toml;
use Gregwar\Cache\Cache;


# Shortcut for "isset()" checks
# Name inspired by https://github.com/ArtBIT/isget/blob/master/src/isget.php
function isget(&$value, $default = null) {
	return isset($value) ? $value : $default;
}

# Process data from MongoDB to convert dates and times
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

# Get and cache configuration data
class Config {
	private static $data = null;
	static function get() {
		if(self::$data === null) {
			self::$data = Toml::Parse('config/config.toml');
		}
		return self::$data;
	}
}

# A "fake cache" used in place of Gregwar\Cache\Cache when caching is disabled
class FakeCache extends Cache {
	public function set($filename, $contents = '') { return $this; }
	protected function checkConditions($cacheFile, array $conditions = []) { return false; }
}

# PHP formats the $_FILES array in an extremely unusual way;
# this function fixes it so that it works more like $_POST.
# See http://php.net/manual/en/reserved.variables.files.php#109958
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

# Winner of the "least interesting function of the year" award...
function midpoint($a, $b) {
	return $a + (($b - $a) / 2);
}

# To fix potential issues with HTTP caching, we take the hashes of different assets (i.e., CSS/JS files)
# and put these hashes into the URLs of those assets. Apache ignores the hashes because of URL rewriting.
# This is called URL fingerprinting; see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
# for further details.

# This class computes the hashes and, if cache-hashes is enabled in the configuration file,
# caches the hashes so we don't need to re-hash files on every single request.
class Hashes {
	private static $data = null;
	private static function getData() {
		$config = Config::get();

		if(!$config['cache-hashes']) {
			self::$data = [];
			self::write();
		} else if(file_exists('cache/hashes.json')) {
			self::$data = (array) json_decode(file_get_contents('cache/hashes.json'));
		} else {
			self::$data = [];
		}
	}
	private static function write() {
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

# This function writes the URLs, with hashes included, into a page before it is displayed to the user.
# We have to do this replacement after the page has been generated so that we can cache
# form UIs properly (when cache-forms is enabled).
function fixAssets($html) {
	return preg_replace_callback('/____\{\{asset (.*?)\}\}____/', function($matches) {
		$config = Config::get();
		$assetMap = $config['debug'] ? [] : [
			'lib/semantic.css' => 'lib/semantic.min.css',
			'lib/semantic.js' => 'lib/semantic.min.js',
			'lib/jquery.js' => 'lib/jquery.min.js',
			'lib/jquery.inputmask.bundle.js' => 'lib/jquery.inputmask.bundle.min.js'
		];
		$fileName = isget($assetMap[$matches[1]], $matches[1]);

		return preg_replace_callback('/^(.*)\.(.*)$/', function($parts) use($matches, $config) {
			return $config['asset-prefix'] . $parts[1] . '.hash-' . Hashes::get($matches[1]) . '.' . $parts[2];
		}, $fileName);

	}, $html);
}

# This keeps track of the number of times a form has been submitted,
# so that it can be displayed on the main list of forms.
# We can't just use Mongo for this, since some forms might not use Mongo.
class SubmitCounts {
	private static $data = null;
	private static function update() {
		if(file_exists('data/submit-counts.json')) {
			self::$data = json_decode(file_get_contents('data/submit-counts.json'));
		} else {
			self::$data = (object) [];
		}
	}
	private static function write() {
		if(!is_dir('data')) {
			mkdir('data');
		}
		file_put_contents('data/submit-counts.json', json_encode(self::$data));
	}
	static function get($formName) {
		if (self::$data === null) {
			self::update();
		}
		return isget(self::$data->$formName, 0);
	}
	static function increment($formID) {
		self::update();
		$counts = self::$data;
		$counts->$formID = isget($counts->$formID, 0) + 1;
		self::$data = $counts;
		self::write();
	}
}

# Holds data from $_POST and $_FILES in a single data structure
class ClientData {
	function __construct($post, $files) {
		$this->post = $post;
		$this->files = $files;
	}
}

# Stores information about an uploaded file that has *not* yet been
# uploaded to S3
class FileInfo {
	function __construct($file, $filename, $mime, $permissions) {
		$this->file = $file;
		$this->filename = $filename;
		$this->mime = $mime;
		$this->permissions = $permissions;
	}
}


# Simple date formatting helpers.
function dfd($date) {
	return $date->format('m/d/Y');
}
function df($date) {
	return $date->format('g:ia m/d/Y');
}

# array_map btu with keys as well as values
function kvmap(callable $fn, $array) {
	$result = [];
	foreach($array as $key => $value) {
		$result[$key] = $fn($key, $value);
	}
	return $result;
}
