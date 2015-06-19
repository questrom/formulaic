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
			if($x === '' || $addr !== false) {
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
				if($x === '') {
					return new OkNothing();
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

			// var_dump($x, $integer);

			if($num !== false) {
				return new OkJust($num);
			} else {
				return new Err('Invalid number.');
			}
		});
	}
	// Required checkers
	function requiredBoolean($enable) {
		return $this->innerBind(function($x) use ($enable) {
			if($enable && !$x) {
				return new Err('Please check this box before submitting the form.');
			}
			return new OkJust($x);
		});
	}
	function requiredMaybe($enable) {
		return $this->bind(function($x) use ($enable) {
			if($enable && $x instanceof Nothing) {
				return new Err('This field is required.');
			} else if ($x instanceof Nothing) {
				return new OkNothing();
			} else {
				return new OkJust($x->get());
			}
		});
	}
	function requiredChoice($enable) {
		return $this->innerBind(function($x) use ($enable) {
				if($enable && count($x) === 0) {
					return new Err('This field is required.');
				}
				return new OkJust($x);
			});
	}
	function requiredString($enable) {
		return $this->innerBind(function($x) use ($enable) {
			if($enable && trim($x) === '') {
				return new Err('This field is required.');
			}
			return new OkJust($x);
		});
	}
	// More sophisticated checks
	function mustHaveDomain($checkDomain) {
		return $this->innerBind(function($x) use ($checkDomain) {
			if($x !== '' && $checkDomain !== null) {
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
	function __construct() {}
	function get() {
		return null;
	}
	function bind(callable $x) {
		return $x(new Nothing());
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