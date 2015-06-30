<?php

abstract class Validate {
	// Type filters
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
	function filterString() {
		return $this->innerBind(function($x) {
			if(is_string($x)) {
				return Result::ok($x);
			} else {
				return Result::error('Invalid data!');
			}
		});
	}
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
	function filterChosenFromOptions($options) {
		return $this->innerBind(function($x) use($options) {
				if($x === '' || $x === null) {
					return Result::none(null);
				} else if(in_array($x, $options, TRUE)) {
					return Result::ok($x);
				} else {
					return Result::error('Invalid data!');
				}
			});
	}
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
	static function timeToSeconds($x) {

		$date = DateTimeImmutable::createFromFormat('g:i a', $x);

		return 	($date->format('G') * 3600) + ($date->format('i') * 60);
	}
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
	function filterEmptyString() {
		return $this->innerBind(function($x) {
			if(trim($x) === '') {
				return Result::none($x);
			} else {
				return Result::ok($x);
			}
		});
	}
	function filterNoChoices() {
		return $this->innerBind(function($x) {
				if(count($x) === 0) {
					return Result::none($x);
				}
				return Result::ok($x);
			});
	}
	// Required checkers
	function requiredMaybe($enable) {
		return $this->bindNothing(function($x) use ($enable) {
			if($enable) {
				return Result::error('This field is required.');
			} else {
				return Result::none($x);
			}
		});
	}
	// More sophisticated checks
	function mustBeTrue($enable) {
		return $this->innerBind(function($x) use ($enable) {
			if($enable && !$x) {
				return Result::error('Please check this box before submitting the form.');
			}
			return Result::ok($x);
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
					return Result::error('Domain must equal: ' . $checkDomain . '.');
				}
			}
			return Result::ok($x);
		});
	}
	function minMaxDate($minDate, $maxDate) {
		return $this->innerBind(function($x) use ($minDate, $maxDate) {
				if($minDate !== null && $minDate > $x) {
					return Result::error('Please enter a date starting at ' . $minDate->format('Y-m-d'));
				} else if($maxDate !== null && $maxDate < $x) {
					return Result::error('Please enter a date ending at ' . $maxDate->format('Y-m-d'));
				} else {
					return Result::ok($x);
				}
			});
	}
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
	function matchRegex($regex) {
		return $this->innerBind(function($x) use ($regex) {
			if($regex !== null && preg_match($regex, $x) === 0) {
				return Result::error('Invalid input!');
			}
			return Result::ok($x);
		});
	}
	function matchHash($hash) {
		return $this->innerBind(function($x) use ($hash) {
			if($hash !== null) {
				if(password_verify($x, $hash)) {
					return Result::ok(null);
				} else {
					return Result::error('Password incorrect!');
				}
			}

			return Result::ok($x);
		});
	}
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
	function maybeString() {
		return $this->innerBind(function($x) {
				if(trim($x) === '') {
					return Result::none($x);
				} else {
					return Result::ok($x);
				}
			});
	}
	function minMaxNumber($min, $max) {
		return $this->innerBind(function($x) use($min, $max) {
			if($x < $min || $x > $max) {
				return Result::error('Number must be between ' . $min . ' and ' . $max . '.');
			}
			return Result::ok($x);
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
				return Result::error('Time must be between ' . $min . ' and ' . $max . '.');
			}
			return Result::ok($x);
		});
	}
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

	// Utility methods
	function collapse() {
		return $this->bindNothing(function($x) {
			return Result::ok($x);
		});
	}
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
}


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