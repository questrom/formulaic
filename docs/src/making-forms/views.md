---
title: Views
template: page.html
nav_groups:
  - primary
nav_sort: 4
---
## Views

The `views` element specifies ways in which the data from the form can be viewed. Currently, there are two types of views: tables (optionally paginated) and graphs (many of which can be shown on a single page).

Tables are specified using the `table-view` element, while pages of graphs are specified using the `graph-view` element. These elements have the following attributes:

* `name` specifies the name of the view, to be used primarily in URLs.
* `title` specifies the title of the view, which the user will see.
* For table views, `per-page` specifies the number of items that will be shown on each page. If absent, no pagination will occur.

### More about table views

The `table-view` element must contain at least one `col` element, which specifies a column within the table. The `col` element has the following attributes

* `name` specifies the name of the form field whose data will be displayed in the table.
* `header` specifies the header at the top of the column.
* `width` specifies the relative width of the column.

  For instance, if there are five columns with `width=1`, each column will take up a fifth of the available space; if one column has `width=2` and another has `width=1`, the former will take up twice as much space as the latter.

* If the `sort` attribute is provided, the table will be sorted by the value of the column. If the attribute is set to `asc`, the sort is ascending; otherwise, it is descending.

### More about graph views

The `graph-view` element contains `bar` and `pie` elements, which represent bar and pie charts, respectively. Both `bar` and `pie` elements have the following attributes:

* `name` specifies the name of the graph, which is used internally and must (like other names) be unique.
* `label` specifies the label text that will go above the graph.