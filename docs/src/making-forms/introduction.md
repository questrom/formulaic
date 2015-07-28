---
title: Introduction
template: page.html
nav_groups:
  - primary
nav_sort: 1
---

### Configuration files

* Formulaic generates forms based on **configuration files**; these must not be confused with the global configuration stored in `config.toml`. 
* Configuration files are written in [**Jade**](http://jade-lang.com), which Formulaic will automatically compile to XML. See below for further details.
* The **sample forms** included with the project provide an excellent way to get used to the syntax &mdash; in fact, these may be more useful that the following documentation.

### Adding a form

Adding a new form is as simple as placing a new Jade file in the `forms` folder of the project. It will be automatically recognized, provided that its filename matches the regular expression `/^[A-za-z0-9_-]+\.jade$/`.

This example (which is also a working configuration file) shows the basic structure:

```jade
form(name="Title of form")
  fields
    //- A list of headers, form fields, etc. to be put into the form
  outputs
    //- Specifies where the data submitted via the form will be stored or sent
  views
    //- A list of ways in which this data can be formatted and displayed
```

<br>
To learn more about...
* The contents of the `fields` element, see [Parts of Forms](parts-of-forms.html).
* The contents of the `outputs` element, see [Outputs](outputs.html).
* The contents of the `views` element, see [Views](views.html).

### An important note about Jade

Note that there is **one key difference** between the Jade used in this app and Jade templates in general. When a `#` character is used in a tag (e.g. `checkbox#foo`), Jade would normally interpret that as an `id` attribute. In other words, it would be equivalent to `checkbox(id="foo")`. However, in this app the `#` character specifies the **`name`** attribute instead. So `checkbox#foo` means `checkbox(name="foo")`. This allows for more concise configuration files, and is used in some of the sample forms. The meaning of the `name` attribute as applied to different elements will be explained later in this documentation.

The following documentation presupposes a general understanding of Jade syntax. When it speaks of "elements" and "attributes," these terms refer to *Jade* elements and attributes; don't try to put normal XML tags into configuration files.

A side note: make sure to **indent Jade files properly**. Small mistakes in indentation can lead to fatal parse errors.