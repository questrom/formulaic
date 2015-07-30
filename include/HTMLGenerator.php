<?php

# These classes define a simple DSL for generating HTML.
# Perhaps this is best explained with a simple example:

#  h()->div->class('test')
#     ->span->id('hello')->c('Hello world')->end
#   ->end->generateString()
# equals:
#   <div class="test"><span id="hello">Hello world</span></div>

# (More complex examples are present throughout the codebase.)

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

# Cache some safestrings globally for speed
$SAFE_LT = new SafeString('<');
$SAFE_GT = new SafeString('>');
$SAFE_LT_SLASH =  new SafeString('</');
$SAFE_EQ_QUOTE = new SafeString('="');
$SAFE_QUOTE = new SafeString('"');

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

	# This function actually converts HTMLGenerator objects into strings.
	# (well, actually arrays, which get re-converted by stringize below.
	# this is a two-step process for caching purposes.)
	# There were three ways of implementing this:
	# 1. Using recursion - though fast and elegant, this results in call
	#    stack overflows with very complex forms.
	# 2. Using iteration - this fixes the stack overflow issue, but is
	#    very slow (like ten times slower).
	# 3. Using iteration, but heavily optimizing things. This fixes the
	#    speed issue, but is very inelegant, making the code difficult
	#    to read.
	# In the end, I chose option (3), so this code is rather...complex.
	# It might be worth reinvestigating this tradeoff in the future.

	# This algorithm is based partly on the tail recursive algorithm
	# given at this link: http://rosettacode.org/wiki/Flatten_a_list#Common_Lisp
	# As well as on the purely iterative JavaScript solution
	# given at this link: http://stackoverflow.com/questions/29991016/

	final function generateString() {
		// $t = microtime(true);
		# The HTML output
		$out = [];

		# The last thing written to $out was a string
		$lastString = false;

		# A "call stack" type structure
		$stack = [];

		# The top of the stack. Keep track of this manually for greater speed.
		# If $stack is like a call stack, this is like the "esp" register
		# on an x86 architecture.
		$top = 1;

		# The thing currently being processed
		$element = $this;

		# The number of extra times that HTML needs to be escaped.
		# This, along with the DoubleEncode class, is needed for nested
		# lists to work properly.
		$escapeCount = 0;

		# Algorithm runs in O(infinity) time. </sarcasm>
		while(true) {

			if (is_array($element) && count($element) > 0) {
				# If it is an array with at least one element, then
				# break it apart: put each element onto the stack,
				# in reverse order, along with the escapeCount.
				$element = array_values($element);
				for($i = count($element) - 1; $i >= 1;) {
					$stack[$top++] = $element[$i--];
					$stack[$top++] = $escapeCount;
				}
				# Now process the first array element.
				$element = $element[0];
			} elseif ($element instanceof Renderable) {
				# If it is a Renderable, render it and handle it in
				# the next iteration. HTMLGeneratorAbstract implements Renderable
				# as well.
				$element = $element->render();
			} elseif ($element instanceof DoubleEncode) {
				# If it is a DoubleEncode, add to escapeCount.
				$element = $element->value;
				$escapeCount++;
			} else {
				# This would be the "base case" if the algorithm were recursive. Here
				# we handle things that can't be further subdivided.

				# Empty arrays/strings and null values produce no output, so just skip them.
				if(!is_array($element) && $element !== null && $element !== '') {

					if($element instanceof SafeString) {
						# If a string is wrapped in a SafeString object, it needs no escaping.
						$element = $element->value;
					} else if(is_scalar($element)) {
						# Strings and other primitive types must be escaped an extra time.
						$element = (string) $element;
						$escapeCount++;
					}

					# Handle the escapeCount by escaping extra times.
					# We assume that AssetURLs and other non-string things won't need extra escaping.
					if(is_string($element)) {
						for($j = $escapeCount; $j--;) {
							$element = htmlspecialchars($element, ENT_QUOTES | ENT_HTML5);
						}
					}

					# Generate arrays that can, in theory, be serialized
					# (e.g. stored in JSON).
					# We could use PHP's serialize(), but this is worse for perf.

					if(is_string($element) && $lastString) {
						$out[count($out) - 1] .= $element;
					} else if(is_string($element)) {
						$out[] = $element;
					} else if($element instanceof AssetUrl) {
						$out[] = ['asset' => $element->value];
					} else if($element instanceof CSRFPlaceholder) {
						$out[] = ['csrf' => true];
					}  else {
						throw new Exception("Invalid HTML component!");
					}
					$lastString = is_string($element);
				}

				if($top > 1) {
					# If the stack still has items left, get the
					# escapeCount and element from it.
					$escapeCount = $stack[--$top];
					$element = $stack[--$top];
				} else {
					break;
				}
			}
		}



		return $out;
	}
}

# Convert an array from generateString() into an actual, final string
# ready to be displayed to the user.
function stringize($out, $csrfToken = null) {

	$config = Config::get();
	$hashes = new Hashes();

	# Unless we're in debug mode, serve minified versions of things like semantic.
	# Don't bother minifying styles.css and client.js, because their size is tiny
	# compared to these libraries.
	$assetMap = $config['debug'] ? [] : [
		'lib/semantic.css' => 'lib/semantic.min.css',
		'lib/semantic.js' => 'lib/semantic.min.js',
		'lib/jquery.js' => 'lib/jquery.min.js',
		'lib/jquery.inputmask.bundle.js' => 'lib/jquery.inputmask.bundle.min.js'
	];

	$str = '';
	foreach($out as $x) {
		if(is_string($x)) {
			$str .= $x;
		} else if(isset($x['asset'])) {
			$x = $x['asset'];

			$fileName = isget($assetMap[$x], $x);

			$str .= preg_replace_callback('/^(.*)\.(.*)$/', function($parts) use($fileName, $config, $hashes) {
				return htmlspecialchars($config['asset-prefix'] . $parts[1] . '.hash-' . $hashes->get($fileName) . '.' . $parts[2], ENT_QUOTES | ENT_HTML5);
			}, $fileName);

		} else if(isset($x['csrf'])) {
			$str .= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_HTML5);
		} else {
			throw new Exception("Invalid HTML component!");
		}
	}

	return $str;
}

# Very simple shortcut for getting an HTML generator.
function h() {
	return new HTMLParentlessContext();
}
