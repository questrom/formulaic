---
title: Route Flowcharts
template: page.html
nav_groups:
  - primary
nav_sort: 3
---

This page describes the basic, general steps involved in handling each route. Note that these charts will make a lot more sense after spending some time reading the rest of this documentation, as well as the code itself.

Note that this does not include information about all possible query string parameters.

## GET `/`

<div class="ui small steps">
  <div class="step">
    <i class="info icon"></i>
    <div class="content">
      <div class="title">Get info about each form</div>
      <div class="description">`Parser->getFormInfo`</div>
    </div>
  </div>
  <div class="step">
    <i class="list layout icon"></i>
    <div class="content">
      <div class="title">Build a FormList</div>
      <div class="description">`new FormList`</div>
    </div>
  </div>
  <div class="step">
    <i class="code outline icon"></i>
    <div class="content">
      <div class="title">Write HTML</div>
      <div class="description">`writeResponse`</div>
    </div>
  </div>
</div>

This route gets the main list of forms.

## GET `/view`

<div class="ui small steps">
  <div class="step">
    <i class="file text outline icon"></i>
    <div class="content">
      <div class="title">Parse config. file</div>
      <div class="description">`Parser->parseJade`</div>
    </div>
  </div>
  <div class="step">
    <i class="unhide icon"></i>
    <div class="content">
      <div class="title">Get the view</div>
      <div class="description">`Page->getView`</div>
    </div>
  </div>
  <div class="step">
    <i class="database icon"></i>
    <div class="content">
      <div class="title">Query Mongo</div>
      <div class="description">`View->query`</div>
    </div>
  </div>
  <div class="step">
    <i class="code outline icon"></i>
    <div class="content">
      <div class="title">Write HTML</div>
      <div class="description">`writeResponse`</div>
    </div>
  </div>
</div>

This route displays a specific view of a specific form. The configuration file to use is specified in `$_GET['form']`; the view to display is specified in `$_GET['view']`.

## GET `/forms/[:formID]`

<div class="ui small steps">
  <div class="step">
    <i class="folder icon"></i>
    <div class="content">
      <div class="title">Check cache</div>
      <div class="description">`Gregwar\Cache`</div>
    </div>
  </div>
  <div class="step">
    <i class="file text outline icon"></i>
    <div class="content">
      <div class="title">Parse config. file</div>
      <div class="description">`Parser->parseJade`</div>
    </div>
  </div>
  <div class="step">
    <i class="sitemap icon"></i>
    <div class="content">
      <div class="title">Build the form</div>
      <div class="description">`->makeFormPart`</div>
    </div>
  </div>
  <div class="step">
    <i class="code outline icon"></i>
    <div class="content">
      <div class="title">Write HTML</div>
      <div class="description">`writeResponse`</div>
    </div>
  </div>
</div>

This route displays a particular form. The configuration file to use is specified in the URL.

## POST `/submit`

<div class="ui small steps">
  <div class="step">
    <i class="file text outline icon"></i>
    <div class="content">
      <div class="title">Parse config. file</div>
      <div class="description">`Parser->parseJade`</div>
    </div>
  </div>
  <div class="step">
    <i class="flag icon"></i>
    <div class="content">
      <div class="title">Get errors/data</div>
      <div class="description">`->getSubmissionPart`</div>
    </div>
  </div>
  <div class="step">
    <i class="save icon"></i>
    <div class="content">
      <div class="title">Run outputs</div>
      <div class="description">`->run`</div>
    </div>
  </div>
  <div class="step">
    <i class="send icon"></i>
    <div class="content">
      <div class="title">JSON response</div>
      <div class="description">`ifError` / `ifOk`</div>
    </div>
  </div>
</div>

This route submits a form, returning either validation errors or a confirmation of success.

## GET `/details`

<div class="ui small steps">
  <div class="step">
    <i class="file text outline icon"></i>
    <div class="content">
      <div class="title">Parse config. file</div>
      <div class="description">`Parser->parseJade`</div>
    </div>
  </div>
  <div class="step">
    <i class="unhide icon"></i>
    <div class="content">
      <div class="title">Make the view</div>
      <div class="description">`new DetailsView`</div>
    </div>
  </div>
  <div class="step">
    <i class="database icon"></i>
    <div class="content">
      <div class="title">Query Mongo</div>
      <div class="description">`View->query`</div>
    </div>
  </div>
  <div class="step">
    <i class="code outline icon"></i>
    <div class="content">
      <div class="title">Write HTML</div>
      <div class="description">`writeResponse`</div>
    </div>
  </div>
</div>


This route is used when the Details button in a table view is pressed.

## GET `/csv`

<div class="ui small steps">
  <div class="step">
    <i class="file text outline icon"></i>
    <div class="content">
      <div class="title">Parse config. file</div>
      <div class="description">`Parser->parseJade`</div>
    </div>
  </div>
  <div class="step">
    <i class="unhide icon"></i>
    <div class="content">
      <div class="title">Make the view</div>
      <div class="description">`new CSVView`</div>
    </div>
  </div>
  <div class="step">
    <i class="database icon"></i>
    <div class="content">
      <div class="title">Query Mongo</div>
      <div class="description">`View->query`</div>
    </div>
  </div>
  <div class="step">
    <i class="code outline icon"></i>
    <div class="content">
      <div class="title">Write HTML</div>
      <div class="description">`writeResponse`</div>
    </div>
  </div>
</div>

This route is used when the Download CSV button in a table view is pressed.