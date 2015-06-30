<?php

abstract class Validate {
	// Type filters
 	function filterBoolean() {
		return $this->innerBind(function($x) {
			$value = filter_var($x, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

			if($value === true) {
				return okJust(true);
			} else if($value === false) {
				return okJust(false);
			} else {
				return new Failure('Invalid data!');
			}
		});
	}
	function filterString() {
		return $this->innerBind(function($x) {
			if(is_string($x)) {
				return okJust($x);
			} else {
				return new Failure('Invalid data!');
			}
		});
	}
	function filterFilterVar($cons, $msg) {
		return $this->innerBind(function($x) use ($cons, $msg) {
			$addr = filter_var($x, $cons);
			if($addr !== false) {
				return okJust($addr);
			} else {
				return new Failure($msg);
			}
		});
	}
	function filterChosenFromOptions($options) {
		return $this->innerBind(function($x) use($options) {
				if($x === '' || $x === null) {
					return emptyResult(null);
				} else if(in_array($x, $options, TRUE)) {
					return okJust($x);
				} else {
					return new Failure('Invalid data!');
				}
			});
	}
	function filterManyChosenFromOptions($options) {
		return $this->innerBind(function($x) use($options) {
			if($x === null) {
				return okJust([]);
			} else if(is_array($x) && count(array_diff($x, $options)) === 0 ) {
				return okJust($x);
			} else {
				return new Failure('Invalid data!');
			}
		});
	}
	function filterDate() {
		return $this->innerBind(function($x) {

				if(trim($x) == '') {
					return emptyResult(null);
				}

				$date = DateTimeImmutable::createFromFormat('m/d/Y', $x);


				if($date !== false) {
					$date = $date->setTime(0, 0, 0);
					return okJust($date);
				} else {
					return new Failure('Invalid date!');
				}
			});
	}
	static function timeToSeconds($x) {

		$date = DateTimeImmutable::createFromFormat('g:i a', $x);

		return 	($date->format('G') * 3600) + ($date->format('i') * 60);
	}
	function filterTime() {
		return $this->innerBind(function($x) {

			if(trim($x) == '') {
				return emptyResult(null);
			}

			$date = DateTimeImmutable::createFromFormat('g:i a', $x);

			if($date !== false) {
				$seconds = ($date->format('G') * 3600) + ($date->format('i') * 60);
				return okJust($seconds);
			} else {
				return new Failure('Invalid time!');
			}
		});
	}
	function filterDateTime() {
		return $this->innerBind(function($x) {

			if(trim($x) == '') {
				return emptyResult(null);
			}

			$date = DateTimeImmutable::createFromFormat('m/d/Y g:i a', $x);

			if($date !== false) {
				return okJust($date);
			} else {
				return new Failure('Invalid time!');
			}
		});
	}
	function filterPhone() {
		return $this->innerBind(function($x) {
			$phn = preg_replace('/[^x+0-9]/', '', $x);
			if(strlen($phn) >= 10 || $x === '') {
				return okJust($phn);
			} else {
				return new Failure('Invalid phone number.');
			}
		});
	}
	function filterNumber($integer) {
		return $this->innerBind(function($x) use ($integer) {

			if($integer) {
				$num = filter_var($x, FILTER_VALIDATE_INT);
			} else {
				$num = filter_var($x, FILTER_VALIDATE_FLOAT);
			}

			if($num !== false) {
				return okJust($num);
			} else {
				return new Failure('Invalid number.');
			}
		});
	}
	function filterEmptyString() {
		return $this->innerBind(function($x) {
			if(trim($x) === '') {
				return emptyResult($x);
			} else {
				return okJust($x);
			}
		});
	}
	function filterNoChoices() {
		return $this->innerBind(function($x) {
				if(count($x) === 0) {
					return emptyResult($x);
				}
				return okJust($x);
			});
	}
	// Required checkers
	function requiredMaybe($enable) {
		return $this->bindNothing(function($x) use ($enable) {
			if($enable) {
				return new Failure('This field is required.');
			} else {
				return emptyResult($x);
			}
		});
	}
	// More sophisticated checks
	function mustBeTrue($enable) {
		return $this->innerBind(function($x) use ($enable) {
			if($enable && !$x) {
				return new Failure('Please check this box before submitting the form.');
			}
			return okJust($x);
		});
	}
	function mustHaveDomain($checkDomain) {
		return $this->innerBind(function($x) use ($checkDomain) {
			if($checkDomain !== null) {
				// The simplest way, according to http://stackoverflow.com/questions/6917198/
				// This seems overly simple, but apparently it works
				$domain = explode('@', $x);
				$domain = array_pop($domain);

				if($domain !== $checkDomain) {
					return new Failure('Domain must equal: ' . $checkDomain . '.');
				}
			}
			return okJust($x);
		});
	}
	function minMaxDate($minDate, $maxDate) {
		return $this->innerBind(function($x) use ($minDate, $maxDate) {
				if($minDate !== null && $minDate > $x) {
					return new Failure('Please enter a date starting at ' . $minDate->format('Y-m-d'));
				} else if($maxDate !== null && $maxDate < $x) {
					return new Failure('Please enter a date ending at ' . $maxDate->format('Y-m-d'));
				} else {
					return okJust($x);
				}
			});
	}
	function minMaxLength($minLength, $maxLength) {
		return $this->innerBind(function($x) use ($minLength, $maxLength) {
			if(strlen($x) > $maxLength) {
				return new Failure('The input is too long. Maximum is ' . $maxLength . ' characters.');
			} else if(strlen($x) < $minLength) {
				return new Failure('The input is too short. Minimum is ' . $minLength . ' characters.');
			} else {
				return okJust($x);
			}
		});
	}
	function matchRegex($regex) {
		return $this->innerBind(function($x) use ($regex) {
			if($regex !== null && preg_match($regex, $x) === 0) {
				return new Failure('Invalid input!');
			}
			return okJust($x);
		});
	}
	function matchHash($hash) {
		return $this->innerBind(function($x) use ($hash) {
			if($hash !== null) {
				if(password_verify($x, $hash)) {
					return okJust(null);
				} else {
					return new Failure('Password incorrect!');
				}
			}

			return okJust($x);
		});
	}
	function minMaxChoices($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
				if(count($x) < $min) {
					return new Failure('Please choose at least ' . $min . ' options.');
				} else if(count($x) > $max) {
					return new Failure('At most ' . $max . ' choices are allowed.');
				}
				return okJust($x);
			});
	}
	function maybeString() {
		return $this->innerBind(function($x) {
				if(trim($x) === '') {
					return emptyResult($x);
				} else {
					return okJust($x);
				}
			});
	}
	function minMaxNumber($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
			if($x < $min || $x > $max) {
				return new Failure('Number must be between ' . $min . ' and ' . $max . '.');
			}
			return okJust($x);
		});
	}
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
				return new Failure('Time must be between ' . $min . ' and ' . $max . '.');
			}
			return okJust($x);
		});
	}
	function minMaxDateTime($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
			if($min !== null && $min->diff($x)->invert === 1) {
				return new Failure('Date must be after ' . $min->format('m/d/Y g:i a') . '.');
			}
			if($max !== null && $x->diff($max)->invert === 1) {
				return new Failure('Date must be before ' . $max->format('m/d/Y g:i a') . '.');
			}
			return okJust($x);
		});
	}
	function stepNumber($step) {
		if($step === 'any') {
			return $this;
		} else {
			return $this->innerBind(function($x) use ($step) {
				// Avoid floating point rounding errors
				$x = $step * round($x / $step);

				return okJust($x);
			});
		}
	}
	function stepTime($step) {
		if($step === 'any') {
			return $this;
		} else {

			return $this->innerBind(function($x) use ($step) {

				if(($x % ( 60 * $step)) === 0) {
					return okJust($x);
				} else {
					return new Failure('Time must be a multiple of ' . $step . ' minutes.');
				}

			});
		}
	}
	function stepDateTime($step) {
		if($step === 'any') {
			return $this;
		} else {
			return $this->innerBind(function($date) use ($step) {

				$seconds = ($date->format('G') * 3600) + ($date->format('i') * 60);

				if(($seconds % ( 60 * $step)) === 0) {
					return okJust($date);
				} else {
					return new Failure('Time must be a multiple of ' . $step . ' minutes.');
				}

			});
		}
	}

	// Utility methods
	function collapse() {
		return $this->bindNothing(function($x) {
			return new Success(new Success($x));
		});
	}
	function innerBind(callable $x) {
		return $this->bind(function($val) use($x) {
			if($val instanceof Failure) {
				return new Success($val);
			} else {
				return $val->bind($x);
			}
		});
	}
	function bindNothing(callable $x) {
		return $this->bind(function($val) use($x) {

			if($val instanceof Failure) {
				return $val->bind_err($x);
			} else {
				return new Success($val);
			}
		});
	}
}


class Success extends Validate {
	function __construct($value) {
		$this->value = $value;
	}
	function bind(callable $x) {
		return $x($this->value);
	}
	function bind_err(callable $x) {
		return $this;
	}
}

class Failure extends Validate  {
	function __construct($value) {
		$this->value = $value;
	}
	function bind(callable $x) {
		return $this;
	}
	function bind_err(callable $x) {
		return $x($this->value);
	}
}

function okJust($x) {
	return new Success(new Success($x));
}

function emptyResult($x) {
	return new Success(new Failure($x));
}
