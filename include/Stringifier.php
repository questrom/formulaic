<?php


# This class handles converting HTML data into strings, as a two step process.
class Stringifier {


	# This function converts HTMLGenerator objects into flat arrays,
	# which can then be cached before final processing using the makeString
	# function below.

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


	# A helper generator function for that converts objects into an iterator
	static function generateArray($element) {

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


					# Generate values that can, in theory, be serialized
					# (e.g. stored in JSON). We could use PHP's serialize(),
					# but this is worse for perf.

					if(is_string($element)) {
						# Handle the escapeCount by escaping extra times.
						for($j = $escapeCount; $j--;) {
							$element = htmlspecialchars($element, ENT_QUOTES | ENT_HTML5);
						}
						yield $element;
					} else {
						if($escapeCount > 0) {
							# We can't perform escapeCount escaping on non-strings
							throw new Exception('Cannot escape non-string values!');
						}
						if($element instanceof AssetUrl) {
							yield ['asset' => $element->value];
						} else if($element instanceof CSRFPlaceholder) {
							yield ['csrf' => true];
						} else {
							throw new Exception("Invalid HTML component!");
						}
					}
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
	}

	# Turns the generated data into a simplified array
	static function makeArray($element) {
		$out = [];
		$lastString = false;

		foreach(self::generateArray($element) as $x) {
			if(is_string($x) && $lastString) {
				$out[count($out) - 1] .= $x;
			} else {
				$out[] = $x;
			}
			$lastString = is_string($x);
		}
		return $out;
	}


	# Generator function used by makeString()
	static function generateString($parts, $csrfToken) {

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

		foreach($parts as $part) {
			if(is_string($part)) {
				yield $part;
			} else if(isset($part['asset'])) {
				$part = $part['asset'];
				$fileName = isget($assetMap[$part], $part);
				yield preg_replace_callback('/^(.*)\.(.*)$/', function($parts) use($fileName, $config, $hashes) {
					return htmlspecialchars($config['asset-prefix'] . $parts[1] . '.hash-' . $hashes->get($fileName) . '.' . $parts[2], ENT_QUOTES | ENT_HTML5);
				}, $fileName);
			} else if(isset($part['csrf'])) {
				yield htmlspecialchars($csrfToken, ENT_QUOTES | ENT_HTML5);
			} else {
				throw new Exception("Invalid HTML component!");
			}
		}
	}

	# Convert an array from makeArray() into an actual, final string
	# ready to be displayed to the user.
	static function makeString($out, $csrfToken = null) {
		$str = '';
		$res = self::generateString($out, $csrfToken);
		foreach($res as $x) {
			$str .= $x;
		}
		return $str;
	}

	# Performs both steps at once.
	static function stringify($element) {
		return self::makeString(self::generateArray($element));
	}

	# Stringify and write a HTTP response
	static function writeResponse($element, $response) {
		$out = self::generateArray($element);


		$parts = self::generateString($out, null);
		foreach($parts as $x) {
			$response->append($x);
		}
	}
}
