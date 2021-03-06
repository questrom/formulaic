<?php

// use Yosymfony\Toml\Toml;
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
	} elseif ($value instanceof MongoDate) {
		# Don't just use the 'U' format in case of pre-1970 date
		$date = (new DateTimeImmutable())->setTimestamp($value->sec);
		return $date->setTimezone(new DateTimeZone($config['time-zone']));
	} else {
		return $value;
	}
}

# Convert output for storage in MongoDB
function buildMongoOutput($data) {
	return array_map(function($x) {
		if($x instanceof DateTimeImmutable) {
			$stamp = $x->getTimestamp();
			return new MongoDate($stamp);
		} elseif ($x instanceof FileInfo) {
			throw new Exception('Unexpected file!');
		} elseif (is_array($x)) {
			return buildMongoOutput($x);
		} else {
			return $x;
		}
	}, $data);
}

# Get and cache configuration data
class Config {
	private static $data = null;
	static function get() {
		if(self::$data === null) {
			self::$data = Toml\Parser::fromFile('config/config.toml');
		}
		return self::$data;
	}
}

# A "fake cache" used in place of Gregwar\Cache\Cache when caching is disabled
class FakeCache extends Cache {
	public function set($filename, $contents = '') {
		return $this;
	}
	protected function checkConditions($cacheFile, array $conditions = []) {
		return false;
	}
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
	private $data = null;
	private function getData() {
		$config = Config::get();

		if(!$config['cache-hashes']) {
			$this->data = [];
			$this->write();
		} elseif (file_exists('cache/hashes.json')) {
			$this->data = (array) json_decode(file_get_contents('cache/hashes.json'));
		} else {
			$this->data = [];
		}
	}
	private function write() {
		file_put_contents('cache/hashes.json', json_encode($this->data));
	}
	function get($key) {
		if($this->data === null) {
			$this->getData();
		}
		if(isset($this->data[$key])) {
			return $this->data[$key];
		} else {
			$hash = sha1_file($key);
			$this->data[$key] = $hash;
			$this->write();
			return $hash;
		}
	}
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

# array_map but with keys as well as values
function kvmap(callable $fn, $array) {
	$result = [];
	foreach($array as $key => $value) {
		$result[$key] = $fn($key, $value);
	}
	return $result;
}
