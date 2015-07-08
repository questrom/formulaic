<?php


class DropdownFormPart extends FormPart {
    function render() {
		return fieldBox($this->h, $this->f->required)
			->addH($this->f->getLabel())
			->div->class('ui fluid dropdown selection')
				->input->name($this->f->name)->type('hidden')->value('')->end
				->div->class('default text')->t('Please choose an option...')->end
				->i->class('dropdown icon')->end
				->div->class('menu')
					->addH(array_map(
						function($v) {
							return $this->h
							->div
								->class('item')->data('value', $v)->t($v)
							->end;
						},
						$this->f->options
					))
				->end
			->end
		->end;
	}
}


class RadiosFormPart extends FormPart {
    public function render() {
        return $this->h
            ->div->class('grouped fields validation-root ' . ($this->f->required ? 'required' : ''))
            	->data('radio-group-name', $this->f->name)
            ->addH($this->f->getLabel())
            ->addH(
                array_map(
                    function($v) {
                        return $this->h
                            ->div->class('field not-validation-root')
                            	->div->class('ui radio checkbox')
                            		->input->name($this->f->name)->type('radio')->value($v)->end
                            		->label->t($v)->end
                            	->end
                            ->end;
                    },
                    $this->f->options
                )
            )
            ->end;
    }
}


class TextareaFormPart extends FormPart {
    function render() {
        return $this->h
            ->ins(fieldBox($this->h, $this->f->required))
            ->addH($this->f->getLabel())
            ->textarea->name($this->f->name)->end
            ->end;
    }
}


class DateTimePickerFormPart extends FormPart {
    function render() {
		return $this->h
		->div->class('field ' . ($this->f->required ? ' required' : ''))
			->addH($this->f->getLabel())
			->div->class('ui left icon input')
				->i->class('calendar icon')->end
				->input->type('text')->name($this->f->name)->data('inputmask', " 'alias': 'proper-datetime' ")->end
			->end
		->end;
	}
}


class TimeInputFormPart extends FormPart {
	function render() {
		return $this->h
		->div->class('field ' . ($this->f->required ? ' required' : ''))
			->addH($this->f->getLabel())
			->div->class('ui left icon input')
				->i->class('clock icon')->end
				->input
                    ->type('text')
                    ->name($this->f->name)
                    ->data('inputmask', " 'alias': 'h:s t', 'placeholder': 'hh:mm am' ")
                ->end
			->end
		->end;
	}
}


class CheckboxFormPart extends FormPart {
	function render() {
		return $this->h
		->div->class('field ' . ($this->f->mustCheck ? 'required' : ''))
			->div->class('ui checkbox')
				->input->type('checkbox')->name($this->f->name)->end
				->addH($this->f->getLabel())
			->end
		->end;
	}
}


class ShowIfComponentFormPart extends FormPart {
    function render() {
    	// Provide a way of specifying type of cnodition, then read this in client.js
		return $this->h
			->div
				->data('show-if-name', $this->f->condition->getName())
				->data('show-if-condition', $this->f->condition->getCondition())
				->addH($this->f->items[0]->makeFormPart())
			->end;
	}
}


class RangeFormPart extends FormPart {
    function render() {
       	return $this->h
		->div->class('ui field')
			->addH($this->f->getLabel())
			->div
				->input
					->type('range')
					->name($this->f->name)
					->max($this->f->max)
					->min($this->f->min)
					->step($this->f->step)
					->value($this->f->def)
				->end
				->span->class('ui left pointing horizontal label range-value')
				->end
			->end
		->end;
    }
}



class ListComponentFormPart extends FormPart {
    function render() {

		return $this->h
		->div->class('ui field validation-root list-component')->data('count','0')->data('group-name', $this->f->name)
                ->data('validation-name', $this->f->name)
			->h5->class('top attached ui message')->t($this->f->label)->end
			->div->class('ui bottom attached segment list-items')
				->script->type('text/template')
					->addH(
                        // Forcibly HTML-encode things so that nested lists are generated properly...
                        generateString(
                            (new HTMLParentlessContext())->div->class('ui vertical segment close-item')
                                ->div->class('content')
                                    ->addH( array_map(function($x) { return $x ? $x->makeFormPart() : null; }, $this->f->items) )
                                ->end
                                ->button->type('button')->class('ui compact negative icon button delete-btn')
                                    ->i->class('trash icon')->end
                               ->end
                            ->end
                        )
                    )
				->end
				->div->class('ui center aligned vertical segment')
					->button->type('button')->class('ui primary labeled icon button add-item')
						->i->class('plus icon')->end
						->t($this->f->addText)
					->end
				->end
			->end
		->end;
    }
}


class GroupFormPart extends FormPart {
    function render() {

        $items = array_map(function($item) {
            if($item instanceof Header) {
                return new GroupHeader($item->__args);
            } else if($item instanceof Notice) {
                return new GroupNotice($item->__args);
            } else {
                return $item;
            }
        }, $this->f->items);

        return $this->h
            ->div->class('group')
            ->addH(array_map(function($value) {
                if(is_array($value)) {
                    return (new HTMLParentlessContext())->div->class('ui segment attached')
                        ->addH(
                            array_map(function($x) { return $x ? $x->makeFormPart() : null; }, $value)
                        )
                        ->end;
                } else {
                    return $value->makeFormPart();
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




class CheckboxesFormPart extends FormPart {
    function render() {
        return $this->h
            ->div
                ->class('grouped fields validation-root ' . ($this->f->required ? 'required' : ''))
                ->data('validation-name', $this->f->name)
                ->addH($this->f->getLabel())
                ->addH(
                    array_map(
                        function($v) {
                            return $this->h->div->class('field not-validation-root')
                                    ->div->class('ui checkbox')
                                     ->input->name($this->f->name . '[]')->type('checkbox')->value($v)->end
                                      ->label->t($v)->end
                                    ->end
                                ->end;
                        },
                        $this->f->options
                    )
                )
            ->end;
    }
}


class CaptchaFormPart extends FormPart {
    function render() {
        $cc = makeCaptcha();
        return $this->h
		->div->class('ui field required')
			->div->class('ui card')
				->div->class('image')
					->img
						->src($cc['data'])
					->end
				->end
				->div->class('content')->data('validation-name', '_captcha')
					->p
						->t('Please prove that you are not a robot by entering the above code into the box below.')
					->end
					->div->class('ui input')
						->input->type('text')->name('_captcha[]')->placeholder('Enter code...')->end
						->input->type('hidden')->name('_captcha[]')->value($cc['id'])->end
					->end
				->end
			->end
		->end;
    }
}


class FormElemFormPart extends FormPart {
    function render() {
        return $this->h
		->form->class('ui form')->action('submit.php')->method('POST')
			->addH( array_map(function($x) { return $x ? $x->makeFormPart() : null; }, $this->f->items) )
			->input
				->type('hidden')
				->name('csrf_token')
				->value('__{{CSRF__TOKEN}}__')
			->end
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


class PageFormPart extends FormPart {
    function render() {
        return $this->h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->f->title)->end
				->link->rel('stylesheet')->href('lib/semantic.css')->end
				->link->rel('stylesheet')->href('styles.css')->end
			->end
			->body
				->div->class('ui text container')
					->div->class('ui segment')
						->addH($this->f->form->makeFormPart())
					->end
				->end
				->div->class('success-modal ui small modal')
					->div->class('header')
						->t('Submission complete')
					->end
					->div->class('content')
						->p->t($this->f->successMessage)->end
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
				->script->src('lib/jquery.js')->end
				->script->src('lib/jquery.inputmask.bundle.js')->end
				->script->src('lib/semantic.js')->end
				->script->src('client.js')->end
			->end
		->end;
    }
}
