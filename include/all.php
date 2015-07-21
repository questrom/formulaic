<?php

# Composer dependencies
require 'vendor/autoload.php';

# Forked and slightly modified version of Jade.php
# see: https://github.com/everzet/jade.php
require 'jade/autoload.php.dist';

# Miscellaneous utility functions
require 'include/utils.php';

# Set the time zone manually so PHP won't complain
date_default_timezone_set(Config::get()['time-zone']);

# Parser for configuration files (and miscellaneous related stuff)
require 'include/Parser.php';

// Show-if conditions
require 'include/Condition.php';

// HTML generation
require 'include/HTMLGenerator.php';

// Form valdation
require 'include/Validate.php';

// Components
require 'include/ComponentHelpers.php';
require 'include/ComponentAbstract.php';
require 'include/FormPart.php';
require 'include/Cells.php';
require 'include/Component.php';

// Outputs
require 'include/Output.php';

// Views
require 'include/View.php';
require 'include/TableView.php';
require 'include/DetailsView.php';
require 'include/GraphView.php';
require 'include/EmailView.php';

// Main form list
require 'include/FormList.php';