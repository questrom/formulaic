formbuilder creates forms based on configuration files written in [**Jade**](http://jade-lang.com/); these are compiled using [jade.php](https://github.com/everzet/jade.php) into XML, which is then parsed and used to render the form.

The best way to learn how these configuration files work is to look at the **sample forms** included in the project. These demonstrate many (though not all) of the features formbuilder provides.

### Adding a form

To add a new form, simply place a Jade file in the `forms` folder. It will be automatically recognized, provided that its filename matches the regular expression `/^[A-za-z0-9_-]+\.jade$/`.

The **basic structure** of a form configuration file is as follows:

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
The next page of this documentation describes the contents of the "fields" element.
