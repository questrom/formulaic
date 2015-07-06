<?php




// Full components
// ===============


class ShowIfComponent extends GroupComponent {
	function __construct($args) {
		$this->items = [
			$args['children'][0]
		];
		$this->cond = $args['cond'];
	}
	function get($h) {
		return $h
			->div->data('show-if', $this->cond)
				->add($this->items[0])
			->end;
	}
	function getMerger($val) {
		return $val
			->collapse()
			->innerBind(function($val) {
				$post_value = $val->post;
				if(
					!(isset($post_value[$this->cond]) ? $post_value[$this->cond] === "on" : false)
				) {
					return Result::ok([]);
				} else {
					return parent::getMerger( Result::ok( $val  ) );
				}
			});
	}
}

class Label implements HTMLComponent {
	function __construct($label) {
		$this->label = $label;
	}
	function get($h) {
		return $h->label->t($this->label)->end;
	}
}

class Checkbox extends PostInputComponent implements Enumerative {
	function __construct($args) {
		parent::__construct($args);
		$this->mustCheck = isset($args['must-check']);
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->mustCheck ? 'required' : ''))
			->div->class('ui checkbox')
				->input->type('checkbox')->name($this->name)->end
				->add($this->getLabel())
			->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterBoolean()
			->mustBeTrue($this->mustCheck);
	}
	function getPossibleValues() {
		return [true, false];
	}
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
			return Result::ok($h
			->td->class($v ? 'positive' : 'negative')
				->t($v ? 'Yes' : 'No')
			->end);
		});
	}
}

class TimeInput extends PostInputComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->max = isset($args['max']) ? $args['max'] : null;
		$this->min = isset($args['min']) ? $args['min'] : null;

		$this->step = isset($args['step']) ? intval($args['step']) : 'any';
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))
			->add($this->getLabel())
			->div->class('ui left icon input')
				->i->class('clock icon')->end
				->input->type('text')->name($this->name)->data('inputmask', " 'alias': 'h:s t', 'placeholder': 'hh:mm am' ")->end
			->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterTime()
			->requiredMaybe($this->required)
			->minMaxTime($this->min, $this->max)
			->stepTime($this->step);
	}
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
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
			return parent::asTableCell(
				$h,
				Result::ok(
					sprintf("%d:%02d %s",$hour,$minute,$xm)
				)
			);
		});
	}
}

class DateTimePicker extends PostInputComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['max']) : null;
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['min']) : null;

		$this->step = isset($args['step']) ? $args['step'] : 'any';
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))
			->add($this->getLabel())
			->div->class('ui left icon input')
				->i->class('calendar icon')->end
				->input->type('text')->name($this->name)->data('inputmask', " 'alias': 'proper-datetime' ")->end
			->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterDateTime()
			->requiredMaybe($this->required)
			->minMaxDateTime($this->min, $this->max)
			->stepDateTime($this->step);
	}
	function asTableCell($h, $value) {
		return parent::asTableCell(
			$h,
			$value->innerBind(function($v) {
				return Result::ok($v->format('n/j/Y g:i A'));
			})
		);
	}
}


class Textarea extends SpecialInput {
	function get($h) {
		return $h
		->ins(fieldBox($h, $this->required))
			->add($this->getLabel())
			->textarea->name($this->name)->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterString()
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
			return Result::ok($h
			->td
				->pre
					->t($v)
				->end
			->end);
		});
	}
}



class Dropdown extends PostInputComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->options = $args['children'];
		$this->required = isset($args['required']);
	}
	function get($h) {
		return fieldBox($h, $this->required)
			->add($this->getLabel())
			->div->class('ui fluid dropdown selection')
				->input->name($this->name)->type('hidden')->value('')->end
				->div->class('default text')->t('Please choose an option...')->end
				->i->class('dropdown icon')->end
				->div->class('menu')
					->add(array_map(
						function($v) use($h) {
							return $h
							->div
								->class('item')->data('value', $v)->t($v)
							->end;
						},
						$this->options
					))
				->end
			->end
		->end;
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
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))
			->add($this->getLabel())
			->add(
				array_map(
					function($v) use($h) {
						return $h
						->div->class('field not-validation-root')
							->div->class('ui radio checkbox')
								->input->name($this->name)->type('radio')->value($v)->end
								->label->t($v)->end
							->end
						->end;
					},
					$this->options
				)
			)
		->end;
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


class Checkboxes extends PostInputComponent implements Enumerative {
	function __construct($args) {
		parent::__construct($args);
		$this->options = $args['children'];

		$this->required = isset($args['required']);
		$this->minChoices = isset($args['min-choices']) ? intval($args['min-choices']) : 0;
		$this->maxChoices = isset($args['max-choices']) ? intval($args['max-choices']) : INF;
	}
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))->data('validation-name', $this->name)
			->add($this->getLabel())
			->add(
				array_map(
					function($v) use($h) {
						return $h->div->class('field not-validation-root')
							->div->class('ui checkbox')
								->input->name($this->name . '[]')->type('checkbox')->value($v)->end
								->label->t($v)->end
							->end
						->end;
					},
					$this->options
				)
			)
		->end;
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
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
			if(count($v) === 0) {
				return Result::none(null);
			}
			return Result::ok($h
			->td
				->ul->class('ui list')
					->add(array_map(function($x) use ($h) {
						return $h->li->t($x)->end;
					}, $v))
				->end
			->end);
		});
	}
}

class Textbox extends SpecialInput {
	use InputField;
	function get($h) {
        return $this->makeInput($h, 'text', null);
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


class FileUpload extends FileInputComponent {
	use InputField;
	function __construct($args) {
		parent::__construct($args);
		$this->required  = isset($args['required']);
		$this->allowedExtensions = array_reduce(
			array_map(function($x) {
				return [$x->ext => $x->mime];
			}, $args['children']),
		'array_merge', []);

		$this->maxSize = intval($args['max-size']);
		$this->permissions = $args['permissions'];

	}
	function get($h) {
		return $this->makeInput($h, 'file');
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
					return Result::error('Invalid file type or wrong MIME type. Allowed extensions are: ' . implode(', ', array_keys($this->allowedExtensions)) . '.');
				}

				if(!is_uploaded_file($file['tmp_name'])) {
					return Result::error('Security error.');
				}


				$filename = sha1_file($file['tmp_name']) . '-' . floor(microtime(true)) . '.' . $ext;

				return Result::ok(new FileInfo($file, $filename, $mime, $this->permissions));
			});
	}
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {

			if(is_string($v) || !isset($v['url'])) {
				// From old version
				return Result::none(null);
			}

			return Result::ok($h
			->td->class('unpadded-cell')
				->a->href($v['url'])->class('ui attached labeled icon button')
					->i->class('download icon')->end
					->t('Download')
				->end
			->end);
		});
	}
	function asDetailedTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {

			if(is_string($v) || !isset($v['url'])) {
				// From old version
				return Result::none(null);
			}

			return Result::ok($h
			->td
				->div->class('ui list')
					->div->class('item') ->strong->t('URL: ')->end->a->href($v['url'])->t($v['url'])->end							->end
					->div->class('item') ->strong->t('Original Filename: ')->end->t($v['originalName'])	->end
					->div->class('item') ->strong->t('Type: ')->end->t($v['mime'])						->end
				->end
			->end);

		});
	}
	function asEmailTableCell($h, $value) { return $this->asDetailedTableCell($h, $value); }
}

class Range extends PostInputComponent {
	function __construct($args) {

		parent::__construct($args);

		$this->max = isset($args['max']) ? intval($args['max']) : 1;
		$this->min = isset($args['min']) ? intval($args['min']) : 0;
		$this->step = isset($args['step']) ? $args['step'] : 'any';
		$this->def = isset($args['default']) ? intval($args['default']) : midpoint($this->min, $this->max);
	}
	function get($h) {
		return $h
		->div->class('ui field')
			->add($this->getLabel())
			->div
				->input
					->type('range')
					->name($this->name)
					->max($this->max)
					->min($this->min)
					->step($this->step)
					->value($this->def)
				->end
				->span->class('ui left pointing horizontal label range-value')
				->end
			->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterString()
			->filterNumber(false)
			->minMaxNumber($this->min, $this->max)
			->stepNumber($this->step);
	}
}


class Password extends SpecialInput {
	use InputField;
	function __construct($args) {
		parent::__construct($args);
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;
	}
	function get($h) {
		return $this->makeInput($h, 'password', '');
	}
	protected function validate($against) {
		return $against
			->filterString()
			->matchHash( isset($this->matchHash) ? $this->matchHash : null )
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
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
	function asTableCell($h, $value) {
		return Result::ok($h
		->td
			->abbr->title('Passwords are not saved in the database')
				->t('N/A')
			->end
		->end);

	}
}

class PhoneNumber extends PostInputComponent {
	use InputField;
	function __construct($args) {
		parent::__construct($args);
		$this->required = isset($args['required']);
	}
	function get($h) {
		return $this->makeInput($h, 'tel', 'call');
	}
	protected function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterPhone();
	}
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
			if(preg_match('/^[0-9]{10}$/', $v)) {
				$showValue = '(' . substr($v, 0, 3) . ')' . json_decode('"\u2006"') . substr($v, 3, 3) . json_decode('"\u2006"') . substr($v, 6, 4);
			} else {
				$showValue = $v;
			}
			return Result::ok($h
			->td
				->a->href('tel:' . $v)
					->t($showValue)
				->end
			->end);
		});
	}
}

class EmailAddr extends PostInputComponent {
	use InputField;
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->mustHaveDomain = isset($args['must-have-domain']) ? $args['must-have-domain'] : null;
	}
	function get($h) {
		return $this->makeInput($h, 'email', 'mail');
	}
	protected function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterFilterVar(FILTER_VALIDATE_EMAIL, 'Invalid email address.')
			->mustHaveDomain($this->mustHaveDomain);
	}
	 function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
			return Result::ok($h
			->td
				->a->href('mailto:' . $v)
					->t($v)
				->end
			->end);
		});
	}
}
class UrlInput extends PostInputComponent {
	use InputField;
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
	}
	function get($h) {
		return $this->makeInput($h, 'url', 'world');
	}
	protected function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterFilterVar(FILTER_VALIDATE_URL, 'Invalid URL.');
	}
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
			return Result::ok($h
			->td
				->a->href($v)->target('_blank')
					->t($v)
				->end
			->end);
		});
	}
}
class NumberInp extends PostInputComponent {
	use InputField;
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->min = isset($args['min']) ? intval($args['min']) : -INF;
		$this->max = isset($args['max']) ? intval($args['max']) : INF;
		$this->integer = isset($args['integer']);
	}
	function get($h) {
		return $this->makeInput($h, 'number', '');
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

class DatePicker extends PostInputComponent {
	use InputField;
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('Y-m-d', $args['min'])->setTime(0,0,0) : null;
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('Y-m-d', $args['max'])->setTime(0,0,0) : null;
	}
	function get($h) {
		return $this->makeInput($h, 'text', 'calendar', " 'alias': 'mm/dd/yyyy' ");
	}
	protected function validate($against) {
		return $against
			->filterDate()
			->requiredMaybe($this->required)
			->minMaxDate($this->min, $this->max);
	}
	function asTableCell($h, $value) {
		return parent::asTableCell(
			$h,
			$value->innerBind(function($v) {
				return Result::ok($v->format('n/j/Y'));
			})
		);
	}
}






class Header extends BaseHeader {
	function get($h) { //this->size
		$size = ($this->size === null) ? 1 : $this->size;
		return $h
		->{'h' . $size}->class('ui header')
			->add(parent::get($h))
		->end;
	}
}

class GroupHeader extends BaseHeader {
	function get($h) {
		$size = ($this->size === null) ? 5 : $this->size;
		return $h
		->{'h' . $size}->class('ui header attached')
			->add(parent::get($h))
		->end;
	}
}


class GroupNotice extends BaseNotice {
	function get($h) {
		return
		$h
		->div->class('ui message attached ' . ($this->icon === null ? '' : ' icon') . ($this->type ? ' ' . $this->type : ''))
			->add(parent::get($h))
		->end;
	}
}

class Notice extends BaseNotice {
	function get($h) {
		return
		$h
		->div->class('ui message floating ' . ($this->icon === null ? '' : ' icon') . ($this->type ? ' ' . $this->type : ''))
			->add(parent::get($h))
		->end;
	}
}

// see http://php.net/manual/en/reserved.variables.files.php
function diverse_array($vector) {
   $result = [];
   foreach($vector as $part => $val) {
   		foreach($val as $index => $ival) {
   			foreach($ival as $name => $info) {
   				$result[$index][$name][$part] = $info;
   			}
   		}
   }
   return $result;
}



class ListComponent extends GroupComponent implements FieldListItem, FieldTableItem {
	function __construct($args) {
		$this->items = $args['children'];
		$this->name = $args['name'];
		$this->label = $args['label'];
		$this->addText = isset($args['add-text']) ? $args['add-text'] : 'Add an item';
	}

	function getByName($name) {

		return ($this->name === $name) ? $this : null;
	}
	function getAllFields() {
		return [ $this ];
	}
	function get($h) {


		return $h
		->div->class('ui field not-validation-root list-component')->data('count','0')->data('group-name', $this->name)
			->h5->class('top attached ui message')->t($this->label)->end
			->div->data('validation-name', $this->name)->class('validation-root ui bottom attached segment list-items')
				->script->type('text/template')
					->div->class('ui vertical segment close-item')
							->div->class('content')
								->add($this->items)
							->end
							->button->type('button')->class('ui compact negative icon button delete-btn')->i->class('trash icon')->end->end
					->end
				->end
				->div->class('ui center aligned vertical segment')
					->button->type('button')->class('ui primary labeled icon button add-item')
						->i->class('plus icon')->end
						->t($this->addText)
					->end
				->end
			->end
		->end;
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
				diverse_array($data[1])
			]);
		})
		->innerBind(function($list) {

			$result = Result::ok([]);
			$number = max(count($list[0]), count($list[1]));

			for($index = 0; $index < $number; $index++) {


				$validationResult = parent::getMerger(
					Result::ok(
						new ClientData(
							isget($list[0][$index], []),
							isget($list[1][$index], [])
						)
					)
				);

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
									$errorSoFar[ $this->name . '[' . $index . '][' . $k . ']'  ] = $v;
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
  function asTableCell($h, $value) {

		return $value->innerBind(function($v) use ($h) {


			if(count($v) === 1) {
				$showValue = '(1 item)';
			} else {
				$showValue = '(' . count($v) . ' items' . ')';
			}

			return Result::ok($h
			->td
				->t($showValue)
			->end);
		});
	}
	function asDetailedTableCell($h, $value) {

		return $value->innerBind(function($v) use ($h) {

				return Result::ok($h
					->td
						->add(array_map(function($listitem) use($h) {
							return $h->table->class('ui definition table')
								->add(array_map(function($field) use ($listitem) {
									if($field instanceof FieldListItem) {
										return new ValueRow( isget($listitem[$field->name]), $field );
									} else {
										return null;
									}
								}, parent::getAllFields() ))
							->end;
						}, $v))
					->end
				);

		});
	}
	function asEmailTableCell($h, $value) {

		return $value->innerBind(function($v) use ($h) {

				return Result::ok($h
					->td
						->add(array_map(function($listitem) use($h) {
							return $h->table->border(1)
								->add(array_map(function($field) use ($listitem) {
									if($field instanceof FieldListItem) {
										return new ValueRow( isget($listitem[$field->name]), $field );
									} else {
										return null;
									}
								}, parent::getAllFields() ))
							->end;
						}, $v))
					->end
				);

		});
	}
}

class Group extends GroupComponent {

	function __construct($args) {

		$this->items = $args['children'];
	}
	function get($h) {

		$items = array_map(function($item) {
			if($item instanceof Header) {
				return new GroupHeader($item->__args);
			} else if($item instanceof Notice) {
				return new GroupNotice($item->__args);
			} else {
				return $item;
			}
		}, $this->items);

		return $h
		->div->class('group')
			->add(array_map(function($value) use ($h) {
					if(is_array($value)) {
						return $h->div->class('ui segment attached')
							->add($value)
						->end;
					} else {
						return $value;
					}
				}, array_reduce($items, function($carry, $item) {
					if($item instanceof GroupHeader || $item instanceof GroupNotice) {
						$carry[] = $item;
						return $carry;
					} else if( is_array(end($carry)) ) {
						$carry[count($carry)-1][] = $item;
						return $carry;
					} else {
						$carry[] = [$item];
						return $carry;
					}
				}, [])))
		->end;
	}
}


class FormElem extends GroupComponent {


	function __construct($args) {
		$this->items = $args['children'];
	}
	function get($h) {
		return $h
		->form->class('ui form')->action('submit.php')->method('POST')
			->add($this->items)
			->div->class('ui floating error message validation-error-message')
				->div->class('header')
					->t('Error validating data')
				->end
				->p
					->t('Unfortunately, the data you provided contains errors. Please see above for more information. ')
					->t('After you have corrected the errors, press the button below to try again.')
				->end
			->end
			->button->type('button')->class('ui labeled icon positive big button centered-button')->data('submit','true')
				->i->class('checkmark icon')->end
				->span->t('Submit Form')->end
			->end
		->end;
	}

}



class IPField implements FieldTableItem, Validatable {
	function __construct() {
		$this->name = '_ip';
		$this->label = 'IP Address';
	}
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use($h) {
			return Result::ok($h
				->td
					->t($v)
				->end
			);
		});
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

}

class TimestampField implements FieldTableItem, Validatable {
	function __construct() {
		$this->name = '_timestamp';
		$this->label = 'Timestamp';
	}
	function asTableCell($h, $value) {
		return $value->innerBind(function($v) use($h) {
			return Result::ok($h
				->td
					->t($v->format('n/j/Y g:i A'))
				->end
			);
		});
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
}

class Page extends GroupComponent {
	function __construct($args) {

		$this->form = $args['byTag']['{}fields'];

		$this->items = [
			$this->form,
			new TimestampField(),
			new IPField()
		];

		$this->title = isset($args['title']) ? $args['title'] : 'Form';
		$this->successMessage = isset($args['success-message']) ? $args['success-message'] : 'The form was submitted successfully.';
		$this->debug = isset($args['debug']);
		$this->outputs = $args['byTag']['{}outputs'];
		$this->views = $args['byTag']['{}views'];
	}
	function get($h) {
		return $h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
				->link->rel('stylesheet')->href('semantic-ui/semantic.css')->end
				->link->rel('stylesheet')->href('styles.css')->end
			->end
			->body
				->div->class('ui text container')
					->div->class('ui segment')
						->add($this->form)
					->end
				->end
				->div->class('success-modal ui small modal')
					->div->class('header')
						->t('Submission complete')
					->end
					->div->class('content')
						->p->t($this->successMessage)->end
					->end
					->div->class('actions')
						->button->type('button')->class('ui primary button approve')->t('OK')->end
					->end
				->end
				->div->class('failure-modal ui small modal')
					->div->class('red ui header')
						->t('Submission failed')
					->end
					->div->class('content')
						->p->t('The server encountered an error when processing your request. Please try again.')->end
					->end
					->div->class('actions')
						->button->type('button')->class('ui primary button approve')->t('OK')->end
					->end
				->end
				->script->src('vendor/components/jquery/jquery.min.js')->end
				->script->src('vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.bundle.js')->end
				->script->src('semantic-ui/semantic.js')->end
				->script->src('client.js')->end
			->end
		->end;
	}
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$attrs = $reader->parseAttributes();
		$attrs['byTag'] = Sabre\Xml\Element\KeyValue::xmlDeserialize($reader);
		return new static($attrs);
	}
}

