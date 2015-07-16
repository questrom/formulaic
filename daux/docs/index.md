<p class="lead">
	Build complex webforms using simple, declarative configuration files.
</p>

### Features

* **Creates** well-designed, easy-to-use forms for nearly any purpose.
* **Validates** data provided by the user with helpful feedback.
* **Sends** data to Amazon S3, MongoDB, or email addresses.
* **Views** data stored in MongoDB in tabular or graphical form.

### Advantages

* **Rapid development:** forms can be developed much more quickly using formbuilder than using HTML/CSS/JS/PHP alone: formbuilder takes care of most of the work.
* **Security:** formbuilder automatically protects against XSS and XSRF attacks.
* **Robustness:** In accordance with the [robustness principle](https://en.wikipedia.org/wiki/Robustness_principle), it attempts to give very strong guarantees about the data it stores.
* **Flexibility:** formbuilder allows webforms to be created with complex features such as conditionally hidden/displayed form fields, nested lists, and file uploads.
* **Extensibility:** formbuilder is built to be extensible, so that it can become even more useful in the future.

### Architecture

* formbuilder is written in **PHP**. It uses several small libraries installed through **Composer**, but it is not based on a large framework.
* Most of the logic is performed on the **server side** to improve performance and simplicity.

### Getting started

Going to the main page of the project will produce a list of forms; from this you can access the forms and their associated views. Some sample forms are included with the project; for more information, see the rest of this documentation.

You may need to adjust some of the parameters in `config/config.toml` for certain features to work (e.g. Amazon S3, email sending, CAPTCHAs).