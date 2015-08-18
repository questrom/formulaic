<?php

# These classes define a simple DSL for generating HTML.
# Perhaps this is best explained with a simple example:

#  (new Stringifier())->stringify(
#    h()->div->class('test')
#      ->span->id('hello')->c('Hello world')->end
#    ->end
#  )
# equals:
#   <div class="test"><span id="hello">Hello world</span></div>

# (More complex examples are present throughout the codebase.
#  The Stringifier class is cpmtaomed in a separate file.)

# More simply: this lets us embed HTML inside of PHP without having
# to use <?php and ?\>. This makes the code more elegant and less
# confusing.

# Be careful that changes made to these classes do not introduce
# serious performance problems, as it is very easy to do so by
# accident. (I can attest.)



# Things implemented by both HTMLParentContext and HTMLParentlessContext,
# which are described below.
interface HTMLGenerator extends Renderable {

	# The "c" function is used to add text or other HTML elements
	# into the current element.
	public function c($arr);

	# __get() is used as to implement things like ->div and ->end
	public function __get($name);

	# render() converts an HTMLGenerator into an array of strings
	# (or an array of arrays, etc.). It is defined by Renderable.
}

# Used to hold strings in order to mark that they do not
# need to be escaped.
class SafeString {
	function __construct($value) {
		$this->value = $value;
	}
}

# Cache some safestrings globally for speed
$SAFE_LT = new SafeString('<');
$SAFE_GT = new SafeString('>');
$SAFE_LT_SLASH =  new SafeString('</');
$SAFE_EQ_QUOTE = new SafeString('="');
$SAFE_QUOTE = new SafeString('"');

# Used to hold values which need to be escaped an extra time.
# Needed for nested lists to work properly.
class DoubleEncode {
	function __construct($value) {
		$this->value = $value;
	}
}

# Used to hold asset URLs that can be replaced with fixAssets()
# See utils.php for details
class AssetUrl {
	function __construct($value) {
		$this->value = $value;
	}
}

# Used as a placeholder for CSRF tokens
class CSRFPlaceholder {}

# Used to represent that a header should be set with a response
class HeaderSet {
	function __construct($key, $value) {
		$this->key = $key;
		$this->value = $value;
	}
}

# This generates an HTML tag with a parent element.
class HTMLParentContext implements HTMLGenerator {

	private $parent, $tag, $contents, $attrs;

	function __construct($parent, $tag) {
		# $tag is the name of the tag
		$this->parent = $parent;
		$this->tag = $tag;
		$this->contents = [];
		$this->attrs = [];
		$this->end = $parent->c($this);
	}

	# Add an attribute
	function __call($name, $args) {
		if(!isset($args[1]) || $args[1]) {
			$this->attrs[$name] = $args[0];
		}
		return $this;
	}

	# Add a child manually
	function c($arr) {
		$this->contents[] = $arr;
		return $this;
	}

	# Shortcut for data-* attributes
	function data($key, $val, $cond = true) {
		if($cond) {
			$this->attrs['data-' . $key] = $val;
		}
		return $this;
	}

	# Create a new child element using, e.g., ->div
	function __get($name) {
		return new HTMLParentContext($this, $name);
	}

	function render() {

		global $SAFE_LT, $SAFE_GT, $SAFE_LT_SLASH, $SAFE_EQ_QUOTE, $SAFE_QUOTE;

		$arr[] = $SAFE_LT;
		$arr[] = $this->tag;

		foreach ($this->attrs as $key => $value) {
			$arr[] = ' ';
			$arr[] = $key;
			$arr[] = $SAFE_EQ_QUOTE;
			$arr[] = $value;
			$arr[] = $SAFE_QUOTE;
		}

		$arr[] = $SAFE_GT;

		$arr[] = $this->contents;

		$arr[] = $SAFE_LT_SLASH;
		$arr[] = $this->tag;
		$arr[] = $SAFE_GT;

		return $arr;
	}
}

# Creates HTML without a parent.
class HTMLParentlessContext implements HTMLGenerator{
	private $contents;

	function __construct() {
		$this->contents = [];
	}

	function __get($name) {
		return new HTMLParentContext($this, $name);
	}

	function c($arr) {
		$this->contents[] = $arr;
		return $this;
	}

	function render() {
		return $this->contents;
	}
}

# Very simple shortcut for getting an HTML generator.
function h() {
	return new HTMLParentlessContext();
}
