<?php

# These classes are mostly components: that is, Configurables that can create Renderables.
# Each one corresponds to an element in the configruation file.

# The __construct($args) method, which is needed to implement Configurable,
# creates an instance of the component from attributes given in the configuration file.

# The "show-if" element
class ShowIfComponent implements FormPartFactory, Storeable, Configurable {

	function __construct($args) {
		$this->condition = $args['children'][0];
		$this->item = $args['children'][1];
	}

	function getAllFields() {
		if ($this->item instanceof Storeable) {
			return $this->item->getAllFields();
		} else {
			# In case the field to be hidden/shown is, say, a header.
			return [];
		}
	}

	function makeFormPart() {
		return new ShowIfComponentFormPart((object) [
			'item' => $this->item->makeFormPart(),
			'condition' => $this->condition
		]);
	}
	function makeGroupPart() {
		return new ShowIfComponentFormPart((object) [
			'item' => $this->item->makeGroupPart(),
			'condition' => $this->condition
		]);
	}

	function getSubmissionPart($val) {
		return $val
			->collapse()
			->ifSuccess(function ($val) {
				if (!($this->condition->evaluate($val))) {
					# Don't submit anything if the form field was hidden
					return Result::ok([]);
				} else {
					return Result::ok($val)->groupValidate([$this->item]);
				}
			});
	}
}

# The "checkbox" element
class Checkbox extends NamedLabeledComponent implements Enumerative {
	function __construct($args) {
		parent::__construct($args);

		# This attribute that the box MUST be checked

		# There is no "required" attribute for checkboxes,
		# since they would duplicate the functionality of "must-check."
		$this->mustCheck = isset($args['must-check']);
	}

	function makeFormPart() {
		return new CheckboxFormPart($this);
	}

	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterBoolean()
			->mustBeTrue($this->mustCheck)
			->name($this->name);
	}

	function getPossibleValues() {
		return [true, false];
	}

	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new CheckboxTableCell($v);
	}
}

# The "time" element
class TimeInput extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->max = isset($args['max']) ? $args['max'] : null;
		$this->min = isset($args['min']) ? $args['min'] : null;
		$this->step = isset($args['step']) ? intval($args['step']) : 'any';
	}
	function makeFormPart() {
		return new TimeInputFormPart($this);
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterTime()
			->requiredMaybe($this->required)
			->minMaxTime($this->min, $this->max)
			->stepTime($this->step)
			->name($this->name);
	}
	function makeTableViewPart($v) {
		# Convert a time for display in a table view.
		if ($v === null) {
			# In case there isn't any value.
			return null;
		}
		$hour = floor($v / 3600);
		$minute = ($v % 3600) / 60;
		$xm = 'am';
		if ($hour > 11) {
			$xm = 'pm';
			$hour -= 12;
		}
		if (intval($hour) === 0) {
			$hour = 12;
		}
		return new OrdinaryTableCell(sprintf('%d:%02d %s', $hour, $minute, $xm));
	}
}


# The "datetime" element
class DateTimePicker extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('Y-m-d g:i a', $args['max']) : null;
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('Y-m-d g:i a', $args['min']) : null;
		$this->step = isset($args['step']) ? $args['step'] : 'any';
	}
	function makeFormPart() {
		return new DateTimePickerFormPart($this);
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterDateTime()
			->requiredMaybe($this->required)
			->minMaxDateTime($this->min, $this->max)
			->stepDateTime($this->step)
			->name($this->name);
	}
	function makeTableViewPart($v) {
		if ($v === null || !is_object($v)) {
			return null;
		}
		return new OrdinaryTableCell($v->format('n/j/Y g:i A'));
	}
}

# The "textarea" element
class Textarea extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->maxLength = isset($args['max-length']) ? intval($args['max-length']) : INF;
		$this->minLength = isset($args['min-length']) ? intval($args['min-length']) : 0;
		$this->required = isset($args['required']);
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;

	}
	function makeFormPart() {
		return new TextareaFormPart($this);
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterString()
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required)
			->name($this->name);
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new TextareaTableCell($v);
	}
}

# The "dropdown" element
class Dropdown extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->options = $args['children'];
		$this->required = isset($args['required']);
	}
	function makeFormPart() {
		return new DropdownFormPart($this);
	}
	function makeTableViewPart($v) {
		return new OrdinaryTableCell($v);
	}


	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterChosenFromOptions($this->options)
			->requiredMaybe($this->required)
			->name($this->name);
	}
}

# The "radios" element
class Radios extends NamedLabeledComponent implements Enumerative {
	function __construct($args) {
		parent::__construct($args);

		$this->options = $args['children'];
		$this->required = isset($args['required']);
	}
	function makeFormPart() {
		return new RadiosFormPart($this);
	}
	function makeTableViewPart($v) {
		return new OrdinaryTableCell($v);
	}
	function getPossibleValues() {
		return $this->options;
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterChosenFromOptions($this->options)
			->requiredMaybe($this->required)
			->name($this->name);
	}
}

# The "checkboxes" element
class Checkboxes extends NamedLabeledComponent implements Enumerative {
	function __construct($args) {
		parent::__construct($args);
		$this->options = $args['children'];

		$this->required = isset($args['required']);
		$this->minChoices = isset($args['min-choices']) ? intval($args['min-choices']) : 0;
		$this->maxChoices = isset($args['max-choices']) ? intval($args['max-choices']) : INF;
	}
	function makeFormPart() {
		return new CheckboxesFormPart($this);
	}
	function getPossibleValues() {
		return $this->options;
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterManyChosenFromOptions($this->options)
			->minMaxChoices($this->minChoices, $this->maxChoices)
			->filterNoChoices()
			->requiredMaybe($this->required)
			->name($this->name);
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		if (count($v) === 0) {
			# Don't display anything if nothing was checked.
			return null;
		}
		return new ListTableCell($v);
	}
}

# The "captcha" element
class Captcha extends NamedLabeledComponent {
	function __construct($args) {
		# Google requires us to use this name for all CAPTCHA elements.
		# Hence, the CAPTCHA element lacks a "name" attribute.
		$this->name = 'g-recaptcha-response';
		$this->label = 'CAPTCHA';
	}
	function makeFormPart() {
		return new CaptchaFormPart($this);
	}
	function makeTableViewPart($v) {
		return new OrdinaryTableCell($v);
	}

	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->checkCaptcha()
			->name($this->name)
			# Don't store the results of the submission in the database.

			# Presumably you don't need to determine what CAPTCHA
			# someone had to solve after the fact :)
			# Presumably...
			->noStore();
	}
}

# The "textbox" element
class Textbox extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->maxLength = isset($args['max-length']) ? intval($args['max-length']) : INF;
		$this->minLength = isset($args['min-length']) ? intval($args['min-length']) : 0;
		$this->required = isset($args['required']);
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
	}
	function makeFormPart() {
		return new InputFormPart($this, 'text', null);
	}
	function makeTableViewPart($v) {
		return new OrdinaryTableCell($v);
	}

	function getSubmissionPart($against) {
		# As with other "validate()" methods, this just chains together a bunch
		# of the methods on "Result" types -- see Validate.php for details
		return $against
			->post($this->name)
			->filterString()
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required)
			->name($this->name);
	}
}

# The "file" element
class FileUpload extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);
		$this->required = isset($args['required']);
		$this->allowedExtensions = array_reduce(
			array_map(function ($x) {
				return [$x->ext => $x->mime];
			}, $args['children']),
			'array_merge',
			[]
		);

		$this->maxSize = intval($args['max-size']);
		$this->permissions = $args['permissions'];

	}
	function makeFormPart() {
		$innerText = 'Allowed file types: ' . implode(', ', array_keys($this->allowedExtensions)) . ' ' . json_decode('"\u00B7"') .
			' Max file size: ' . ByteUnits\Metric::bytes($this->maxSize)->format(0);
		return new InputFormPart($this, 'file', null, null, $innerText);
	}
	function getSubmissionPart($against) {
		# Validating file inputs is a rather difficult task.
		# This solution is based on: http://php.net/manual/en/features.file-upload.php#114004

		# It should be pretty bullet-proof, which is important because
		# it is easy to create security vulnerabilities with file uploads.

		return $against
			->files($this->name)
			->ifOk(function ($val) {
				# See http://php.net/manual/en/features.file-upload.php
				if (!is_array($val) || !isset($val['error']) || is_array($val['error'])) {
					return Result::error('Invalid data.');
				} else {
					if ($val['error'] === UPLOAD_ERR_INI_SIZE || $val['error'] === UPLOAD_ERR_FORM_SIZE) {
						return Result::error('File size exceeds server or form limit.');
					} else {
						if ($val['error'] === UPLOAD_ERR_NO_FILE) {
							return Result::none(null);
						} else {
							if ($val['error'] === UPLOAD_ERR_OK) {
								return Result::ok($val);
							} else {
								return Result::error('Error uploading file.');
							}
						}
					}
				}
			})
			->requiredMaybe($this->required)
			->ifOk(function ($file) {
				if ($file['size'] > $this->maxSize) {
					return Result::error('File must be under ' . $this->maxSize . ' bytes in size.');
				} else {
					return Result::ok($file);
				}
			})
			->ifOk(function ($file) {

				$finfo = new finfo(FILEINFO_MIME_TYPE);
				$mime = $finfo->file($file['tmp_name']);

				$ext = array_search(
					$mime,
					$this->allowedExtensions,
					true
				);

				if ($ext === false) {
					return Result::error('Invalid file type or wrong MIME type. Allowed extensions are: ' .
						implode(', ', array_keys($this->allowedExtensions)) . '.');
				}

				if (!is_uploaded_file($file['tmp_name'])) {
					return Result::error('Security error.');
				}


				$filename = sha1_file($file['tmp_name']) . '-' . floor(microtime(true)) . '.' . $ext;

				return Result::ok(new FileInfo($file, $filename, $mime, $this->permissions));
			})
			->name($this->name);
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		if (is_string($v) && !isset($v['url'])) {
			# In case the file upload is from an older version of the app,
			# which stored things in a rather unsual manner
			return null;
		}
		return new FileUploadTableCell($v);
	}
	function makeDetailsViewPart($v) {

		if ($v === null) {
			return null;
		}

		if (is_string($v) || !isset($v['url'])) {
			# In case the file upload is from an older version of the app,
			# which stored things in a rather unsual manner
			return null;
		}
		return new FileUploadDetailedTableCell($v);

	}
	function makeEmailViewPart($v) {

		if ($v === null) {
			return null;
		}

		if (is_string($v) || !isset($v['url'])) {
			# In case the file upload is from an older version of the app,
			# which stored things in a rather unsual manner
			return null;
		}
		return new FileUploadDetailedTableCell($v);
	}
}

# The "range" element
class Range extends NamedLabeledComponent {
	function __construct($args) {

		parent::__construct($args);

		$this->max = isset($args['max']) ? intval($args['max']) : 1;
		$this->min = isset($args['min']) ? intval($args['min']) : 0;
		$this->step = isset($args['step']) ? $args['step'] : 'any';
		$this->def = isset($args['default']) ? intval($args['default']) : midpoint($this->min, $this->max);
	}
	function makeFormPart() {
		return new RangeFormPart($this);
	}
	function makeTableViewPart($v) {
		return new OrdinaryTableCell($v);
	}

	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterString()
			->filterNumber(false)
			->minMaxNumber($this->min, $this->max)
			->stepNumber($this->step)
			->name($this->name);
	}
}


# The "password" element
class Password extends NamedLabeledComponent {

	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);

		# Strictly speaking, "match-hash" isn't required, but there's no real reason
		# not to set it.
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;

		if (isset($args['match-hash'])) {
			$this->required = true;
		}
	}
	function makeFormPart() {
		return new InputFormPart($this, 'password', null);
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterString()
			->matchHash(isset($this->matchHash) ? $this->matchHash : null)
			->filterEmptyString()
			->requiredMaybe($this->required)
			->name($this->name)
			# Don't store passwords in the DB, as they are in plaintext
			# and would present a security risk.
			->noStore();
	}
	function makeTableViewPart($v) {
		return new PasswordTableCell();
	}
}

# The "phone" element
class PhoneNumber extends NamedLabeledComponent {

	function __construct($args) {
		parent::__construct($args);
		$this->required = isset($args['required']);
	}
	function makeFormPart() {
		return new InputFormPart($this, 'tel', 'call');
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterPhone()
			->name($this->name);
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		# Format the phone number with pretty unicode spaces
		# before displaying it in a table
		if (preg_match('/^[0-9]{10}$/', $v)) {
			$showValue = '(' . substr($v, 0, 3) . ')' . json_decode('"\u2006"') . substr($v, 3, 3) . json_decode('"\u2006"') . substr($v, 6, 4);
		} else {
			$showValue = $v;
		}
		return new LinkTableCell('tel:' . $v, $showValue);
	}
}

# The "email" element
class EmailAddr extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->mustHaveDomain = isset($args['must-have-domain']) ? $args['must-have-domain'] : null;
	}
	function makeFormPart() {
		$innerText = isset($this->mustHaveDomain) ? ('Must be @' . $this->mustHaveDomain) : null;
		return new InputFormPart($this, 'email', 'mail', null, $innerText);
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterFilterVar(FILTER_VALIDATE_EMAIL, 'Invalid email address.')
			->mustHaveDomain($this->mustHaveDomain)
			->name($this->name);
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new LinkTableCell('mailto:' . $v, $v);
	}
}

# The "url" element
class UrlInput extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);
		$this->required = isset($args['required']);
	}
	function makeFormPart() {
		return new InputFormPart($this, 'url', 'world');
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterFilterVar(FILTER_VALIDATE_URL, 'Invalid URL.')
			->name($this->name);
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new LinkTableCell($v, $v, true);
	}
}

# The "number" element
class NumberInp extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->min = isset($args['min']) ? intval($args['min']) : -INF;
		$this->max = isset($args['max']) ? intval($args['max']) : INF;
		$this->integer = isset($args['integer']);
	}
	function makeTableViewPart($v) {
		return new OrdinaryTableCell($v);
	}
	function makeFormPart() {
		return new NumberFormPart($this);
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterString()
			->maybeString()
			->requiredMaybe($this->required)
			->filterNumber($this->integer)
			->minMaxNumber($this->min, $this->max)
			->name($this->name);
	}
}

# The "date" element
class DatePicker extends NamedLabeledComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->min = isset($args['min']) ?
			DateTimeImmutable::createFromFormat('Y-m-d', $args['min'])->setTime(0, 0, 0) : null;
		$this->max = isset($args['max']) ?
			DateTimeImmutable::createFromFormat('Y-m-d', $args['max'])->setTime(0, 0, 0) : null;
	}
	function makeFormPart() {
		$sublabel = '';

		# Create a message giving the minimum and maximum, since input masks don't give us any way
		# of preventing the user from entering values outside a specific range.

		# In the future, it might be better to use a "proper" (calendar-style) datepicker.
		# It might also be good to use HTML5 date/time input types, but this might create issues
		# with cross-browser compatibility if input masks are used at the same time.

		if (isset($this->max) && isset($this->min)) {
			$sublabel = 'Please provide a date between ' . dfd($this->min) . ' and ' . dfd($this->max) . '.';
		} else {
			if (isset($this->max)) {
				$sublabel = 'Please provide a date no later than ' . dfd($this->max) . '.';
			} else {
				if (isset($this->min)) {
					$sublabel = 'Please provide a date no earlier than ' . dfd($this->min) . '.';
				}
			}
		}

		return new InputFormPart($this, 'text', 'calendar', " 'alias': 'mm/dd/yyyy' ", $sublabel);
	}
	function getSubmissionPart($against) {
		return $against
			->post($this->name)
			->filterDate()
			->requiredMaybe($this->required)
			->minMaxDate($this->min, $this->max)
			->name($this->name);
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new OrdinaryTableCell($v->format('n/j/Y'));
	}
}

# The "Header" element
class Header implements FormPartFactory, Configurable {
	function __construct($args) {
		$this->text = $args['innerText'];
		$this->subhead = isset($args['subhead']) ? $args['subhead'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->size = isset($args['size']) ? intval($args['size']) : null;
	}
	function makeFormPart() {
		return new HeaderFormPart($this);
	}
	function makeGroupPart() {
		return new GroupHeaderFormPart($this);
	}
}

# The "notice" element
class Notice implements FormPartFactory, Configurable {
	function __construct($args) {
		$this->text = isset($args['text']) ? $args['text'] : '';
		$this->header = isset($args['header']) ? $args['header'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->list = isset($args['children']) ? $args['children'] : null;
		if (isset($args['children']) && count($args['children']) === 0) {
			$this->list = null;
		}
		$this->ntype = isset($args['type']) ? $args['type'] : null;
	}
	function makeFormPart() {
		return new NoticeFormPart($this);
	}
	function makeGroupPart() {
		return new GroupNoticeFormPart($this);
	}
}

# The "list" element.
# The implementation is, unfortunately, quite complex.
class ListComponent implements FormPartFactory, Configurable,
	TableViewPartFactory, DetailsViewPartFactory, EmailViewPartFactory, Storeable {
	use Tableize, Groupize, Fieldize;
	function __construct($args) {
		$this->items = $args['children'];
		$this->name = $args['name'];
		$this->label = $args['label'];
		$this->maxItems = isset($args['max-items']) ? intval($args['max-items']) : INF;
		$this->minItems = isset($args['min-items']) ? intval($args['min-items']) : 0;
		$this->addText = isset($args['add-text']) ? $args['add-text'] : 'Add an item';
	}

	# This is the same as the getAllFields element of GroupComponent.
	private function getAllFieldsWithin() {
		$arr = [];
		foreach ($this->items as $item) {
			if ($item instanceof Storeable) {
				$arr = array_merge($arr, $item->getAllFields());
			}
		}
		return $arr;
	}

	# Generates a UI for adding/removing items
	function makeFormPart() {
		return new ListComponentFormPart($this);
	}
	function getSubmissionPart($val) {
		# Get the relevant parts of $_POST/$_FILES,
		# converting $_FILES with diverse_array if need be.
		return $val
			->ifOk(function ($v) {
				return Result::ok(
					[
						isset($v->post[ $this->name ]) ? $v->post[ $this->name ] : null,
						isset($v->files[ $this->name ]) ? $v->files[ $this->name ] : null
					]
				);
			})
			->ifOk(function ($data) {
				return Result::ok([
					is_array($data[0]) ? $data[0] : [],
					is_array($data[1]) ? diverse_array($data[1]) : []
				]);
			})
			->listValidate($this->minItems, $this->maxItems, $this->name, $this->items);
	}

	# Only in details/email view do we actually show the individual list items.
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		if (count($v) === 1) {
			$showValue = '(1 item)';
		} else {
			$showValue = '(' . count($v) . ' items)';
		}
		return new OrdinaryTableCell($showValue);
	}
	function makeDetailsViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new ListDetailedTableCell($v, $this->getAllFieldsWithin());
	}
	function makeEmailViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new ListEmailTableCell($v, $this->getAllFieldsWithin());
	}
}

# The "group" element
class Group extends GroupComponent {
	function __construct($args) {
		$this->items = $args['children'];
	}
	function makeFormPart() {
		return new GroupFormPart($this);
	}
}

# Keeps track of the IP address associated with a form submission.
# This is never displayed as part of the form, so it doesn't
# implement FormPartFactory.
class IPField implements TableViewPartFactory, Storeable {
	use Fieldize;
	function __construct() {
		$this->name = '_ip';
		$this->label = 'IP Address';
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new OrdinaryTableCell($v);
	}
	function getSubmissionPart($val) {
		return Result::ok(['_ip' => $_SERVER['REMOTE_ADDR']]);
	}
}

# Keeps track of the timestamp associated with a form submission.
# This is never displayed as part of the form, so it doesn't
# implement FormPartFactory.
class TimestampField implements TableViewPartFactory, Storeable {
	use Fieldize;
	function __construct() {
		$this->name = '_timestamp';
		$this->label = 'Timestamp';
	}
	function makeTableViewPart($v) {
		if ($v === null) {
			return null;
		}
		return new OrdinaryTableCell($v->format('n/j/Y g:i A'));
	}
	function getSubmissionPart($val) {
		return Result::ok(['_timestamp' => new DateTimeImmutable()]);
	}
}

# The "fields" element
class FieldList extends GroupComponent {
	function __construct($args) {
		$this->items = $args['children'];
		$this->items[] = new TimestampField();
		$this->items[] = new IPField();
	}
	function makeFormPart() {
		return new FormElemFormPart($this);
	}
}

# The main "form" element, which is the root eleemnt of any
# configuration file.
class Page implements Configurable {

	function __construct($args) {
		$this->form = $args['byTag']['fields'];

		$this->title = isset($args['title']) ? $args['title'] : 'Form';
		$this->successMessage = isset($args['success-message']) ? $args['success-message'] :
			'The form was submitted successfully.';
		$this->outputs = $args['byTag']['outputs'];
		$this->views = $args['byTag']['views'];
	}

	function makeFormPart() {
		return new PageFormPart($this);
	}

	# Obtain a view and tell it what page it's on
	function getView($name) {
		$view = $this->views->getByName($name);
		$view->setPage($this);
		return $view;
	}

	# Set the form's ID based on the URL
	function setId($id) {
		$this->id = $id;
		$this->form->id = $id;
	}

	# Get the MongoOutput associated with the form
	function getMongo() {
		return $this->outputs->getStorage();
	}
}

