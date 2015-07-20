---
title: Outputs
template: page.html
nav_groups:
  - primary
nav_sort: 3
---

The elements described on this page are used to specify how the data submitted through a form will be **processed**, **distributed**, and **stored**. They must be placed within the `outputs` section of a [configuration file](introduction.html).

<h3 class="ui header top attached">
The `s3` element
</h3><div class="ui bottom attached segment">
This element specifies that files uploaded through the form should be stored in Amazon S3. It must be placed **before** any other output elements in the configuration file.

Note that uploaded files will be renamed for security reasons. Also note that S3 is currently the *only* way of storing uploadeded files (they are never stored locally on a permanent basis).

##### Attributes:

* **`bucket`**

  Specifies in which S3 bucket the uploaded files will be stored.
</div>

<h3 class="ui header top attached">
The `mongo` element
</h3><div class="ui bottom attached segment">
This element specifies that form submissions should be stored in MongoDB.

Unlike the other outputs described on this page, MongoDB is treated as read-write rather than write-only. **For this reason, MongoDB is used by [views](views.html) to retrieve data.** In particular, the first `mongo` element present in a configuration file will be used by the views.

##### Attributes:
* **`server`**

  Specifies the `mongodb://` URL of the MongoDB server. See [this page from the PHP docs](http://php.net/manual/en/mongoclient.construct.php) for more information about the URL format.

* **`database` and `collection`**

  These attributes specify the database and collection in which the data should be stored.
</div>

<h3 class="ui header top attached">
The `mongo` element
</h3><div class="ui bottom attached segment">
This element specifies that form submissions should be emailed to a particular person. To send submissions to multiple addresses, provide multiple `email-to` elements as needed.

For this to work properly, SMTP must be configured in the `config.toml` file. 

##### Attributes:

* **`from`**

  Specifies the From header of the email (for example,&nbsp;&nbsp;`Form Builder <formbuilder@bu.edu>` would work).

* **`to`**

  Specifies the email address to which emails will be sent.

* **`subject`**

  Specifies the email's subject line.
</div>