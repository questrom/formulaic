<?php

abstract class Validate extends Result {
	// Type filters
 	function filterBoolean() {
		return $this->innerBind(function($x) {
			$value = filter_var($x, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

			if($value === true) {
				return new OkJust(true);
			} else if($value === false) {
				return new OkJust(false);
			} else {
				return new Err('Invalid data!');
			}
		});
	}
	function filterString() {
		return $this->innerBind(function($x) {
			if(is_string($x)) {
				return new OkJust($x);
			} else {
				return new Err('Invalid data!');
			}
		});
	}
	function filterFilterVar($cons, $msg) {
		return $this->innerBind(function($x) use ($cons, $msg) {
			$addr = filter_var($x, $cons);
			if($addr !== false) {
				return new OkJust($addr);
			} else {
				return new Err($msg);
			}
		});
	}
	function filterChosenFromOptions($options) {
		return $this->innerBind(function($x) use($options) {
				if($x === '' || $x === null) {
					return new OkNothing();
				} else if(in_array($x, $options, TRUE)) {
					return new OkJust($x);
				} else {
					return new Err('Invalid data!');
				}
			});
	}
	function filterManyChosenFromOptions($options) {
		return $this->innerBind(function($x) use($options) {
			if($x === null) {
				return new OkJust([]);
			} else if(is_array($x) && count(array_diff($x, $options)) === 0 ) {
				return new OkJust($x);
			} else {
				return new Err('Invalid data!');
			}
		});
	}
	function filterDate() {
		return $this->innerBind(function($x) {
				
				if(trim($x) == '') {
					return new OkNothing(null);
				}

				$date = DateTimeImmutable::createFromFormat('Y-m-d', $x);
				$date = $date->setTime(0, 0, 0);

				if($date !== false) {
					return new OkJust($date);
				} else {
					return new Err('Invalid date!');
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
				return new OkNothing(null);
			}
				
			$date = DateTimeImmutable::createFromFormat('g:i a', $x);
			
			if($date !== false) {
				$seconds = ($date->format('G') * 3600) + ($date->format('i') * 60);
				return new OkJust($seconds);
			} else {
				return new Err('Invalid time!');
			}
		});
	}
	function filterPhone() {
		return $this->innerBind(function($x) {
			$phn = preg_replace('/[^x+0-9]/', '', $x);
			if(strlen($phn) >= 10 || $x === '') {
				return new OkJust($phn);
			} else {
				return new Err('Invalid phone number.');
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
				return new OkJust($num);
			} else {
				return new Err('Invalid number.');
			}
		});
	}
	function filterEmptyString() {
		return $this->innerBind(function($x) {
			if(trim($x) === '') {
				return new OkNothing($x); 
			} else {
				return new OkJust($x);
			}
		});
	}
	function filterNoChoices() {
		return $this->innerBind(function($x) {
				if(count($x) === 0) {
					return new OkNothing($x);
				}
				return new OkJust($x);
			});
	}
	// Required checkers
	function requiredMaybe($enable) {
		return $this->bind(function($x) use ($enable) {
			if($enable && $x instanceof Nothing) {
				return new Err('This field is required.');
			} else if ($x instanceof Nothing) {
				return new OkNothing($x->get());
			} else {
				return new OkJust($x->get());
			}
		});
	}
	// More sophisticated checks
	function mustBeTrue($enable) {
		return $this->innerBind(function($x) use ($enable) {
			if($enable && !$x) {
				return new Err('Please check this box before submitting the form.');
			}
			return new OkJust($x);
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
					return new Err('Domain must equal: ' . $checkDomain . '.');
				}
			}
			return new OkJust($x);
		});
	}
	function minMaxDate($minDate, $maxDate) {
		return $this->innerBind(function($x) use ($minDate, $maxDate) {
				if($minDate !== null && $minDate > $x) {
					return new Err('Please enter a date starting at ' . $minDate->format('Y-m-d'));
				} else if($maxDate !== null && $maxDate < $x) {
					return new Err('Please enter a date ending at ' . $maxDate->format('Y-m-d'));
				} else {
					return new OkJust($x);
				}
			});
	}
	function minMaxLength($minLength, $maxLength) {
		return $this->innerBind(function($x) use ($minLength, $maxLength) {
			if(strlen($x) > $maxLength) {
				return new Err('The input is too long. Maximum is ' . $maxLength . ' characters.');
			} else if(strlen($x) < $minLength) {
				return new Err('The input is too short. Minimum is ' . $minLength . ' characters.');
			} else {
				return new OkJust($x);
			}
		});
	}
	function matchRegex($regex) {
		return $this->innerBind(function($x) use ($regex) {
			if($regex !== null && preg_match($regex, $x) === 0) {
				return new Err('Invalid input!');
			}
			return new OkJust($x);
		});
	}
	function matchHash($hash) {
		return $this->innerBind(function($x) use ($hash) {
			if($hash !== null) {
				if(password_verify($x, $hash)) {
					return new OkJust(null);
				} else {
					return new Err('Password incorrect!');
				}
			}
			
			return new OkJust($x);
		});
	}
	function minMaxChoices($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
				if(count($x) < $min) {
					return new Err('Please choose at least ' . $min . ' options.');
				} else if(count($x) > $max) {
					return new Err('At most ' . $max . ' choices are allowed.');
				}
				return new OkJust($x);
			});
	}
	function maybeString() {
		return $this->innerBind(function($x) {
				if(trim($x) === '') {
					return new OkNothing();
				} else {
					return new OkJust($x);
				}
			});
	}
	function minMaxNumber($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
			if($x < $min || $x > $max) {
				return new Err('Number must be between ' . $min . ' and ' . $max . '.');
			}
			return new OkJust($x);
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
				return new Err('Time must be between ' . $min . ' and ' . $max . '.');
			}
			return new OkJust($x);
		});
	}
	function stepNumber($step) {
		if($step === 'any') {
			return $this;
		} else {
			return $this->innerBind(function($x) use ($step) {
				
				// Avoid floating point rounding errors
				$x = $step * round($x / $step);

				return new OkJust($x);
			});
		}
	}
	function stepTime($step) {
		if($step === 'any') {
			return $this;
		} else {
			return $this->innerBind(function($x) use ($step) {

				if(($x % ( 60 * $step)) === 0) {
					return new OkJust($x);					
				} else {
					return new Err('Time must be a multiple of ' . $step . ' minutes.');
				}

			});
		}
	}
}


class Err extends Validate {
	private $value;
	function __construct($value) {
		$this->value = $value;
	}
	function get() {
		return $this->value;
	}
	function bind(callable $x) {
		return $this;
	}
	function bind_err(callable $x) {
		return $x($this->value);
	}
	function innerBind(callable $x) {
		return $this;
	}
}

class OkNothing extends Validate {
	function __construct($value = null) {
		$this->value = $value;
	}
	function get() {
		return $this->value;
	}
	function bind(callable $x) {
		return $x(new Nothing($this->value));
	}
	function bind_err(callable $x) {
		return $this;
	}

	function innerBind(callable $x) {
		return $this;
	}
}

class OkJust  extends Validate {
	private $value;
	function __construct($value) {
		$this->value = $value;
	}
	function get() {
		return $this->value;
	}
	function bind(callable $x) {
		return $x(new Just($this->value));
	}
	function bind_err(callable $x) {
		return $this;
	}
	function innerBind(callable $x) {
		return $x($this->value);
	}
}