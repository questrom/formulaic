
## Parts of forms

The following section describes each of the elements that can be nested within the "fields" element given in the configuration file. Each of these elements makes up a part of the form that is displayed to the user.

Before talking about each of these elements specifically, global attributes must be discussed.

### Global attributes

A few global attributes are shared by most (*not necessarily all*) of the elements that describe form fields:

* `name`

  This attribute assigns a name to a form field. It is used within the HTML "name" attribute of the generated form, and is also used as the key for the form field in MongoDB.

  Note that *no two fields in the same form may have the same name.* Ignoring this instruction will result in undefined (and probably undesired) behavior.

* `label`

  This attribute specifies the label which will go above a form field. All form fields *must* be given labels.

* `sublabel`

  This attribute specifies a piece of smaller text to be rendered in gray below a form field's label. It is useful for providing more detailed instructions to the user.

  In some cases, the sublabel will be generated from other attributes of the form field if it is not explicitly specified. For instance, it may display the minimum and maximum accepted values of a form control.

* `required`

  If provided, this attribute specifies that a value for the field *must* be provided by the user. Note that by default *all fields are optional*.

### Form fields

The following elements represent form fields; they can be put inside the "fields" element of the form's configuration file.

<hr>
#### The `checkbox` element

This element creates a checkbox.

##### Attributes
* `must-check`

  If provided, this attribute specifies that the user cannot submit the form without checking the checkbox.

<hr>
#### The `textbox` and `textarea` elements

These elements create single-line and multi-line text boxes, respectively.

##### Attributes
* `min-length` and `max-length`
  
  These specify the minimum and maximum number of characters that can be entered by the user, respectively.

* `must-match`

  This attribute specifies a regular expression which the text must match.

<hr>
#### The `password` element

This element creates a password input field. Putting this inside a `show-if` block (as described later) will result in unintended behavior (in some cases, the user will be able to submit the form without a password).

##### Attributes
* `match-hash`

  Specifies a salted and hashed password, made with PHP's `password_hash` function, which the entered password must match.

<hr>
#### The `dropdown` and `radios` elements

These elements create dropdown menus and lists of radio buttons, respectively. Both of them allow the user to select an option from a list, though they do so in different ways.

`option` elements nested inside in order to specify the options among which the user can choose. (See the sample forms to get a better idea of how this works.)

<hr>
#### The `checkboxes` element

This element creates a list of checkboxes, from which the user can choose zero or more options. As with `dropdown` and `radios`, child `option` elements are used to specify the list of available options.

##### Attributes
* `min-choices` and `max-choices`

These attributes specify the minimum and maximum nubmer of choices that the user can select.

If you ever set `max-choices` to be 1, you probably want to just use the `radios` element instead.

<hr>
#### The `range` element

This element creates a slider, which allows the user to select a numeric value within a specified range. If greater precision is needed, consider using the `number` element instead.

##### Attributes

* `min` and `max`

  These specify the minimum and maximum values for the slider, respectively.

* `def`

  Specifies the default value for the slider. Note that there is no way for the user to choose not to provide an input to a slider -- in other words, you cannot really have "optional" sliders.

* `step`
  
  Specifies the smallest increment along the slider. For instance, if this is set to 1, only integers can be provided; if set to 2, only even integers are allowed.

<hr>
#### The `phone`, `email`, and `url` elements

These elements provide textboxes in which the user can only enter phone numbers, email addresses, and URLs, respectively.

##### Attributes

* `must-have-domain`

  This attribute, which only applies to the `email` element, specifies a domain which the email address must have. For example, if this is set to `bu.edu`, the entered email must end with `@bu.edu`.

<hr>
#### The `number` element

Allows the user to input a precise numeric value.

##### Attributes

* `min` and `max`

  These specify the minimum and maximum values of the number.

<hr>
#### The `time` element

Allows the user to specify a time of day. Note that this will be stored in the database as a number of *minutes after midnight.*

##### Attributes

* `min` and `max`

  These specify, in "h:mm xm" format, the minimum and maximum times that can be entered.

* `step`

  If specified, the time (measured in minutes past midnight) must be a multiple of this number.

<hr>
#### The `date` element

Allows the user to specify a date without an associated time.


##### Attributes
* `min` and `max`

  These specify, in `YYYY-MM-DD` format, the minimum and maximum dates that can be entered in the form field.

<hr>
#### The `datetime` element

Allows the user to specify both a date and a time. To improve user-friendliness, use a single `datetime` field instead of separate `date` and `time` fields.

##### Attributes
* `min` and `max`
  
  These specify, in `YYYY-MM-DD hh:mm xm` format, the minimum and maximum values that can be entered.

* `step`

  If specified, the time (measured in minutes past midnight) must be a multiple of this number.

<hr>
#### The `file` element

Allows the user to upload a file to Amazon S3. Files are **never** stored locally, though it would be possible to implement this feature.

Note that you must *whitelist* specific pairs of file extensions and MIME types for this to work properly. Use `allow` elements to specify these extension-mimetype pairs -- see the sample forms for an example of how to do this.

##### Attributes
* `max-size`
 
  The maximum file size, in *bytes.* Note that the file size limit set in `php.ini` can override this.

* `permissions`

  If the file is to be uploaded to Amazon S3, this attribute specifies what permissions the file will have. `public-read` is probably what you want; for a full list of possible values, see [this page](https://github.com/tpyo/amazon-s3-php-class/blob/121318e65e857a994b22ffe0aa04a0c55e832bea/S3.php#L40).

<hr>
#### The `captcha` element

Creates a CAPTCHA, which the user must solve in order for the form to be submitted. Uses reCAPTCHA.

<hr>
### Groups of form fields

These elements allow form fields to be grouped and combined.

<hr>
#### The `group` element

Creates a border around a group of form controls in order to show that they are related. This element is purely cosmetic inasmuch as it does not affect the data stored in the database (or anywhere else).

<hr>
#### The `list` element

Essentially, this allows the user to duplicate a set of form fields an arbitrary number of times, thus allowing the creation of lists.

For example, to create a list of file uploads with associated captions, one might use:

```jade
list(name="pictures", label="Pictures to upload", add-text="Add a picture")
  file(label="Image file", name="image_file", max-size="10000000", permissions="public-read")
    allow(ext="jpg", mime="image/jpeg")
    allow(ext="png", mime="image/png")
  textbox(label="Caption", name="caption", required=true)
```
<br>
The associated data will be stored in the database as an array (in this example, the key for the array will be `pictures`).

##### Attributes

* `add-text`

  At the bottom of a list, there is a button to add an item to the list. This specifies the text on that button.

* `min-items`, `max-items`

  The minimum and maximum number of items that the user can input, respectively.

<hr>
### Other form elements

These elements, which can also be nested within the "fields" element, do not represent form fields at all; rather, they just display text to the user.

When placed inside of a `group`, these elements format themselves to match.

<hr>
#### The `header` element

Creates a heading (that is, a `<h1>-<h6>` element). The text of the header is placed inside of the element.

#### Attributes
* `size`

  The size (1-6) of the header.

* `icon`
  
  An icon to display next to the header. These icons come from [Semantic UI](http://semantic-ui.com/elements/icon.html).

* `subhead`

  Sub header text to display.

<hr>
#### The `notice` element

Creates a message offset from the surrounding text and form fields. Optionally, `li` elements can be placed inside of this element to create an unordered list.

#### Attributes

* `text`

  The text of the notice.

* `header`

  A header to be placed at the top of the notice.

* `icon`

  An icon displayed next to the notice. (See the documentation for the `header` element for more details.)

<hr>
### Conditionals

The `show-if` element is used to create conditional form fields: form fields that are only shown when a certain condition is met. The first child element within a `show-if` must be a condition; the second element must be a form field. See the sample forms to learn more about how this works.

The following elements specify conditions:

<hr>
#### The `is-checked` element

This condition is only satisfied when a checkbox (specified in the `name` attribute) is checked.

<hr>
#### The `is-not-checked` element

This condition is only satisfied when a checkbox (specified in the `name` attribute) is **NOT** checked.

<hr>
### The `is-radio-selected` element

This condition is matched if a radio button (whose value is specified by the `value` attribute) within a `radios` element (whose name is specified in the `name` attribute) is selected.