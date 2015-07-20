---
title: Outputs
template: page.html
nav_groups:
  - primary
nav_sort: 3
---

The elements described in this section are used to specify where the data submitted via the form will be sent or stored. They must be placed within the `outputs` element (as described in the Introduction). 

<hr>
#### The `s3` element

The `s3` output stores file upload data in Amazon S3, which must be configured in the `config.toml` file. It must be placed **before** any other outputs in the configuration file.

Note that uploaded files will be renamed for security reasons. Also note that S3 is currently the *only* way of storing uploadeded files at the moment.

##### Attributes
* The `bucket` attribute specifies in which bucket the file uploads will be stored.

<hr>
#### The `mongo` element

The `mongo` output stores submitted form data in MongoDB.

Unlike the other outputs in this section, MongoDB is read-write rather than write-only. For this reason, the first `mongo` element in the configuration file will be used by the "views" described in the following section.

##### Attributes
* `server` specifies the `mongodb://` url of the database. See [this page from the PHP docs](http://php.net/manual/en/mongoclient.construct.php) for more information about the URL format.
* `database` and `collection` specify the database and collection where data is to be stored, respectively.

<hr>
#### The `email-to` element

This element specifies a person to whom the form submission will be sent. To send submissions to multiple addresses, enter multiple `email-to` elements as needed.

For this to work properly, SMTP must be configured in the `config.toml` file. 

#### Attributes
* `to` specifies the address to which emails will be sent
* `from` specifies the From header of the email (e.g. `Form Builder <formbuilder@bu.edu>`)
* `subject` specifies the email's subject line.

