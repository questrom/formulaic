formbuilder
=====

Formbuilder is a system for creating forms declaratively. Based on a syntactically simple configuration file, it produces a webform and validates, stores, and displays the resulting submissions.

The program itself is written in PHP, using a few libraries but no large framework. Most of the logic is performed on the **server** side for the sake of performance and simplicity.

The form configuration files are written using Jade; see :doc:`creating-forms` to learn how to write these files. Several sample forms are provided with the project.


Contents:

.. toctree::
   :maxdepth: 2

   creating-forms
