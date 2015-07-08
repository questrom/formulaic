<?php

// Load dependencies
require('vendor/autoload.php');
require('jade/autoload.php.dist');

// Utils
require ('utils.php');


// Misc settings
date_default_timezone_set(Config::get()['time-zone']);



// Config parsing
require('Parser.php');

// Show-if conditions
require('Condition.php');

// HTML generation
require('HTMLGenerator.php');

// Form valdation
require('Validate.php');

// Components
require('ComponentHelpers.php');
require('ComponentAbstract.php');
require('Component.php');

// Outputs
require('Output.php');

// Views
require('TableView.php');
require('DetailsView.php');
require('GraphView.php');
require('EmailView.php');