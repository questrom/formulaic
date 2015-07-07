<?php

use Yosymfony\Toml\Toml;

/* Based on https://github.com/ArtBIT/isget/blob/master/src/isget.php */
function isget(&$value, $default = null) {
	return isset($value) ? $value : $default;
}

/* Recursively process data from MongoDB to fix date/time info */
function fixMongoDates($value) {
	if(is_array($value)) {
		return array_map('fixMongoDates', $value);
	} else if($value instanceof MongoDate) {
		return DateTimeImmutable::createFromFormat('U', $value->sec)->setTimezone(new DateTimeZone('America/New_York'));
	} else {
		return $value;
	}
}

/* Get config data */
function getConfig() {
	return Toml::Parse('config/config.toml');
}