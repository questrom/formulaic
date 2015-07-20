---
title: Introduction
template: page.html
nav_groups:
  - primary
nav_sort: 1
---

### Configuration files

* Formulaic generates forms based on **configuration files**; these must not be confused with the global configuration stored in `config.toml`. 
* Configuration files are written in [**Jade**](http://jade-lang.com), which Formulaic will automatically compile to XML.
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

#### A brief note

The following documentation presupposes a general understanding of Jade syntax. When it speaks of "elements" and "attributes," these terms refer to *Jade* elements and attributes; don't try to put normal XML tags into configuration files.

A side note: make sure to **indent Jade files properly**. Small errors in indentation can lead to parse errors.