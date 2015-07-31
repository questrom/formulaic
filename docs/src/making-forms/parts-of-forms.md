---
title: Parts of Forms
template: page.html
nav_groups:
  - primary
nav_sort: 2
---

The following section describes the elements which can be placed within the `fields` section of a configuration file. Each of these elements will correspond to a part of the form that is displayed to the user.

<h3 class="ui header top attached">
Global attributes
</h3><div class="ui bottom attached segment">
A few "global attributes" can be applied to most (*not necessarily all*) of the elements in the `fields` section. Of these, `name` and `label` **must be provided** for all form fields (that is, all elements that accept user input).

* **`name`**

  This attribute assigns a name to a form field. This name will be used within the HTML "name" attribute of the generated form; as the key for the form field in data stored in MongoDB; and to refer to the field elsewhere in the configuration file. However, it will never be exposed to the user.

  Note that *no two fields in a form may have the same name.* Ignoring this instruction will cause problems.

* **`label`**
 
  This attribute specifies the label which will go above a form field. All form fields that accept user input *must* be given labels; otherwise, it would be impossible to distinguish fields from one another.

* **`sublabel`**

  This attribute specifies a piece of smaller text to be rendered in gray below a form field's label. It is useful for providing more detailed instructions to the user.

  In some cases, the sublabel will be generated from other attributes of the form field if it is not explicitly specified. For instance, it may display the minimum and maximum accepted values of a form control.

* **`required`**

  If provided, this attribute specifies that a value for the field *must* be provided by the user. Note that **by default all fields are optional.**
</div>

## Form fields

The following elements represent form fields that accept input from the user.

<h3 class="ui header top attached">
The `checkbox` element
</h3><div class="ui bottom attached segment">
This element creates a checkbox.

##### Attributes:
* **`must-check`**

  If provided, this attribute specifies that the user cannot submit the form without checking the checkbox.
</div>

<h3 class="ui header top attached">
The `textbox` and `textarea` elements
</h3><div class="ui bottom attached segment">
These elements create single-line and multi-line text boxes, respectively.

##### Attributes:
* **`min-length` and `max-length`**
  
  These specify the minimum and maximum number of characters that can be entered by the user.

* **`must-match`**

  This attribute specifies a regular expression which the text must match. Note that validation error messages do not expose the regular expression to the user, and are thus rather unhelpful.
</div>

<h3 class="ui header top attached">
The `password` element
</h3><div class="ui bottom attached segment">
This element creates a password input field. It is intended for creating forms which require a password to be submitted. Note that data from password fields is **never** permanently stored (in databases or otherwise) for the sake of security.

Putting a `password` element inside of a `show-if` element (as described later) will result in unintended behavior: if the field is hidden, the user will be able to submit the form without needing a password!

##### Attributes:
* **`match-hash`**

  Specifies a salted and hashed password, created with PHP's `password_hash` function, which the entered password must match.

  There is no way to specify the password in plaintext for security reasons.
</div>

<h3 class="ui header top attached">
The `dropdown` and `radios` elements
</h3><div class="ui bottom attached segment">
These elements create dropdown menus and lists of radio buttons, respectively. Both of them allow the user to select an option from a list; the difference is purely cosmetic.

In order to specify the individual options available to the user, simply nest `option` elements inside. (See the sample forms to learn the precise syntax for this.)
</div>

<h3 class="ui header top attached">
The `checkboxes` element
</h3><div class="ui bottom attached segment">
This element creates a list of checkboxes, from which the user can select zero or more options. As with `dropdown` and `radios`, child `option` elements are used to specify the list of available options.

##### Attributes:
* **`min-choices` and `max-choices`**
  
  These attributes specify the minimum and maximum number of choices that the user can select.
</div>

<h3 class="ui header top attached">
The `range` element
</h3><div class="ui bottom attached segment">
This element creates a slider, which allows the user to select a numeric value within a specified range. If greater precision is needed, consider using the `number` element instead.

##### Attributes

* **`min` and `max`**
  
  These attributes specify the minimum and maximum values for the slider, respectively.

* **`def`**

  Specifies the default value for the slider. Note that there is no way for the user to choose not to provide input to a slider -- in other words, you cannot really have "optional" sliders.

* **`step`**

  Specifies the smallest increment along the slider. For example: if this is set to 1, only integers are allowed; if it is set to 2, only even integers will be accepted.
</div>

<h3 class="ui header top attached">
The `phone`, `email`, and `url` elements
</h3><div class="ui bottom attached segment">
These elements provide textboxes in which the user can only enter phone numbers, email addresses, and URLs, respectively.

##### Attributes:

* **`must-have-domain`**

  This attribute, which applies only to the `email` element, specifies a domain which the email address must have. For instance, if this attribute is set to `bu.edu`, the entered email must end with `@bu.edu`.
</div>

<h3 class="ui header top attached">
The `number` element
</h3><div class="ui bottom attached segment">
This element allows the user to input a precise numeric value.

##### Attributes:

* **`min` and `max`**
  
  These specify the minimum and maximum values that the user can enter.
</div>

<h3 class="ui header top attached">
The `time` element
</h3><div class="ui bottom attached segment">
This element allows the user to specify a time of day. Note that this will be stored in the database as the number of *seconds after midnight,* to avoid possible issues with date/time conversions.

##### Attributes:

* **`min` and `max`**

  These specify, in "h:mm xm" format, the minimum and maximum times that the user can enter.

* **`step`**

  If specified, the time (measured in *minutes* past midnight) must be a multiple of this number.
</div>

<h3 class="ui header top attached">
The `date` element
</h3><div class="ui bottom attached segment">
This element allows the user to specify a date without an associated time.

##### Attributes:
* **`min` and `max`**

  These specify, in `YYYY-MM-DD` format, the minimum and maximum dates that can be entered in the form field.
</div>

<h3 class="ui header top attached">
The `datetime` element
</h3><div class="ui bottom attached segment">
This element allows the user to specify a date without an associated time.

No time zone conversion is performed to avoid [problems](http://www.wired.com/2012/06/falsehoods-programmers-believe-about-time/) and the associated [headaches](http://i.ytimg.com/vi/-5wpm-gesOY/maxresdefault.jpg). The `time-zone` option in the global configuration file is used elsewhere within Formulaic.

##### Attributes:
* **`min` and `max`**

  These specify, in `YYYY-MM-DD hh:mm xm` format, the minimum and maximum values that can be entered in the form field.

* **`step`**

  If specified, the time of day (measured in minutes past midnight) must be a multiple of this number. This does not take into account the date in any way.
</div>

<h3 class="ui header top attached">
The `file` element
</h3><div class="ui bottom attached segment">
This element allows the user to upload a file, which can be stored in Amazon S3. Files are **never** stored locally on the server for more than a short period of time.

For this element to work properly, you must *whitelist* specific pairs of file extensions and MIME types that can be uploaded. The `allow` element is used to specify these pairs -- see the sample forms for the syntax to use.

##### Attributes:
* **`max-size`**
  
  The maximum file size, in *bytes*. Note that the file size limit set in `php.ini` can override this.

* **`permissions`**

  If the file is to be uploaded to Amazon S3, this attribute specifies what permissions the file will have. `public-read` is probably what you want; for a full list of possible values, see [this page](https://github.com/tpyo/amazon-s3-php-class/blob/121318e65e857a994b22ffe0aa04a0c55e832bea/S3.php#L40).
</div>

<h3 class="ui header top attached">
The `captcha` element
</h3><div class="ui bottom attached segment">
This element uses reCAPTCHA to add a CAPTCHA to the form. This is useful for preventing spam.
</div>

## Groups of form fields

These elements do not represent individual form fields; rather, they provide ways to combine other form fields together. In general, they can be arbirarily nested within each other (though this has not been exhaustively tested in all cases).

<h3 class="ui header top attached">
The `group` element
</h3><div class="ui bottom attached segment">
Creates a border around a group of form controls in order to show that they are related. This element is purely cosmetic: it does not affect the data stored in the database (or anywhere else).

The children of this element represent the form fields to be grouped together.
</div>

<h3 class="ui header top attached">
The `list` element
</h3><div class="ui bottom attached segment">
Essentially, this allows the user to duplicate a set of form fields an arbitrary number of times, thus allowing the creation of lists.

This element is best explained by example. To create a list of file uploads with associated captions, one might use:

```jade
list(name="pictures", label="Pictures to upload", add-text="Add a picture")
  file(label="Image file", name="image_file", max-size="10000000", permissions="public-read")
    allow(ext="jpg", mime="image/jpeg")
    allow(ext="png", mime="image/png")
  textbox(label="Caption", name="caption", required=true)
```

The associated data will be stored in the database as an array (in this example, the key for the array will be `pictures`).

##### Attributes

* **`add-text`**

  Specifies the text on the button that is used to add an item to the list.

* **`min-items` and `max-items`**
  
  These attributes specify the minimum and maximum number of items that the user can provide.
</div>

## Conditionals

The `show-if` element is used to create conditional form fields: form fields that are only shown when a certain condition is met. The first child element within a `show-if` must be a **condition**, the second element must be the **form field** which is to be shown/hidden.

To put a group of form fields behind a single conditional, use the `group` element to combine them.

#### The following elements specify conditions:

<h3 class="ui header top attached">
The `is-checked` element
</h3><div class="ui bottom attached segment">
This condition is only satisfied when a checkbox is checked.
##### Attributes:
* **`name`**

  The name of the checkbox.
</div>

<h3 class="ui header top attached">
The `is-not-checked` element
</h3><div class="ui bottom attached segment">
This condition is only satisfied when a checkbox is **not** checked.
##### Attributes:
* **`name`**

  The name of the checkbox.
</div>

<h3 class="ui header top attached">
The `is-radio-selected` element
</h3><div class="ui bottom attached segment">
This condition is only satisfied when a radio element having a certain value is selected.
##### Attributes:
* **`name`**

  The name of the `radios` element.

* **`value`**

  The text of the `option` element representing the radio button. In other words, this attribute specifies which radio button inside of the `radios` element is to be used.
</div>

## Other form elements

These elements, which can also be placed within the `fields` element, do not represent form fields at all; instead, they display text to the user. When placed inside of a `group`, these elements format themselves nicely to match.

<h3 class="ui header top attached">
The `header` element
</h3><div class="ui bottom attached segment">
Creates a heading; the text of the heading should be placed inside of the `header` element.

##### Attributes:
* **`size`**

  The size of the header, where `1` is the largest and `6` is the smallest.

* **`icon`**

  An icon to display next to the header. These icons come from [Semantic UI](http://semantic-ui.com/elements/icon.html). For example, if you want the "alarm" icon, set&nbsp;&nbsp;`icon="alarm"`.

* **`subhead`**

  Text to be displayed at a smaller size beneath the header itself.
</div>

<h3 class="ui header top attached">
The `notice` element
</h3><div class="ui bottom attached segment">
Creates a message offset from the surrounding text and form fields. Optionally, `li` elements can be placed inside of this element to create an unordered list.

Unlike with `header`, the text inside of a notice must be specified using the `text` attribute, instead of being placed directly within the element.

##### Attributes:

* **`text`**

  The text contained in the notice.

* **`header`**

  A header to be placed at the top of the notice.

* **`icon`**

  An icon displayed next to the notice. (See the documentation for the `header` element for more details.)
</div>

<h3 class="ui header top attached">
The `inject` element
</h3><div class="ui bottom attached segment">
This element allows HTML to be injected into the form. For this to work, it must be enabled in `config/config.toml`; it is **disabled by default** for security reasons.

Note that the HTML inserted in this way must also be valid XML, so that the configuration file parser doesn't mess it up. Trying to inject Jade code instead of HTML will cause issues, as the Jade parser used in the project is suited only to configuration-file parsing.

To use this element, simply place HTML inside of it, preferably as [piped text](http://jade-lang.com/reference/plain-text/).

##### Attributes:

* **`no-sanitize`**

  Do not attempt to sanitize HTML at all. This attribute can be disabled in `config/config.toml`. Note that you should not put too much trust in the sanitizer (HTMLPurifier), as it is unlikely to be entirely bug-free.
</div>