<?php

date_default_timezone_set('America/New_York');



// Utils
require ('include/utils.php');

// Config parsing
require('include/Parser.php');

// HTML generation
require('include/HTMLGenerator.php');

// Form valdation
require('include/Validate.php');

// Components
require('include/ComponentHelpers.php');
require('include/ComponentAbstract.php');
require('include/Component.php');

// Outputs
require('include/Output.php');

// Views
require('include/TableView.php');
require('include/DetailsView.php');