---
title: Home
template: page.html
nav_groups:
  - primary
nav_sort: 1
---

<h1 class="ui center aligned header" style="font-size:3em">
    Formulaic
    <p class="sub header" style="font-size: 0.5em">Build complex webforms using simple, declarative configuration files.</p>
</h1>

### Features

* **Creates** well-designed, easy-to-use forms for nearly any purpose.
* **Validates** data provided by the user with helpful feedback.
* **Sends** data to Amazon S3, MongoDB, or email addresses.
* **Views** data stored in MongoDB in tabular or graphical form.

### Advantages

* **Rapid development:** forms can be developed much more quickly using Formulaic than using HTML/CSS/JS/PHP alone: Formulaic takes care of most of the work.
* **Security:** Formulaic automatically protects against XSS, XSRF, and unrestricted file upload attacks.
* **Robustness:** In accordance with the [robustness principle](https://en.wikipedia.org/wiki/Robustness_principle), it attempts to give very strong guarantees about the data it stores.
* **Flexibility:** Formulaic can create forms of all types, including ones with conditionally displayed fields, nested lists, and file uploads.
* **Extensibility:** Formulaic is built to be extensible, so that it can become even more useful in the future.

### Architecture

* Formulaic is written in **PHP** so that it can be set up quickly in most environments.
* It uses several small libraries installed through **Composer**, but it is not based on a large framework.
* It requires **Apache** for `.htaccess` support.
* Most of the logic is performed on the **server side** to improve performance and keep things simple.
* The client side uses **Semantic UI**, making it easily themeable.