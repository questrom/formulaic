<?php

# These classes define a simple DSL for generating HTML.
# Perhaps this is best explained with a simple example:

#  h()->div->class('test')
#     ->span->id('hello')->c('Hello world')->end
#   ->end->generateString()
# equals:
#   <div class="test"><span id="hello">Hello world</span></div>

# More simply: this lets us embed HTML inside of PHP without having
# to use <?php and ?\>. This makes the code more elegant and less
# confusing.

# Be careful that changes made to these classes do not introduce
# serious performance problems, as it is very easy to do so by
# accident. (I can attest.)

# The below class, HTMLGeneratorAbstract, provides methods shared
# by all four HTMLGenerator objects. The h() function just returns
# a new HTMLParentlessContext, as a shortcut.
abstract class HTMLGeneratorAbstract {

	# The "c" function is used to add text or other HTML elements
	# into the current element.
	abstract function c($arr);

	# __get() is used as to implement things like ->div and ->end
	abstract function __get($name);

	# toStringArray converts an HTMLGenerator into an array of strings
	# (or an array of arrays, etc.)
	abstract function toStringArray();

	# This function actually converts HTMLGenerator objects into strings.
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
		$out = '';

		# A "call stack" type structure
		$stack = [];

		# The thing currently being processed
		$element = $this;

		# The number of extra times that HTML needs to be escaped.
		# This, along with the DoubleEncode class, is needed for nested
		# lists to work properly.
		$escapeCount = 0;

		# Algorithm runs in O(infinity) time. </sarcasm>
		while(true) {

			if($element instanceof Renderable) {
				# If it is a Renderable, render it and handle it in
				# the next iteration.
				$element = $element->render();
			} else if($element instanceof HTMLGeneratorAbstract) {
				# If it is an HTMLGenerator, convert it into an array
				# and handle it in the next iteration.
				$element = $element->toStringArray();
			} else if ($element instanceof DoubleEncode) {
				# If it is a DoubleEncode, turn it into an array, add
				# to escapeCount, and iterate again.
				$element = [$element->value];
				$escapeCount++;
			} else if (is_array($element) && count($element) > 0) {
				# If it is an array with at least one element, leave
				# one element behind on the stack. Also store escapeCount
				# on the stack.
				$stack[] = array_pop($element);
				$stack[] = $escapeCount;
			} else {
				# This would be the "base case" if the algorithm were recursive. Here
				# we handle things that can't be further subdivided.

				# Empty arrays/strings and null values produce no output, so just skip them.
				if(!is_array($element) && $element !== null && $element !== '') {


					if($element instanceof SafeString) {
						# If a string is wrapped in a SafeString object, it needs no escaping.
						$element = $element->value;
					} else if(is_scalar($element)) {
						# Strings and other primitive types must be escaped.
						$element = htmlspecialchars($element, ENT_QUOTES | ENT_HTML5);
					} else {
						throw new Error("Invalid HTML component!");
					}

					# Handle the escapeCount by escaping extra times.
					for($j = $escapeCount; $j--;) {
						$element = htmlspecialchars($element, ENT_QUOTES | ENT_HTML5);
					}

					# Add to the output
					$out .= $element;
				}


				if(count($stack) > 0) {
					# If the stack still has items left, get the
					# escapeCount and element from it.
					$escapeCount = array_pop($stack);
					$element = array_pop($stack);
				} else {
					# Otherwise, we're done!
					break;
				}
			}

		}

		// echo '<br><br><br>'.(microtime(true)-$t)*1000;

		return $out;
	}
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
class AssetUrl extends SafeString {
	function __construct($value) {
		$this->value = '____{{asset ' . $value . '}}____';;
	}
}

# An HTMLGenerator which generates the inside of an HTML element,
# excluding start and end tags.
class HTMLContentGenerator extends HTMLGeneratorAbstract {
	function __construct($children = []) {
		$this->children = $children;
	}

	# Create a new tag
	function __get($name) {
		return new HTMLTagGenerator(new HTMLContentGenerator(), $name);
	}

	# Add a child
	function c($arr) {
		$children = $this->children;
		$children[] = $arr;
		return new HTMLContentGenerator($children);
	}

	# Make an array
	function toStringArray() {
		return $this->children;
	}
}

# Generates the outside of an HTML element, including start/end tags.
# Internally, uses HTMLContentGenerator to make the inside of the tag.
class HTMLTagGenerator extends HTMLGeneratorAbstract {
	private $contents, $tag, $attrs;

	function __construct( $contents, $tag, $attrs = []) {
		$this->contents = $contents;
		$this->tag = $tag;
		$this->attrs = $attrs;
	}

	# Add an attribute, if the second parameter is given and false,
	# then don't add it (this allows for attributes that are only added conditionally).
	function __call($name, $args) {
		if(isset($args[1]) && !$args[1]) {
			return $this;
		}
		$this->attrs[$name] = $args[0];
		return new HTMLTagGenerator($this->contents, $this->tag, $this->attrs);
	}

	# Shortcut for "data-*" attributes.
	function data($key, $val, $cond = true) {
		if(!$cond) {
			return $this;
		}
		return new HTMLTagGenerator($this->contents, $this->tag, array_merge($this->attrs, [  'data-' . $key => $val ] ));
	}

	# Add a child element manually
	function c($arr) {
		return new HTMLTagGenerator($this->contents->c($arr), $this->tag, $this->attrs);
	}

	# Create a new child element
	function __get($name) {
		return new HTMLTagGenerator(new HTMLContentGenerator(), $name);
	}

	# Turn it into an array of strings (or an array of arrays of strings, etc...)
	function toStringArray() {
		$arr = [];
		$arr[] = new SafeString('<');
		$arr[] = $this->tag;
		foreach ($this->attrs as $key => $value) {
			$arr[] = new SafeString(' ');
			$arr[] = $key;
			$arr[] = new SafeString('="');
			$arr[] = $value;
			$arr[] = new SafeString('"');
		}

		$arr[] = new SafeString('>');

		# Might as well take care of the toStringArray() call here instead
		# of in generateString().
		$arr[] = $this->contents->toStringArray();

		$arr[] = new SafeString('</');
		$arr[] = $this->tag;
		$arr[] = new SafeString('>');
		return $arr;
	}
}

# Create an HTML element that has a parent. Basically, this just makes "->end" work
# as a way of "leaving" a child element.
class HTMLParentContext extends HTMLGeneratorAbstract {
	function __construct($parent, $generator) {
		# $generator is an HTML generator that creates the child element.
		$this->parent = $parent;
		$this->generator = $generator;
	}

	function __call($name, $args) {
		return new HTMLParentContext($this->parent, $this->generator->__call($name, $args));
	}

	function c($arr) {
		return new HTMLParentContext($this->parent, $this->generator->c($arr));
	}

	function data($key, $val, $cond = true) {
		return new HTMLParentContext($this->parent, $this->generator->data($key, $val, $cond));
	}

	function __get($name) {
		if($name === 'end') {
			return $this->parent->c($this);
		} else {
			return new HTMLParentContext($this, $this->generator->__get($name));
		}
	}

	function toStringArray() {
		return $this->generator->toStringArray();
	}
}

# Creates an HTML element with NO parent.
class HTMLParentlessContext extends HTMLGeneratorAbstract{
	function __construct($generator = null) {
		$this->generator = $generator ? $generator : new HTMLContentGenerator();
	}

	function __get($name) {
		return new HTMLParentContext($this, $this->generator->__get($name));
	}

	function c($arr) {
		return new HTMLParentlessContext($this->generator->c($arr));
	}

	function toStringArray() {
		return $this->generator->toStringArray();
	}
}

# Very simple shortcut for getting an HTML generator.
function h() {
	return new HTMLParentlessContext();
}
