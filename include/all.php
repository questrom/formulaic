<?php

# Various things =========================================================================

# Composer dependencies
require 'vendor/autoload.php';

# Forked and slightly modified version of Jade.php
# see: https://github.com/everzet/jade.php
require 'jade/autoload.php.dist';

# Miscellaneous utility functions
require 'include/utils.php';

# Set the time zone manually so PHP won't complain
date_default_timezone_set(Config::get()['time-zone']);

# Parses and manages configuration files
require 'include/Parser.php';

# Conditions within "show-if" elements.
require 'include/Condition.php';

# DSL for generating HTML
require 'include/HTMLGenerator.php';

# Form validation helper
require 'include/Validate.php';

# Components =========================================================================

# These files contain some (not all) "components": that is, classes with two properties:
# - They implement the Configurable interface, and thus can be created from
#   elements placed inside configuration files.
# - They have methods which create Renderable objects -- in other words,
#   they allow the creation of HTML elements.
# Many are also Storeable: i.e., they have associated data that can be stored
# inside of, say, MongoDB.

# More specifically, ComponentAbstract.php contains some interfaces, traits, and
# abstract classes used by components, while Component.php contains the
# components themselves.

require 'include/ComponentAbstract.php';
require 'include/Component.php';

# Renderables ========================================================================

# These files contain some (not all) classes implementing Renderable. Such classes
# can be turned into HTML.

# More specifically, FormPart.php contains Renderables primarily used within forms,
# while Cells.php contains ones primarily used within tables.

require 'include/FormPart.php';
require 'include/Cells.php';

# Outputs ===========================================================================

# This file includes various classes that act as Outputs - places where data from form
# submissions can be sent, stored, or otherwise handled.

require 'include/Output.php';

# Views =============================================================================

# These files include various classes related to views -- that is, ways in which data
# from form submissions can be displayed.

# More specifically:
# View - General interfaces and helpers
# TableView - Things related to table views
# GraphView - Things related to graph views
# DetailsView - The view shown after clicking the "Details" button in a table
# EmailView - Used by the "email-to" output

require 'include/View.php';
require 'include/TableView.php';
require 'include/GraphView.php';
require 'include/DetailsView.php';
require 'include/EmailView.php';

# FormList =============================================================================

# This file includes classes used to generate the main list of forms shown upon entering
# the application.

require 'include/FormList.php';