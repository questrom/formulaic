<?php

use Gregwar\Captcha\CaptchaBuilder;
use Sabre\Xml\XmlDeserializable as XmlDeserializable;


// Full components
// ===============

class ShowIfComponent implements FormPartFactory, Validatable, NameMatcher, XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->item = $args['children'][1];
		$this->condition = $args['children'][0];
	}

	function getAllFields() {
		if($this->item instanceof NameMatcher) {
			return $this->item->getAllFields();
		} else {
			return [];
		}
	}

	function getByName($name) {
		if($this->item instanceof NameMatcher) {
			return $this->item->getByName($name);
		} else {
			return null;
		}
	}

	function makeFormPart() {
		return new ShowIfComponentFormPart($this);
	}
	function getMerger($val) {
		return $val
			->collapse()
			->innerBind(function($val) {
				if(!($this->condition->evaluate($val))) {
					return Result::ok([]);
				} else {
					return Result::ok($val)->groupValidate([$this->item]);
				}
			});
	}
}

class CheckboxTableCell implements Renderable {
	function __construct($value) {
		$this->value = $value;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return $this->h
		->td->class($this->value ? 'positive' : 'negative')
			->t($this->value ? 'Yes' : 'No')
		->end;
	}

}

class Checkbox extends PostInputComponent implements Enumerative, TableCellFactory {
	function __construct($args) {
		parent::__construct($args);
		$this->mustCheck = isset($args['must-check']);
	}
	function makeFormPart() { return new CheckboxFormPart($this); }
	protected function validate($against) {
		return $against
			->filterBoolean()
			->mustBeTrue($this->mustCheck);
	}
	function getPossibleValues() {
		return [true, false];
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		return new CheckboxTableCell($v);
	}
}

class TimeInput extends PostInputComponent implements TableCellFactory {
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
	protected function validate($against) {
		return $against
			->filterTime()
			->requiredMaybe($this->required)
			->minMaxTime($this->min, $this->max)
			->stepTime($this->step);
	}
	function makeTableCellPart($v) {
			if($v === null) { return null; }
			$hour = floor($v / 3600);
			$minute = ($v % 3600) / 60;
			$xm = 'am';
			if($hour > 11) {
				$xm = 'pm';
				$hour -= 12;
			}
			if(intval($hour) === 0) {
				$hour = 12;
			}

			return new OrdinaryTableCell(
				sprintf('%d:%02d %s', $hour, $minute, $xm)
			);


	}
}


class DateTimePicker extends PostInputComponent implements TableCellFactory {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['max']) : null;
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['min']) : null;

		$this->step = isset($args['step']) ? $args['step'] : 'any';
	}
	function makeFormPart() {
		return new DateTimePickerFormPart($this);
	}
	protected function validate($against) {
		return $against
			->filterDateTime()
			->requiredMaybe($this->required)
			->minMaxDateTime($this->min, $this->max)
			->stepDateTime($this->step);
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		return new OrdinaryTableCell($v->format('n/j/Y g:i A'));
	}
}

class TextareaTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		return $this->h
			->td
				->pre
					->t($this->value)
				->end
			->end;
	}
}

class Textarea extends PostInputComponent implements TableCellFactory {
	function __construct($args) {
		parent::__construct($args);

		$this->maxLength = isset($args['max-length']) ? intval($args['max-length']) : INF;
		$this->minLength = isset($args['min-length']) ? intval($args['min-length']) : 0;
		$this->required  = isset($args['required']);
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;

	}
	function makeFormPart() {
		return new TextareaFormPart($this);
	}
	protected function validate($against) {
		return $against
			->filterString()
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		return new TextareaTableCell($v);
	}
}



class Dropdown extends PostInputComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->options = $args['children'];
		$this->required = isset($args['required']);
	}
	function makeFormPart() {
		return new DropdownFormPart($this);
	}

	protected function validate($against) {
		return $against
			->filterChosenFromOptions($this->options)
			->requiredMaybe($this->required);
	}
}


class Radios extends PostInputComponent implements Enumerative {
	function __construct($args) {
		parent::__construct($args);

		$this->options = $args['children'];
		$this->required = isset($args['required']);
	}
	function makeFormPart() {
		return new RadiosFormPart($this);
	}
	function getPossibleValues() {
		return $this->options;
	}
	protected function validate($against) {
		return $against
			->filterChosenFromOptions($this->options)
			->requiredMaybe($this->required);
	}
}

class ListTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		return $this->h
			->td
				->ul->class('ui list')
					->addH(array_map(function($x) {
						return $this->h->li->t($x)->end;
					}, $this->value))
				->end
			->end;
	}
}


class Checkboxes extends PostInputComponent implements Enumerative {
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
	protected function validate($against) {

		return $against
			->filterManyChosenFromOptions($this->options)
			->minMaxChoices($this->minChoices, $this->maxChoices)
			->filterNoChoices()
			->requiredMaybe($this->required);
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		if(count($v) === 0) { return null;}
		return new ListTableCell($v);
	}
}


function makeCaptcha() {

	$builder = new CaptchaBuilder;
	$builder->build(290, 80);
	if(!isset($_SESSION['phrases'])) {
		$_SESSION['phrases'] = [];
	}
	$id = mt_rand();
	$_SESSION['phrases'][$id] = $builder->getPhrase();

	return [
		'data' => $builder->inline(),
		'id' => $id
	];
}


class Captcha extends PostInputComponent {
	function __construct($args) {
		$this->name = '_captcha';
		$this->label = 'CAPTCHA';
	}
	function makeFormPart() {
		return new CaptchaFormPart($this);
	}
	protected function validate($against) {
		return $against
			->innerBind(function($x) {
				$code = $x[0];
				$id = intval($x[1]);
				if(!isset($_SESSION['phrases'][$id])) {
					return Result::error('Invalid data');
				}

				$isCorrect = (new CaptchaBuilder($_SESSION['phrases'][$id]))->testPhrase($code);
				unset($_SESSION['phrases'][$id]); // So user can't just reuse one CAPTCHA/id pair

				if($isCorrect) {
					return Result::error('Incorrect phrase.');
				}
				return Result::ok(null);
			});
	}
}


class Textbox extends PostInputComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->maxLength = isset($args['max-length']) ? intval($args['max-length']) : INF;
		$this->minLength = isset($args['min-length']) ? intval($args['min-length']) : 0;
		$this->required  = isset($args['required']);
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;

	}
	function makeFormPart() {
		return new InputFormPart($this, 'text', null);
	}
	protected function validate($against) {
		return $against
			->filterString()
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
}

class FileUploadTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		return $this->h
			->td->class('unpadded-cell')
				->a->href($this->value['url'])->class('ui attached labeled icon button')
					->i->class('download icon')->end
					->t('Download')
				->end
			->end;
	}
}

class FileUploadDetailedTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		$v = $this->value;
		return $this->h
		->td
			->div->class('ui list')
				->div->class('item') ->strong->t('URL: ')->end->a->href($v['url'])->t($v['url'])->end							->end
				->div->class('item') ->strong->t('Original Filename: ')->end->t($v['originalName'])	->end
				->div->class('item') ->strong->t('Type: ')->end->t($v['mime'])						->end
			->end
		->end;
	}
}


class FileUpload extends FileInputComponent implements TableCellFactory {
	function __construct($args) {
		parent::__construct($args);
		$this->required  = isset($args['required']);
		$this->allowedExtensions = array_reduce(
			array_map(function($x) {
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
	protected function validate($against) {
		return $against
			->innerBind(function($val) {
				// See http://php.net/manual/en/features.file-upload.php
				if(!is_array($val) || !isset($val['error']) || is_array($val['error'])) {
					return Result::error('Invalid data.');
				} else if($val['error'] === UPLOAD_ERR_INI_SIZE || $val['error'] === UPLOAD_ERR_FORM_SIZE) {
					return Result::error('File size exceeds server or form limit.');
				} else if($val['error'] === UPLOAD_ERR_NO_FILE) {
					return Result::none(null);
				} else if($val['error'] === UPLOAD_ERR_OK) {
					return Result::ok($val);
				} else {
					return Result::error('Error uploading file.');
				}
			})
			->requiredMaybe($this->required)
			->innerBind(function($file) {
				if($file['size'] > $this->maxSize) {
					return Result::error('File must be under ' . $this->maxSize . ' bytes in size.');
				} else {
					return Result::ok($file);
				}
			})
			->innerBind(function($file) {

				$finfo = new finfo(FILEINFO_MIME_TYPE);
				$mime = $finfo->file($file['tmp_name']);

				$ext = array_search(
					$mime,
					$this->allowedExtensions,
					true
				);

				if($ext === false) {
					return Result::error('Invalid file type or wrong MIME type. Allowed extensions are: ' .
						implode(', ', array_keys($this->allowedExtensions)) . '.');
				}

				if(!is_uploaded_file($file['tmp_name'])) {
					return Result::error('Security error.');
				}


				$filename = sha1_file($file['tmp_name']) . '-' . floor(microtime(true)) . '.' . $ext;

				return Result::ok(new FileInfo($file, $filename, $mime, $this->permissions));
			});
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		if(is_string($v) && !isset($v['url'])) { return null; }
		return new FileUploadTableCell($v);
	}
	function makeDetailedTableCell($v) {

			if($v === null) { return null; }

			if(is_string($v) || !isset($v['url'])) {
				// From old version
				return null;
			}
			return new FileUploadDetailedTableCell($v);

	}
	function makeEmailTableCell($v) {

			if($v === null) { return null; }

			if(is_string($v) || !isset($v['url'])) {
				// From old version
				return null;
			}
			return new FileUploadDetailedTableCell($v);
	}
}

class Range extends PostInputComponent {
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
	protected function validate($against) {
		return $against
			->filterString()
			->filterNumber(false)
			->minMaxNumber($this->min, $this->max)
			->stepNumber($this->step);
	}
}

class PasswordTableCell implements Renderable {
	function __construct() {
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return $this->h
			->td
				->abbr->title('Passwords are not saved in the database')
					->t('N/A')
				->end
			->end;
	}
}


class Password extends PostInputComponent  {

	function __construct($args) {
		parent::__construct($args);

		$this->required  = isset($args['required']);
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;

		if(isset($args['match-hash'])) {
			$this->required = true;
		}
	}
	function makeFormPart() {
		return new InputFormPart($this, 'password', null);
	}
	protected function validate($against) {
		return $against
			->filterString()
			->matchHash( isset($this->matchHash) ? $this->matchHash : null )
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
	function getMerger($val) {
		return parent::getMerger($val)
			->innerBind(function($x) {
				// Avoid storing passwords.
				return Result::ok([]);
			});
	}
	function makeTableCellPart($v) {
		return new PasswordTableCell();

	}
}

class LinkTableCell implements Renderable {
	function __construct($url, $value, $blank = false) {
		$this->h = new HTMLParentlessContext();
		$this->url = $url;
		$this->value = $value;
		$this->blank = $blank;
	}
	function render() {
		return $this->h
			->td
				->a->href($this->url)->target('_blank', $this->blank)
					->t($this->value)
				->end
			->end;
	}
}

class PhoneNumber extends PostInputComponent implements TableCellFactory {

	function __construct($args) {
		parent::__construct($args);
		$this->required = isset($args['required']);
	}
	function makeFormPart() {
		return new InputFormPart($this, 'tel', 'call');
	}
	protected function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterPhone();
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		if(preg_match('/^[0-9]{10}$/', $v)) {
			$showValue = '(' . substr($v, 0, 3) . ')' . json_decode('"\u2006"') . substr($v, 3, 3) . json_decode('"\u2006"') . substr($v, 6, 4);
		} else {
			$showValue = $v;
		}
		return new LinkTableCell('tel:' . $v, $showValue);
	}
}

class EmailAddr extends PostInputComponent implements TableCellFactory {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->mustHaveDomain = isset($args['must-have-domain']) ? $args['must-have-domain'] : null;
	}
	function makeFormPart() {
		$innerText = isset($this->mustHaveDomain) ? ('Must be @' . $this->mustHaveDomain) : null;
		return new InputFormPart($this, 'email', 'mail',  null, $innerText);
	}
	protected function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterFilterVar(FILTER_VALIDATE_EMAIL, 'Invalid email address.')
			->mustHaveDomain($this->mustHaveDomain);
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		return new LinkTableCell('mailto:' . $v, $v);
	}
}

class UrlInput extends PostInputComponent implements TableCellFactory {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
	}
	function makeFormPart() {
		return new InputFormPart($this, 'url', 'world');
	}
	protected function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterFilterVar(FILTER_VALIDATE_URL, 'Invalid URL.');
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		return new LinkTableCell($v, $v, true);
	}
}
class NumberInp extends PostInputComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->min = isset($args['min']) ? intval($args['min']) : -INF;
		$this->max = isset($args['max']) ? intval($args['max']) : INF;
		$this->integer = isset($args['integer']);
	}
	function makeFormPart() {
		return new NumberFormPart($this);
	}
	protected function validate($against) {
		return $against
			->filterString()
			->maybeString() // So we end up with a Maybe<> if not required
			->requiredMaybe($this->required)
			->filterNumber($this->integer)
			->minMaxNumber($this->min, $this->max);
	}
}

function dfd($date) {
	return $date->format('m/d/Y');
}

class DatePicker extends PostInputComponent implements TableCellFactory {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->min = isset($args['min']) ?
			DateTimeImmutable::createFromFormat('Y-m-d', $args['min'])->setTime(0,0,0) : null;
		$this->max = isset($args['max']) ?
			DateTimeImmutable::createFromFormat('Y-m-d', $args['max'])->setTime(0,0,0) : null;
	}
	function makeFormPart() {
		$sublabel = '';

		if(isset($this->max) && isset($this->min)) {
			$sublabel = 'Please provide a date between ' . dfd($this->min) . ' and ' . dfd($this->max) . '.';
		} else if (isset($this->max)) {
			$sublabel = 'Please provide a date no later than ' . dfd($this->max) . '.';
		} else if(isset($this->min)) {
			$sublabel = 'Please provide a date no earlier than ' . dfd($this->min) . '.';
		}

		return new InputFormPart($this, 'text', 'calendar', " 'alias': 'mm/dd/yyyy' ", $sublabel);
	}
	protected function validate($against) {
		return $against
			->filterDate()
			->requiredMaybe($this->required)
			->minMaxDate($this->min, $this->max);
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		// return $value->innerBind(function($v) use($h) {
			return new OrdinaryTableCell($v->format('n/j/Y'));

		// });
	}
}



class Header extends BaseHeader {
	function makeFormPart() {
		return new HeaderFormPart($this);
	}
}

class GroupHeader extends BaseHeader {
	function makeFormPart() {
		return new GroupHeaderFormPart($this);
	}
}

class GroupNotice extends BaseNotice {
	function makeFormPart() {
		return new GroupNoticeFormPart($this);
	}
}

class Notice extends BaseNotice {
	function makeFormPart() {
		return new NoticeFormPart($this);
	}
}

class ListComponent implements FormPartFactory, Validatable, NameMatcher,
	XmlDeserializable, TableCellFactory {
	use Configurable;
	function __construct($args) {
		$this->items = $args['children'];
		$this->name = $args['name'];
		$this->label = $args['label'];

		$this->maxItems = isset($args['max-items']) ? intval($args['max-items']) : INF;
		$this->minItems = isset($args['min-items']) ? intval($args['min-items']) : 0;

		$this->addText = isset($args['add-text']) ? $args['add-text'] : 'Add an item';
	}

	function getByName($name) {
		return ($this->name === $name) ? $this : null;
	}
	function getAllFields() {
		return [ $this ];
	}

	// Borrowed from GroupComponent
	private function getAllFieldsWithin() {
		$arr = [];
		foreach($this->items as $item) {
			if($item instanceof NameMatcher) {
				$arr = array_merge($arr, $item->getAllFields());
			}
		}
		return $arr;
	}
	function makeFormPart() {
		return new ListComponentFormPart($this);
	}
	function getMerger($val) {

		return $val
		->innerBind(function($v) {

			return Result::ok(
				[
					isset($v->post[$this->name]) ? $v->post[$this->name] : null,
					isset($v->files[$this->name]) ? $v->files[$this->name] : null
				]
			);
		})
		->innerBind(function($data) {


			return Result::ok([
				is_array($data[0]) ? $data[0] : [],
				is_array($data[1]) ? diverse_array($data[1] ) : []
			]);
		})
		->innerBind(function($list) {



			$result = Result::ok([]);
			$number = array_merge( array_keys($list[0]), array_keys($list[1]) );
			$number = (count($number) > 0 ? max( $number ) : -1) + 1;

			if($number < $this->minItems) {
				return Result::error([ $this->name => 'Please provide at least ' . $this->minItems . ' items' ]);
			}
			if($number > $this->maxItems) {
				return Result::error([ $this->name => 'Please provide at most ' . $this->maxItems . ' items' ]);
			}


			for($index = 0; $index < $number; $index++) {



				if(!isset($list[0][$index]) && !isset($list[1][$index])) {
					continue;
				}

				$validationResult =
					Result::ok(
						new ClientData(
							isget($list[0][$index], []),
							isget($list[1][$index], [])
						)
					)->groupValidate($this->items);




				$result = $result
					->innerBind(function($soFar) use($validationResult, $index) {
						return $validationResult
							->innerBind(function($fieldResult) use($soFar, $index) {
								$soFar[$index] = $fieldResult;
								return Result::ok($soFar);
							})
							->ifError(function($fieldError) {
								return Result::error([]);
							});
					})
					->ifError(function($errorSoFar) use($validationResult, $index) {
						return $validationResult
							->ifError(function($fieldError) use($errorSoFar, $index) {
								foreach($fieldError as $k => $v) {

									$k = explode('[', $k);
									$kStart = $k[0];
									$kRest = (count($k) > 1) ?
										'[' . implode('[', array_slice($k, 1)) :
										'';

									$errorSoFar[ $this->name . '[' . $index . '][' . $kStart . ']' . $kRest  ] = $v;
								}

								return Result::error($errorSoFar);
							})
							->innerBind(function($fieldResult) use($errorSoFar) {
								return Result::error($errorSoFar);
							});

					});
			}
			$result = $result
				->innerBind(function($x) {
					return Result::ok([$this->name => array_values($x)]);
				});
			return $result;
		});
	}
	use Tableize;
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		if(count($v) === 1) {
			$showValue = '(1 item)';
		} else {
			$showValue = '(' . count($v) . ' items)';
		}

		return new OrdinaryTableCell($showValue);

	}
	function makeDetailedTableCell($v) {


			if($v === null) { return null; }

				return new ListDetailedTableCell($v, $this->getAllFieldsWithin());


	}
	function makeEmailTableCell($v) {

				if($v === null) { return null; }

				return new ListEmailTableCell($v, $this->getAllFieldsWithin());

	}
}

class ListEmailTableCell implements Renderable {
	function __construct($value, $fields) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
		$this->fields = $fields;
	}
	function render() {
		return $this->h
		->td
			->addH(array_map(function($listitem) {
				return $this->h->table->border(1)
					->addH(array_map(function($field) use ($listitem) {
						if($field instanceof TableCellFactory) {
							return (new EmailValueRow( isget($listitem[$field->name]), $field ));
						} else {
							return null;
						}
					}, $this->fields))
				->end;
			}, $this->value))
		->end;
	}
}

class ListDetailedTableCell implements Renderable {
	function __construct($v, $value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
		$this->v = $v;
	}
	function render() {

		return $this->h
		->td
			->addH(array_map(function($listitem) {
				return new ValueTable($this->value, $listitem, false);
			}, $this->v))
		->end;
	}
}

class Group extends GroupComponent {
	function __construct($args) {
		$this->items = $args['children'];
	}
	function makeFormPart() {
		return new GroupFormPart($this);
	}
}

class IPField implements Validatable, TableCellFactory, NameMatcher {
	function __construct() {
		$this->name = '_ip';
		$this->label = 'IP Address';
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		return new OrdinaryTableCell($v);
	}
	function getByName($name) {
		return ($this->name === $name) ? $this : null;
	}
	function getMerger($val) {
		return Result::ok(['_ip' => $_SERVER['REMOTE_ADDR']]);
	}
	function getAllFields() {
		return [ $this ];
	}
	use Tableize;
}

class TimestampField implements Validatable, TableCellFactory, NameMatcher {
	function __construct() {
		$this->name = '_timestamp';
		$this->label = 'Timestamp';
	}
	function makeTableCellPart($v) {
		if($v === null) { return null; }
		return new OrdinaryTableCell($v->format('n/j/Y g:i A'));
	}
	function getByName($name) {
		return ($this->name === $name) ? $this : null;
	}
	function getMerger($val) {
		return Result::ok(['_timestamp' => new DateTimeImmutable()]);
	}
	function getAllFields() {
		return [ $this ];
	}
		use Tableize;
}


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

class Page implements XmlDeserializable {
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$attrs = $reader->parseAttributes();
		$attrs['byTag'] = Sabre\Xml\Element\KeyValue::xmlDeserialize($reader);

		return new static($attrs);
	}
	function __construct($args) {
		$this->form = $args['byTag']['{}fields'];

		$this->title = isset($args['title']) ? $args['title'] : 'Form';
		$this->successMessage = isset($args['success-message']) ? $args['success-message'] :
			'The form was submitted successfully.';
		$this->outputs = $args['byTag']['{}outputs'];
		$this->views = $args['byTag']['{}views'];
	}
	function makeFormPart() {
		return new PageFormPart($this);
	}
	function getView($name) {
		$view = $this->views->getByName($name);
		$view->setPage($this);
		return $view;
	}
	function setId($id) {
		$this->id = $id;
		$this->form->id = $id;
	}
}

