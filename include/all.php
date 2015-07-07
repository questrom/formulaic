<?php

require('vendor/autoload.php');

date_default_timezone_set('America/New_York');

require('jade/autoload.php.dist');

// Utils
require ('utils.php');

// Config parsing
require('Parser.php');

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