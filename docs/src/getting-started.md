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
    * Composer (see below) will tell you if you are missing any required PHP extensions
    * If you are using the XDebug extension for development, you may need to increase the value of the `xdebug.max_nesting_level` configuration option.
2. Apache (tested with 2.4.12)
    * `.htaccess` usage must be fully enabled
    * Serving over HTTPS is recommended if you are using password-protected forms
    * Besides the default ones, the following extensions should be enabled:
        - `mod_deflate` - for GZip
        - `mod_headers` and `mod_expires` - for proper caching
        - `mod_rewrite` - for routing
3. MongoDB 3.0 (for data storage)
4. [Composer](https://getcomposer.org) (tested with version "1.0-dev")

### Installation

1. Copy the files from the main project directory to the server.
2. Run `composer install` to install all PHP dependencies
3. Copy the `config/config-example.toml` file into `config/config.toml`. Modify this file as needed; some features will not work without proper configuration.
4. Test the app's features to make sure things are working. In particular, make sure the `.htaccess` settings have taken effect.


### Basic usage

Going to the main page of the project will produce a list of forms; from this you can access the forms and their associated views. Some sample forms are included with the project.

To learn how to make a form, go to [the next page of this documentation](making-forms/introduction.html).

To learn more about the code base, go to [Understanding the Code](understanding-the-code/introduction.html).

### Rebuilding these docs

These documentation pages are made with [Metalsmith](http://metalsmith.io). To build them:

1. Go into the `docs` folder
2. Run `npm install`
3. Run `node build.js`
    * If you receive an error, you may need to run `node build.js` a second time