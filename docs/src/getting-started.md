---
title: Getting Started
template: page.html
nav_groups:
  - primary
nav_sort: 2
---

### Installation

1. Install PHP >= 5.5 and Apache. The application was tested with PHP 5.5.25.0 and Apache 2.4.12, but similar versions should also work fine.
2. Copy the files from the main project directory to the server.
3. Copy the `config/config-example.toml` file into `config/config.toml`. Modify this file as needed; some features will not work without proper configuration.
4. Depending on your configuration, you may need to enable some extensions for Apache and PHP.

### Basic usage

Going to the main page of the project will produce a list of forms; from this you can access the forms and their associated views. Some sample forms are included with the project.

To learn how to make a form, go to [the next page of this documentation](making-forms/introduction.html).

To learn more about the code base, go to [Understanding the Code](understanding-the-code/introduction.html).