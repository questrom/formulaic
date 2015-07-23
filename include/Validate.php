<?php

# In order to understand what this abstract class does, please
# first read the documentation below it, which describes the nature of
# the Success, Failure, and Result types.

# This class provides a number of "shortcut" methods for Results
# so that functions used for the purpose of validation can be
# reused by several form fields. These methods could be put
# on a static class, but that would make the syntax generally uglier.
abstract class Validate {

	# These functions makes it easier to deal with values created
	# by the Result class.


	# collapse() does the following conversion:
	# Success(Success($x)) -> Success($x)
	# Success(Failure($x)) -> Success($x)
	# Failure($x)          -> Failure($x)
	function collapse() {
		return $this
			->ifSuccess(function($x) {
				return new Success(
					$x
					->ifSuccess(function($y) { return new Success($y); })
					->ifError(function($y) { return new Success($y); })
					->ifSuccess(function($y) { return $y; })
				);
			});
	}

	# Bind a Success within a Success.
	# Basically, if $this is a Success(Success($z)), then
	# the "callable" provided to this method will be invoked with $z as
	# its parameter. Then, the return value of the callable will be returned.

	# This lets us ignore Result::none and Result::error values when validating.
	function innerBind(callable $x) {
		return $this->ifSuccess(function($val) use($x) {
			return $val
				->ifSuccess(function($data) use($val, $x) {
					return new Success($val->ifSuccess($x));
				})
				->ifError(function($data) {
					return new Success(Result::none($data));
				})
				->ifSuccess(function($data) {
					return $data;
				});
		});
	}

	# Similar to innerBind(), but it binds a Failure within a Success;
	# it can be used to  make sure a value is a Result::none.
	function bindNothing(callable $x) {
		return $this->ifSuccess(function($val) use($x) {
			return $val
				->ifSuccess(function($data) {
					return new Success(Result::ok($data));
				})
				->ifError(function($data) use($val, $x) {
					return new Success($val->ifError($x));
				})
				->ifSuccess(function($data) {
					return $data;
				});
		});
	}

	# Convert a value to a boolean, if possible.
	function filterBoolean() {
		return $this->innerBind(function($x) {
			$value = filter_var($x, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

			if($value === true) {
				return Result::ok(true);
			} else if($value === false) {
				return Result::ok(false);
			} else {
				return Result::error('Invalid data!');
			}
		});
	}

	# Make sure a value is a string
	function filterString() {
		return $this->innerBind(function($x) {
			if(is_string($x)) {
				return Result::ok($x);
			} else {
				return Result::error('Invalid data!');
			}
		});
	}

	# Run a value through "filter_var"
	function filterFilterVar($cons, $msg) {
		return $this->innerBind(function($x) use ($cons, $msg) {
			$addr = filter_var($x, $cons);
			if($addr !== false) {
				return Result::ok($addr);
			} else {
				return Result::error($msg);
			}
		});
	}

	# Check that a value is a member of $options
	function filterChosenFromOptions($options) {
		return $this->innerBind(function($x) use($options) {
			if($x === '' || $x === null) {
				return Result::none(null);
			} else if(in_array($x, $options, true)) {
				return Result::ok($x);
			} else {
				return Result::error('Invalid data!');
			}
		});
	}

	# Check that a value is an array
	# All of the array's members mtub e contained in $options
	function filterManyChosenFromOptions($options) {
		return $this->innerBind(function($x) use($options) {
			if($x === null) {
				return Result::ok([]);
			} else if(is_array($x) && count(array_diff($x, $options)) === 0 ) {
				return Result::ok($x);
			} else {
				return Result::error('Invalid data!');
			}
		});
	}

	# Convert a value into a date, if possible
	function filterDate() {
		return $this->innerBind(function($x) {
			if(trim($x) == '') {
				return Result::none(null);
			}

			$date = DateTimeImmutable::createFromFormat('m/d/Y', $x);


			if($date !== false) {
				$date = $date->setTime(0, 0, 0);
				return Result::ok($date);
			} else {
				return Result::error('Invalid date!');
			}
		});
	}

	# Static helper method to convert a time into second-past-midnight form.
	static function timeToSeconds($x) {
		$date = DateTimeImmutable::createFromFormat('g:i a', $x);
		return 	($date->format('G') * 3600) + ($date->format('i') * 60);
	}

	# Convert a value into a time, if possible
	function filterTime() {
		return $this->innerBind(function($x) {

			if(trim($x) == '') {
				return Result::none(null);
			}

			$date = DateTimeImmutable::createFromFormat('g:i a', $x);

			if($date !== false) {
				$seconds = ($date->format('G') * 3600) + ($date->format('i') * 60);
				return Result::ok($seconds);
			} else {
				return Result::error('Invalid time!');
			}
		});
	}

	# Convert a value into a date/time, if possible
	function filterDateTime() {
		return $this->innerBind(function($x) {

			if(trim($x) == '') {
				return Result::none(null);
			}

			$date = DateTimeImmutable::createFromFormat('m/d/Y g:i a', $x);

			if($date !== false) {
				return Result::ok($date);
			} else {
				return Result::error('Invalid time!');
			}
		});
	}

	# Check that a value is a phone number
	function filterPhone() {
		return $this->innerBind(function($x) {
			$phn = preg_replace('/[^x+0-9]/', '', $x);
			if(strlen($phn) >= 10 || $x === '') {
				return Result::ok($phn);
			} else {
				return Result::error('Invalid phone number.');
			}
		});
	}

	# Check that a value is a number
	function filterNumber($integer) {
		return $this->innerBind(function($x) use ($integer) {

			if($integer) {
				$num = filter_var($x, FILTER_VALIDATE_INT);
			} else {
				$num = filter_var($x, FILTER_VALIDATE_FLOAT);
			}

			if($num !== false) {
				return Result::ok($num);
			} else {
				return Result::error('Invalid number.');
			}
		});
	}

	# Marks a result containing an empty string as absent
	function filterEmptyString() {
		return $this->innerBind(function($x) {
			if(trim($x) === '') {
				return Result::none($x);
			} else {
				return Result::ok($x);
			}
		});
	}

	# Marks a result containing an empty array as absent
	function filterNoChoices() {
		return $this->innerBind(function($x) {
			if(count($x) === 0) {
				return Result::none($x);
			}
			return Result::ok($x);
		});
	}

	# If a field is "required," make sure it isn't a Result::none
	function requiredMaybe($enable) {
		return $this->bindNothing(function($x) use ($enable) {
			if($enable) {
				return Result::error('This field is required.');
			} else {
				return Result::none($x);
			}
		});
	}

	# Check that a specific value is boolean TRUE
	function mustBeTrue($enable) {
		return $this->innerBind(function($x) use ($enable) {
			if($enable && !$x) {
				return Result::error('Please check this box before submitting the form.');
			}
			return Result::ok($x);
		});
	}

	# Check that an email address has a specific domain
	function mustHaveDomain($checkDomain) {
		return $this->innerBind(function($x) use ($checkDomain) {
			if($checkDomain !== null) {
				# See http://stackoverflow.com/questions/6917198/
				$domain = explode('@', $x);
				$domain = array_pop($domain);

				if($domain !== $checkDomain) {
					return Result::error('Domain must equal: ' . $checkDomain . '.');
				}
			}
			return Result::ok($x);
		});
	}

	# Check that a date is within a range
	function minMaxDate($minDate, $maxDate) {
		return $this->innerBind(function($x) use ($minDate, $maxDate) {

			$x = $x->setTime(0,0,0);
			if($minDate !== null && $minDate > $x) {
				return Result::error('Please enter a date starting at ' . $minDate->format('Y-m-d'));
			} else if($maxDate !== null && $maxDate < $x) {
				return Result::error('Please enter a date ending at ' . $maxDate->format('Y-m-d'));
			} else {
				return Result::ok($x);
			}
		});
	}

	# Check that the length of a string is within a specific range
	function minMaxLength($minLength, $maxLength) {
		return $this->innerBind(function($x) use ($minLength, $maxLength) {
			if(strlen($x) > $maxLength) {
				return Result::error('The input is too long. Maximum is ' . $maxLength . ' characters.');
			} else if(strlen($x) < $minLength) {
				return Result::error('The input is too short. Minimum is ' . $minLength . ' characters.');
			} else {
				return Result::ok($x);
			}
		});
	}

	# Check that a string matches a regular expression
	function matchRegex($regex) {
		return $this->innerBind(function($x) use ($regex) {
			if($regex !== null && preg_match($regex, $x) === 0) {
				return Result::error('Invalid input!');
			}
			return Result::ok($x);
		});
	}

	# Check that a password matches a hashed password created
	# with PHP's built in password_hash() function
	function matchHash($hash) {
		return $this->innerBind(function($x) use ($hash) {
			if($hash !== null) {
				if(password_verify($x, $hash)) {
					return Result::ok($x);
				} else {
					return Result::error('Password incorrect!');
				}
			}

			return Result::ok($x);
		});
	}

	# Check that an array's length is within a certain range
	function minMaxChoices($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
			if(count($x) < $min) {
				return Result::error('Please choose at least ' . $min . ' options.');
			} else if(count($x) > $max) {
				return Result::error('At most ' . $max . ' choices are allowed.');
			}
			return Result::ok($x);
		});
	}

	# If a string is empty, don't bother validating it
	# (Unless the field is "required", in which case
	# requiredMaybe will deal with it)
	function maybeString() {
		return $this->innerBind(function($x) {
			if(trim($x) === '') {
				return Result::none($x);
			} else {
				return Result::ok($x);
			}
		});
	}

	# Check that a nubmer is within a certain range
	function minMaxNumber($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
			if($x < $min || $x > $max) {
				return Result::error('Number must be between ' . $min . ' and ' . $max . '.');
			}
			return Result::ok($x);
		});
	}

	# Check that a time (measured in minutes past midnight)
	# is within a certain range
	function minMaxTime($min, $max) {
		$compmin = $min === null ? 0 : self::timeToSeconds($min);
		$compmax = $max === null ? 86400 : self::timeToSeconds($max);

		// For text display
		if($min === null) {
			$min = '12:00 AM';
		}
		if($max === null) {
			$max = '11:59 PM';
		}

		return $this->innerBind(function($x) use($min, $max, $compmin, $compmax) {
			if($x < $compmin || $x > $compmax) {
				return Result::error('Time must be between ' . $min . ' and ' . $max . '.');
			}
			return Result::ok($x);
		});
	}

	# Check that a date/time is within a certain range
	function minMaxDateTime($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
			if($min !== null && $min->diff($x)->invert === 1) {
				return Result::error('Date must be after ' . $min->format('m/d/Y g:i a') . '.');
			}
			if($max !== null && $x->diff($max)->invert === 1) {
				return Result::error('Date must be before ' . $max->format('m/d/Y g:i a') . '.');
			}
			return Result::ok($x);
		});
	}

	# Check that a number is a multiple of another number
	function stepNumber($step) {
		if($step === 'any') {
			return $this;
		} else {
			return $this->innerBind(function($x) use ($step) {
				// Avoid floating point rounding errors
				$x = $step * round($x / $step);

				return Result::ok($x);
			});
		}
	}

	# Check that a time (measured in minutes past midnight)
	# is a multiple of a specific number
	function stepTime($step) {
		if($step === 'any') {
			return $this;
		} else {

			return $this->innerBind(function($x) use ($step) {

				if(($x % ( 60 * $step)) === 0) {
					return Result::ok($x);
				} else {
					return Result::error('Time must be a multiple of ' . $step . ' minutes.');
				}

			});
		}
	}

	# Check that the "time" component of a date/time is a multiple
	# of a specific number
	function stepDateTime($step) {
		if($step === 'any') {
			return $this;
		} else {
			return $this->innerBind(function($date) use ($step) {

				$seconds = ($date->format('G') * 3600) + ($date->format('i') * 60);

				if(($seconds % ( 60 * $step)) === 0) {
					return Result::ok($date);
				} else {
					return Result::error('Time must be a multiple of ' . $step . ' minutes.');
				}

			});
		}
	}

	# Validate a CAPTCHA using reCAPTCHA
	function checkCaptcha()  {
		return $this->innerBind(function($v) {
			$recaptcha = new \ReCaptcha\ReCaptcha(Config::get()['recaptcha']['secret-key']);
				$resp = $recaptcha->verify($v, $_SERVER['REMOTE_ADDR']);
				if ($resp->isSuccess()) {
				    return Result::ok(null);
				} else {
				    return Result::error('Please prove that you are human.');
				}
		});
	}

	# Upon success, don't store anything in the database
	function noStore() {
		return $this->innerBind(function($v) {
			return Result::ok([]);
		});
	}

	# Pull a specific field out of an array and validate that.
	# In general, this means: get the $_POST data corresponding
	# to a particular form field.
	function byName($name) {
		return $this->innerBind(function($v) use($name) {
			return Result::ok(isget($v[$name]));
		});
	}

	# Put the value into an array with the given key.
	# Generally, this means: allow the data to be merged with other
	# data and stored in the database.
	function name($name) {
		return $this
			->collapse()
			->ifSuccess(function($r) use($name) {
				return Result::ok([$name => $r]);
			})
			->ifError(function($r) use($name)  {
				return Result::error([$name => $r]);
			});
	}


	# Validate all of the form fields associated with a "list" element.

	# This is made complicated by the horribly complicated format in which HTML
	# forms send data to the server, and by the horribly complicated way in which
	# PHP handles this data.

	# See the getSubmissionPart() method in ListComponent for some context.

	function listValidate($minItems, $maxItems, $name, $items) {
		return $this->innerBind(function($list) use ($minItems, $maxItems, $name, $items) {

			$result = Result::ok([]);


			# Get the indices of the list to test
			$indices = array_unique( array_merge( array_keys($list[0]), array_keys($list[1]) ) );
			sort($indices);

			# First, determine how many items are in the list, in total.
			$number = count($indices);

			# Make sure the number of items provided is between $minItems and $maxItems
			if($number < $minItems) {
				return Result::error([ $name => 'Please provide at least ' . $minItems . ' items' ]);
			}
			if($number > $maxItems) {
				return Result::error([ $name => 'Please provide at most ' . $maxItems . ' items' ]);
			}


			# For each item in the list...
			foreach($indices as $index) {

				# Validate all of the fields within the list
				$validationResult =
					Result::ok(
						new ClientData(
							isget($list[0][$index], []),
							isget($list[1][$index], [])
						)
					)->groupValidate($items);



				# Combine the result of this validation with the data validated
				# in previous iterations of the loop.
				$result = $result
					->innerBind(function($soFar) use($validationResult, $index) {

						return $validationResult
							->innerBind(function($fieldResult) use($soFar, $index) {
								# Combine two successes
								$soFar[$index] = $fieldResult;
								return Result::ok($soFar);
							})
							->ifError(function($fieldError) {
								# If validation fails in one iteration, the end result
								# must be an error.
								return Result::error([]);
							});
					})
					->ifError(function($errorSoFar) use($validationResult, $index, $name) {
						return $validationResult
							->ifError(function($fieldError) use($errorSoFar, $index, $name) {
								# Combine two errors, putting things in a format the client JS code can understand.
								foreach($fieldError as $k => $v) {

									$k = explode('[', $k);
									$kStart = $k[0];
									$kRest = (count($k) > 1) ?
										'[' . implode('[', array_slice($k, 1)) :
										'';

									$errorSoFar[ $name . '[' . $index . '][' . $kStart . ']' . $kRest  ] = $v;
								}

								return Result::error($errorSoFar);
							})
							->innerBind(function($fieldResult) use($errorSoFar) {
								# Error + success = original error
								return Result::error($errorSoFar);
							});

					});
			}
			# If it's a success, name the resulting data properly
			# for storage in the database.
			$result = $result
				->innerBind(function($x) use($name) {
					return Result::ok([$name => array_values($x)]);
				});
			return $result;
		});
	}


	# This function validates a group of fields.
	# It passes the data to be validated (usually a ClientData object)
	# through each of the fields in turn.

	# If it hits any errors, it returns an array of names => associated
	# error messages. If it does not, it returns the data to be stored
	# in Mongo (or formatted and set by email, etc.)

	# Used by the "group" and "fields" elements.
	function groupValidate($items) {
		return $this->innerBind(function($val) use ($items) {
			return array_reduce($items, function($total, $field) use($val) {
				if(!($field instanceof Storeable)) {
					return $total;
				}
				return $field
					->getSubmissionPart(
						Result::ok($val)
					)
					->collapse()
					->ifSuccess(function($r) {
						return Result::ok(function($total) use ($r) {
							return array_merge($r, $total);
						});
					})
					->ifError(function($r) {
						return Result::error(function($total) use ($r) {
							return array_merge($r, $total);
						});
					})
					->ifError(function($merge) use($total) {
						return $total
							->innerBind(function($x) {
								return Result::error([]);
							})
							->ifError(function($x) use ($merge) {
								return Result::error($merge($x));
							});
					})
					->innerBind(function($merge) use($total) {
						return $total
							->innerBind(function($x) use ($merge) {
								return Result::ok($merge($x));
							});
					});
			}, Result::ok([]));
		});
	}
}

# Below we define the data types used to store and manage form submission data.

# What we need is a type with three variants, so to speak:
# - One to store values that have been successfully validated and converted
# - One to store values that have failed to validate properly
# - One to store values that were never provided (this is especially useful
#   when we need to make sure "required" fields are provided).

# Below, we first describe two types that are basically monads; they are used
# to represent successes and failures, respectively. Though this provides a
# quite elegant approach to the problem, it only gives us *two* types -- not
# three, as we want.

# In order to get three, we combine these two types together. In particular:
# - Successful values are stored as a Success within a Success
# - Absent values are stored as a Failure within a Success
# - Invalid values are stored as a Failure

# We use Result -- a class with only static methods -- to produce these
# three types.

# To make it easier to work with values having these three types,
# three methods in the Validate class are particularly important:
# bindNothing(), innerBind(), and collapse(). These methods are discussed above.

# This method, though rather complicated, does give us what we want --
# a type with three separate variants but with the elegance provided
# by a monadic approach.

# Monad transformers (here, EitherT) may allow this to be simplified.
# This may be something to investigate in the future, if this codebase
# becomes important :)

class Success extends Validate {
	private $value;
	function __construct($value) {
		$this->value = $value;
	}
	function ifSuccess(callable $x) {
		return $x($this->value);
	}
	function ifError(callable $x) {
		return $this;
	}
}

class Failure extends Validate  {
	private $value;
	function __construct($value) {
		$this->value = $value;
	}
	function ifSuccess(callable $x) {
		return $this;
	}
	function ifError(callable $x) {
		return $x($this->value);
	}
}

class Result {
	static function ok($x) {
		return new Success(new Success($x));
	}
	static function none($x) {
		return new Success(new Failure($x));
	}
	static function error($x) {
		return new Failure($x);
	}
}