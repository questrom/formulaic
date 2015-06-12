
var hg = mercury,
	h = mercury.h;

// document.getElementById('formholder').appendChild(form(js));

function checkbox(o) {
	return h('.field',
		h('.ui.checkbox', [
			h('input', {name: o.name, type: 'checkbox'}),
			h('label', o.label)
		])
	);
}

function textbox(o) {

	return h('.field',
		h('.ui.input', [
			h('input', { name: o.name, type: 'text' }),
			h('label', o.label)
		])
	);
}

function Form() {

}

function form(o) {
	o = o.form;
	return h('form.ui.form', {action: 'submit.php', method: 'POST'}, [
		o.map(function(inner) {
			console.log('$I', inner);
			if(inner.type === "checkbox") {
				return checkbox(inner);
			} else {
				return textbox(inner);
			}
		}),
		h('input.submit.button', {type:'Submit', value:'hey'})
	]);
}


var semantic = {};

  semantic.validateForm = {};

 semantic.validateForm.ready = function() {
	$('.ui.checkbox').checkbox();
		$('.ui.form').form({
			tb01: {
				identifier: 'tb01',
			rules: [
				{
				      type   : 'empty',
		          prompt : 'Please enter a password'
		      }
			]
			}
		}, {
			inline: true, on: 'blur'
		});
};


$(document)
  .ready(function() {

	mercury.app(document.getElementById('formholder'), mercury.state({ form: mercury.value( js ) }), form);

  	semantic.validateForm.ready();
  })
;