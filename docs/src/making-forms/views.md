---
title: Views
template: page.html
nav_groups:
  - primary
nav_sort: 4
---

The elements described on this page are used to create "views" &mdash; pages on which the user can easily view the data submitted through the form. To add a view, place a `table-view` or `graph-view` element (as described below) into the `views` section of a [configuration file](introduction.html).

Views can be accessed through the main page of the app, in the links placed below each form.

## Table Views

<h3 class="ui header top attached">
The `table-view` element
</h3><div class="ui bottom attached segment">
This element creates a table view: a view which shows data as an (optionally paginated) table, along with a "Download CSV" button. Each `table-view` element **must contain one or more `col` elements.**

##### Attributes:
* **`name`**

  Specifies the name of the view, to be used in URLs. No two views for the same form can have the same name.

* **`title`**

  Specifies the title of the view, which the user will see.

* **`per-page`**

  If provided, this attribute specifies the number of items that are shown on a page. If not provided, the table will not be paginated.
</div>

<h3 class="ui header top attached">
The `col` element
</h3><div class="ui bottom attached segment">
This element creates a column within a table view. Each `table-view` element must contain one or more columns.

##### Attributes:

* **`name`**

  The name of the form field whose associated data will be displayed in the column.

* **`header`**

  The header at the top of the column.

* **`width`**

  The *relative* width of the column.

  For instance, if there are five columns with `width=1`, each column will take up a fifth of the available space; if one column has `width=2` and another has `width=1`, the former will take up twice as much space as the latter.

* **`sort`**

  If provided, the table will be sorted by the value of this column.

  If the attribute is set to `asc`, the sort is ascending; otherwise, it is descending.
</div>

## Graph Views

<h3 class="ui header top attached">
The `graph-view` element
</h3><div class="ui bottom attached segment">
This element creates a graph view: a view that displays data as a series of one or more graphs. Each `graph-view` element **must contain one or more `bar` or `pie` elements.**

##### Attributes:
* **`name`**

  Specifies the name of the view, to be used in URLs. No two views for the same form can have the same name.

* **`title`**

  Specifies the title of the view, which the user will see.
</div>

<h3 class="ui header top attached">
The `bar` and `pie` elements
</h3><div class="ui bottom attached segment">
These elements, which must be placed within a `graph-view` element, create bar graphs and pie charts, respectively.

##### Attributes:

* **`name`**

  The name of the form field whose associated data will be displayed in the graph.

* **`label`**

  The header text that will be displayed above the graph.
</div>