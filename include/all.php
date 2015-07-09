<?php

// Load dependencies
require 'vendor/autoload.php';
require 'jade/autoload.php.dist';

// Utils
require 'include/utils.php';


// Misc settings
date_default_timezone_set(Config::get()['time-zone']);



// Config parsing
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
require 'include/Component.php';

// Outputs
require 'include/Output.php';

// Views
require 'include/View.php'; // include/ needed hee
require 'include/TableView.php';
require 'include/DetailsView.php';
require 'include/GraphView.php';
require 'include/EmailView.php';

// Main form list
require 'include/FormList.php';