<?php

/* Based on https://github.com/ArtBIT/isget/blob/master/src/isget.php */
function isget(&$value, $default = null) {
	return isset($value) ? $value : $default;
}