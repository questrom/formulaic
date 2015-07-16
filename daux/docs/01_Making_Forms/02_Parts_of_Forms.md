
## Parts of forms

The following section describes each of the elements that can be nested within the "fields" element given in the configuration file. Each of these elements makes up a part of the form that is displayed to the user.

Before talking about each of these elements specifically, global attributes must be discussed.

### Global attributes

A few global attributes are shared by most (*not necessarily all*) of the elements that describe form fields:

* `name`

  This attribute assigns a name to a form field. It is used within the HTML "name" attribute of the generated form, and is also used as the key for the form field in MongoDB.

  Note that *no two fields in the same form may have the same name.* Ignoring this instruction will result in undefined (and probably undesired) behavior.

* `label`

  This attribute specifies the label which will go above a form field. All form fields *must* be given labels.

* `sublabel`

  This attribute specifies a piece of smaller text to be rendered in gray below a form field's label. It is useful for providing more detailed instructions to the user.

  In some cases, the sublabel will be generated from other attributes of the form field if it is not explicitly specified. For instance, it may display the minimum and maximum accepted values of a form control.

* `required`

  If provided, this attribute specifies that a value for the field *must* be provided by the user. Note that by default *all fields are optional*.

### Form fields

The following elements represent form fields; they can be put inside the "fields" element of the form's configuration file.

<hr>
#### The `checkbox` element

This element creates a checkbox.

##### Attributes
* `must-check`

  If provided, this attribute specifies that the user cannot submit the form without checking the checkbox.

<hr>
#### The `textbox` and `textarea` elements

These elements create single-line and multi-line text boxes, respectively.

##### Attributes
* `min-length` and `max-length`
  
  These specify the minimum and maximum number of characters that can be entered by the user, respectively.

* `must-match`

  This attribute specifies a regular expression which the text must match.

<hr>
#### The `password` element

This element creates a password input field. Putting this inside a `show-if` block (as described later) will result in unintended behavior (in some cases, the user will be able to submit the form without a password).

##### Attributes
* `match-hash`

  Specifies a salted and hashed password, made with PHP's `password_hash` function, which the entered password must match.

<hr>
#### The `dropdown` and `radios` elements

These elements create dropdown menus and lists of radio buttons, respectively. Both of them allow the user to select an option from a list, though they do so in different ways.

`option` elements nested inside in order to specify the options among which the user can choose. (See the sample forms to get a better idea of how this works.)

<hr>
#### The `checkboxes` element

This element creates a list of checkboxes, from which the user can choose zero or more options. As with `dropdown` and `radios`, child `option` elements are used to specify the list of available options.

##### Attributes
* `min-choices` and `max-choices`

These attributes specify the minimum and maximum nubmer of choices that the user can select.

If you ever set `max-choices` to be 1, you probably want to just use the `radios` element instead.

<hr>
#### The `range` element

This element creates a slider, which allows the user to select a numeric value within a specified range. If greater precision is needed, consider using the `number` element instead.

##### Attributes

* `min` and `max`

  These specify the minimum and maximum values for the slider, respectively.

* `def`

  Specifies the default value for the slider. Note that there is no way for the user to choose not to provide an input to a slider -- in other words, you cannot really have "optional" sliders.

* `step`
  
  Specifies the smallest increment along the slider. For instance, if this is set to 1, only integers can be provided; if set to 2, only even integers are allowed.

<hr>
#### The `phone`, `email`, and `url` elements

These elements provide textboxes in which the user can only enter phone numbers, email addresses, and URLs, respectively.

##### Attributes

* `must-have-domain`

  This attribute, which only applies to the `email` element, specifies a domain which the email address must have. For example, if this is set to `bu.edu`, the entered email must end with `@bu.edu`.

<hr>
#### The `number` element


# UNFINISHED!

<code class="descname">number</code></dt>
<dd><p>Allows the user to input a numeric value.</p>
<dl class="option">
<dt id="cmdoption-arg-min">
<span id="cmdoption-arg-max"></span><code class="descname">min</code><code class="descclassname"></code><code class="descclassname">, </code><code class="descname">max</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-min" title="Permalink to this definition">¶</a></dt>
<dd><p>These specify the minimum and maximum values of the number.</p>
</dd></dl>

</dd></dl>

<dl class="object">
<dt>
<code class="descname">time</code></dt>
<dd><p>Allows the user to specify a time of day.</p>
<dl class="option">
<dt id="cmdoption-arg-min">
<span id="cmdoption-arg-max"></span><code class="descname">min</code><code class="descclassname"></code><code class="descclassname">, </code><code class="descname">max</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-min" title="Permalink to this definition">¶</a></dt>
<dd><p>These specify, in &#8220;h:mm xm&#8221; format, the minimum and maximum times that can be entered.</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-step">
<code class="descname">step</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-step" title="Permalink to this definition">¶</a></dt>
<dd><p>If specified, the time (measured in minutes past midnight) must be a multiple of this number of minutes.</p>
</dd></dl>

</dd></dl>

<dl class="object">
<dt>
<code class="descname">date</code></dt>
<dd><p>Allows the user to specify a date.</p>
<dl class="option">
<dt id="cmdoption-arg-min">
<span id="cmdoption-arg-max"></span><code class="descname">min</code><code class="descclassname"></code><code class="descclassname">, </code><code class="descname">max</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-min" title="Permalink to this definition">¶</a></dt>
<dd><p>These specify, in &#8220;YYYY-MM-DD&#8221; format, the minimum and maximum dates that can be entered.</p>
</dd></dl>

</dd></dl>

<dl class="object">
<dt>
<code class="descname">datetime</code></dt>
<dd><p>Allows the user to specify a date and time.</p>
<dl class="option">
<dt id="cmdoption-arg-min">
<span id="cmdoption-arg-max"></span><code class="descname">min</code><code class="descclassname"></code><code class="descclassname">, </code><code class="descname">max</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-min" title="Permalink to this definition">¶</a></dt>
<dd><p>These specify, in &#8220;m/d/y hh:mm xm&#8221; format, the minimum and maximum values that can be entered.</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-step">
<code class="descname">step</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-step" title="Permalink to this definition">¶</a></dt>
<dd><p>If specified, the time (measured in minutes past midnight) must be a multiple of this number of minutes.</p>
</dd></dl>

</dd></dl>

<dl class="object">
<dt>
<code class="descname">file</code></dt>
<dd><p>Allows the user to upload a file.</p>
<p><cite>allow</cite> elements nested inside of the <cite>file</cite> element specify what types of files are allowed; each <cite>allow</cite> element corresponds to a single file type. The <cite>mime</cite> attribute specifies the MIME type; the <cite>ext</cite> attribute specifies the file extension.</p>
<dl class="option">
<dt id="cmdoption-arg-max-size">
<code class="descname">max-size</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-max-size" title="Permalink to this definition">¶</a></dt>
<dd><p>The maximum file size, in bytes. Note that the file size limit set in <code class="docutils literal"><span class="pre">php.ini</span></code> will override this.</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-permissions">
<code class="descname">permissions</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-permissions" title="Permalink to this definition">¶</a></dt>
<dd><p>The permissions which the file will have in Amazon S3. <code class="docutils literal"><span class="pre">public-read</span></code> is probably what you want.</p>
</dd></dl>

</dd></dl>

<dl class="object">
<dt>
<code class="descname">group</code></dt>
<dd><p>Creates a border around a group of related form fields. Does not affect the data sent to MongoDB (or other sources) in any way.</p>
</dd></dl>

<dl class="object">
<dt>
<code class="descname">captcha</code></dt>
<dd><p>Creates a CAPTCHA, which the user must solve in order for the form to be submitted.</p>
</dd></dl>

<dl class="object">
<dt>
<code class="descname">list</code></dt>
<dd><p>Allows the user to duplicate a set of form fields an arbitrary number of times. For example, to create a list of file uploads with associated captions, one might use:</p>
<div class="highlight-jade"><div class="highlight"><pre>list(name=&#39;pictures&#39;, label=&#39;Pictures to upload&#39;, add-text=&#39;Add a picture&#39;, min-items=&#39;2&#39;)
  file(label=&#39;Image file&#39;, name=&#39;image_file&#39;, max-size=&#39;10000000&#39;, permissions=&#39;public-read&#39;)
    allow(ext=&#39;jpg&#39;, mime=&#39;image/jpeg&#39;)
    allow(ext=&#39;png&#39;, mime=&#39;image/png&#39;)
  textbox(label=&#39;Caption&#39;, name=&#39;caption&#39;, required=true)
</pre></div>
</div>
<p>The associated data will be stored in the database as an array.</p>
<dl class="option">
<dt id="cmdoption-arg-add-text">
<code class="descname">add-text</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-add-text" title="Permalink to this definition">¶</a></dt>
<dd><p>The text displayed on the button which allows the user to add another item to the list.</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-min-items">
<span id="cmdoption-arg-max-items"></span><code class="descname">min-items</code><code class="descclassname"></code><code class="descclassname">, </code><code class="descname">max-items</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-min-items" title="Permalink to this definition">¶</a></dt>
<dd><p>The minimum/maximum number of items which the user can input.</p>
</dd></dl>

</dd></dl>

</div>
<div class="section" id="other-form-elements">
<h3>Other form elements<a class="headerlink" href="#other-form-elements" title="Permalink to this headline">¶</a></h3>
<dl class="object">
<dt>
<code class="descname">header</code></dt>
<dd><p>Creates a header. The text of the header is provided within the element.</p>
<dl class="option">
<dt id="cmdoption-arg-subhead">
<code class="descname">subhead</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-subhead" title="Permalink to this definition">¶</a></dt>
<dd><p>Sub header text to display.</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-icon">
<code class="descname">icon</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-icon" title="Permalink to this definition">¶</a></dt>
<dd><p>An icon to display next to the header. (These icons come from Semantic UI.)</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-size">
<code class="descname">size</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-size" title="Permalink to this definition">¶</a></dt>
<dd><p>The size (1-6) of the header.</p>
</dd></dl>

</dd></dl>

<dl class="object">
<dt>
<code class="descname">notice</code></dt>
<dd><p>Creates a notice offset from the surrounding text and form fields. <code class="docutils literal"><span class="pre">li</span></code> elements within the notice can create list items.</p>
<dl class="option">
<dt id="cmdoption-arg-text">
<code class="descname">text</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-text" title="Permalink to this definition">¶</a></dt>
<dd><p>The text to place in the notice.</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-header">
<code class="descname">header</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-header" title="Permalink to this definition">¶</a></dt>
<dd><p>A header to place at the top of the notice.</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-icon">
<code class="descname">icon</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-icon" title="Permalink to this definition">¶</a></dt>
<dd><p>An icon to display next to the header. (These icons come from Semantic UI.)</p>
</dd></dl>

<dl class="option">
<dt id="cmdoption-arg-size">
<code class="descname">size</code><code class="descclassname"></code><a class="headerlink" href="#cmdoption-arg-size" title="Permalink to this definition">¶</a></dt>
<dd><p>The size (1-6) of the header.</p>
</dd></dl>

</dd></dl>

</div>
<div class="section" id="conditionals">
<h3>Conditionals<a class="headerlink" href="#conditionals" title="Permalink to this headline">¶</a></h3>
<p>The <cite>show-if</cite> element introduces conditional form fields &#8211; form fields that are only shown when a certain condition is met. The first element within a <cite>show-if</cite> must be a condition; the second element specifies the form field. Conditions are as follows:</p>
<dl class="object">
<dt>
<code class="descname">is-checked</code></dt>
<dd><p>This condition is matched if a checkbox (specified in the <code class="docutils literal"><span class="pre">name</span></code> attribute) is checked).</p>
</dd></dl>

<dl class="object">
<dt>
<code class="descname">is-not-checked</code></dt>
<dd><p>This condition is matched if a checkbox (specified in the <code class="docutils literal"><span class="pre">name</span></code> attribute) is <strong>NOT</strong> checked).</p>
</dd></dl>

<dl class="object">
<dt>
<code class="descname">is-radio-selected</code></dt>
<dd><p>This condition is matched if a radio button group (whose name is given by the <code class="docutils literal"><span class="pre">name</span></code> attribute) has a certain value (given by the <code class="docutils literal"><span class="pre">value</span></code> attribute).</p>
</dd></dl>

<p>See the provided example forms for more details on how exactly this works.</p>
</div>
</div>
</div>


          </div>
          <footer>
  
    <div class="rst-footer-buttons" role="navigation" aria-label="footer navigation">
      
      
        <a href="index.html" class="btn btn-neutral" title="formbuilder" accesskey="p"><span class="fa fa-arrow-circle-left"></span> Previous</a>
      
    </div>
  

  <hr/>

  <div role="contentinfo">
    <p>
        &copy; Copyright 2015, Jason Hansel, Questrom School of Business, Boston University.
    </p>
  </div>
  Built with <a href="http://sphinx-doc.org/">Sphinx</a> using a <a href="https://github.com/snide/sphinx_rtd_theme">theme</a> provided by <a href="https://readthedocs.org">Read the Docs</a>.

</footer>

        </div>
      </div>

    </section>

  </div>
  
