<?php

# Various things =========================================================================

# Composer dependencies
require 'vendor/autoload.php';

# Create directories
if(!is_dir('cache')) { mkdir('cache'); }

# Set charset
voku\helper\UTF8::checkForSupport(); # will run mb_internal_encoding if necessary
ini_set('default_encoding', 'UTF-8');

# Forked and slightly modified version of Jade.php
# see: https://github.com/everzet/jade.php
require 'jade/autoload.php.dist';

# Miscellaneous utility functions
require 'utils.php';

# Set the time zone manually so PHP won't complain
date_default_timezone_set(Config::get()['time-zone']);

# Parses and manages configuration files
require 'Parser.php';

# Conditions within "show-if" elements.
require 'Condition.php';

# Form validation helper
require 'Validate.php';

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

require 'ComponentAbstract.php';
require 'Component.php';

# DSL for generating HTML ============================================================

# This is similar to "hyperscript" in JS -- it provides a simple way of making HTML.
# HTMLGenerator - includes objects used to generate objects representing HTML
# Stringifier - handles converting such objects into strings

require 'HTMLGenerator.php';
require 'Stringifier.php';

# Renderables ========================================================================

# These files contain some (not all) classes implementing Renderable. Such classes
# can be turned into HTML.

require 'FormPart.php'; # Renderables primarily used within forms
require 'TablePart.php'; # Renderables primarily used within tables

# Outputs ===========================================================================

# This file  various classes that act as Outputs - places where data from form
# submissions can be sent, stored, or otherwise handled.

require 'Output.php';

# Views =============================================================================

# These files various classes related to views -- that is, ways in which data
# from form submissions can be displayed.

require 'View.php'; # General interfaces/helpers
require 'GeneralTable.php'; # Things shared among TableView/DetailsView/EmailView
require 'TableView.php'; # Table views
require 'GraphView.php'; # Graph views
require 'DetailsView.php'; # The view shown after clicking the "Details" button in a table
require 'EmailView.php'; # Used by the "email-to" output

# FormList =============================================================================

# This file  classes used to generate the main list of forms shown upon entering
# the application.

require 'FormList.php';