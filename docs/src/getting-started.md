---
title: Getting Started
template: page.html
nav_groups:
  - primary
nav_sort: 2
---

### Dependencies

The following dependencies are required in production.

1. PHP >= 5.5 (tested with 5.5.25.0)
    * The following extensions should be enabled:
        - `php_mbstring` - used in Jade parsing
        - `php_fileinfo` - used to handle file uploads
        - `php_mongo` - for MongoDB support (be sure to use a version >= 1.6.8)
        - Anything required by dependencies (search for "ext-" in composer.lock)
    * If you are using the XDebug extension for development, you may need to increase the value of the `xdebug.max_nesting_level` configuration option.
2. Apache (tested with 2.4.12)
    * `.htaccess` usage must be fully enabled
    * Serving over HTTPS is recommended if you are using password-protected forms
    * Besides the default ones, the following extensions should be enabled:
        - `mod_deflate` - for GZip
        - `mod_headers` and `mod_expires` - for proper caching
        - `mod_rewrite` - for routing
3. MongoDB 3.0 (recommeded but not strictly required; needed for data storage)

### Installation

1. Copy the files from the main project directory to the server.
2. Copy the `config/config-example.toml` file into `config/config.toml`. Modify this file as needed; some features will not work without proper configuration.
3. Depending on your configuration, you may need to enable some extensions for Apache and PHP.

### Basic usage

Going to the main page of the project will produce a list of forms; from this you can access the forms and their associated views. Some sample forms are included with the project.

To learn how to make a form, go to [the next page of this documentation](making-forms/introduction.html).

To learn more about the code base, go to [Understanding the Code](understanding-the-code/introduction.html).