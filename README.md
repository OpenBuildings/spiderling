# Spiderling

[![Build Status](https://travis-ci.org/OpenBuildings/spiderling.png?branch=master)](https://travis-ci.org/OpenBuildings/spiderling)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/OpenBuildings/spiderling/badges/quality-score.png?s=1ceec2d9ed5edf22e8187bf4df41d8758aecdd54)](https://scrutinizer-ci.com/g/OpenBuildings/spiderling/)
[![Code Coverage](https://scrutinizer-ci.com/g/OpenBuildings/spiderling/badges/coverage.png?s=f056fa81f6a4f1fde71505682083b8f75b42d9c0)](https://scrutinizer-ci.com/g/OpenBuildings/spiderling/)
[![Latest Stable Version](https://poser.pugx.org/openbuildings/spiderling/v/stable.png)](https://packagist.org/packages/openbuildings/spiderling)

This is a library for crawling web pages with curl and PhantomJS. Heavily inspired by [Capybara](https://github.com/jnicklas/capybara). It's a major component in [phpunit-spiderling](https://github.com/OpenBuildings/phpunit-spiderling) for integration level testing. It can handle AJAX requests easily and allows switching from fast PHP-only drivers to JavaScript-enabled like PhantomJS easily, without modifying the code.

## A quick example

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com');

$li = $page->find('ul.nav > li.test');

echo $li->text();

$page
  ->fill_in('Name', 'New Name')
  ->fill_in('Description', 'some description')
  ->click_button('Submit');
```

This will output the text content of the HTML node ``li.test`` fill in some inputs and submit the form.

## The DSL

The Page object has a rich DSL for accessing content and filling in forms:

### Navigation

- ``visit($page, $query)``: Direct the browser to open a new URL
- ``current_url()``: Retrieve the current URL - this will be affected by redirects or scripts changing the browser URL in any way.
- ``current_path()``: This will return the URL without the protocol and host part, usefull for writing more generic code.

### Getters

Each node represents a HTML tag on the page, and you can use extensive getter methods to probe its contents. All of these getters are dynamic, meaning that there is no cache involved and each methods sends a call to its appropriate driver.

- ``is_root()``: Check if the current element is the root "node"
- ``id()``: Get the 'id' of the current node - this ID uniquely identifies the node for the current page.
- ``html()``: Get the raw html of the current node - this is like calling outerHTML on a dom element.
- ``tag_name()``: Get the tag name of the dom element. e.g. DIV, SPAN, FORM, SELECT
- ``attribute($name)``: Get an attribute of the current tag. If the tag is empty e.g. ``<div disabled />`` then it will return an empty string. If there is no attribute however, NULL will be returned
- ``text()``: Get the text content of an html tag - this is similar to how browsers render HTML tags, all whitespace will be merged to single spaces.
- ``is_visible()``: Check if a node is visible. PhantomJS driver will return correct value if the item is hidden via JS, CSS or inline styles.
- ``is_selected()``: Check if an option tag is "selected"
- ``is_checked()``: Check if an input tag is "checked"
- ``value()``: Get the value of an input form tag

An example of using some of these getters, if we have this page:

```html
<html>
  <body>
    <ul>
      <li class="first"><span>LABEL</span> This is the first link</li>
      <li class="some class"><span>LABEL</span> Go <a href="http://example.com">somewhere</a></li>
    </ul>
  </body>
</html>
```

Then you could write the following PHP code:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-page');

$li = $page->find('ul > li.first');

// Will output "LI"
echo $li->tag_name();

// Will output "first"
echo $li->attribute('class');

// Will output "LABEL This is the first link"
echo $li->text();

// Will output "<li class="first"><span>LABEL</span> This is the first link</li>"
echo $li->html();
```

### Setters

Spiderling also gives you the ability to modify the current page, filling in input fields, pressing buttons and links, submitting forms. This can be accomplished with the low level setters:

- ``set($value)``: If the node is a representation of an input field (input, textarea or even a select), you can use this method to set its value.
- ``append($text)``: If you need to append some text to a textfield or input, you can use the ``append()`` method instead of ``set()`` - this allows performing the operation with fewer round trips to the driver.
- ``click()``: If the node represents something that you can click (like a link or a button) you can use the ``click()`` method on that node. It will perform the required operation as if a person clicked it, loading the new page and updating the result of current_url / current_path getters.
- ``select_option()``: If the node is an option tag, than you can use this method to select it. This will unselect any other selected options in the SELECT tag, unless its marked as "multiple"
- ``unselect_option()``: The opposite of ``select_option()``
- ``hover($x = NULL, $y = NULL)``: hover the mouse over the current node, triggering the javascript / css events and states. You can optionally pass x and y offsets so you can fine-tune this.
- ``drop_files($files)``: This will trigger all the JavaScript events associated with dropping files on top of a dom element (HTML5 feature).

So having an example form like this:

```html
<html>
  <body>
    <form action="/submit" method="post">
      <div class="row">
        <label for="text-input">Name:</label>
        <input type="text" id="text-input" name="name" />
      </div>
      <div class="row">
        <label for="description-input">Description:</label>
        <textarea name="description" id="description-input" cols="30" rows="10">
          Some text
        </textarea>
      </div>
      <input type="submit" value="Submit"/>
    </form>
  </body>
</html>
```

We could do this script:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-form');

$page
  ->find('#text-input')
    ->set('New Name');

$page
  ->find('#description-input')
    ->append(' with some additions');

$page
  ->find('input[type="submit"]')
    ->click();

// This will return the submitted action of the form, e.g. http://example.com/submit
echo $page->current_url();
```

### Locators

You can find elements not only by CSS selectors (which is the default) but also input elements, buttons and links have special finders. This is referred to as "locator type".

- css - the default
- xpath - using XPath
- link - find links by id, title, text inside the link or even alt text of an image inside the link.
- field - find input elements (TEXTAREA, INPUT, SELECT) by id, name, text of the label, pointing to this input, placeholder of the input or the text of the option of a select tag, that does not have a value (usually the default option)
- label - find a label tag by id, title, content text or image alt text inside the label
- button - find a button by id, title, name, value, text inside the button or alt text of the image inside the button

All of these locator types give you the ability to easily scan the page and select something you are looking for to click or fill without looking at the html of the page at all. Everywhere there is a selector you can enter an ``array('{locator type}', '{selector}')`` to change the default locator type.

Here's an example using the previous HTML:

```html
<html>
  <body>
    <form action="/submit" method="post">
      <div class="row">
        <label for="text-input">Name:</label>
        <input type="text" id="text-input" name="name" />
      </div>
      <div class="row">
        <label for="description-input">Description:</label>
        <textarea name="description" id="description-input" cols="30" rows="10">
          Some text
        </textarea>
      </div>
      <input type="submit" value="Submit"/>
    </form>
  </body>
</html>
```
The php code becomes clearer and less brittle - the underlying html can change but your code will still work as expected:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-form');

$page
  ->find(array('field', 'Name'))
    ->set('New Name');

$page
  ->find(array('field', 'Description'))
    ->set('some description');

$page
  ->find(array('button', 'Submit'))
    ->click();

// This will return the submitted action of the form, e.g. http://example.com/submit
echo $page->current_url();
```

### Filters

If using only locators is not enough, you can easily narrow down the search with "filters". They iterate over the found candidates, filtering out ones that don't match. Be careful with them because they load the nodes and check them one-by-one which might be performance intensive, but it is OK in most cases.

Here are the available filters:

- __visible__: TRUE or FALSE - filter out visible or not visible nodes
- __value__: string of value - filter out nodes that don't have a matching value
- __text__: string of text - filter out nodes that don't have the given text
- __attributes__: array of attribute name => attribute value - filter out nodes that don't have all of the given attributes (names and values)
- __at__: specifically select which node of the list to return, all other are filtered out.

Here is how you might use the filters with this HTML:

```html
<html>
  <body>
    <ul>
      <li class="one">Row One</li>
      <li class="two">Row Two</li>
      <li style="display:none">Row Three</li>
    </ul>
    <select name="test">
      <option value="test">Option 1</option>
      <option value="test 2">Option 2</option>
    </select>
  </body>
</html>
```

```php

use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-filters-page');

// Will output "Row One"
echo $page->find('li', array('text' => 'One'))->text();

// Will output "Row Three"
echo $page->find('li', array('visible' => FALSE))->text();

// Will output "Option 2"
echo $page->find('option', array('value' => 'test 2'))->text();
```

### Finders

Most locator types have a custom method for finding an element with that particular type. There are also some other custom finders which you might find useful:

- ``find($selector, array $filters = array())`` - default, uses CSS selectors
- ``find_field($selector, array $filters = array())`` - uses 'field' locator type to find input elements
- ``find_link($selector, array $filters = array())`` - uses 'link' locator type to find anchor tags
- ``find_button($selector, array $filters = array())`` - uses 'button' locator type
- ``not_present($selector, array $filters = array())`` - the opposite of "find" makes sure an element is not present on the page
- ``all($selector, array $filters = array())`` - returns a Nodelist - an iteratable and countable array-like object that you can 'foreach' and 'count' easily. Have in mind that it features lazy loading, so only when you access a node it gets loaded by the driver. ``count()`` does not load any nodes at all.

The previous form example can be rewritten like this:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-form');

$page
  ->find_field('Name')
    ->set('New Name');

$page
  ->find_field('Description')
    ->set('some description');

$page
  ->find_button('Submit')
    ->click();

// This will return the submitted action of the form, e.g. http://example.com/submit
echo $page->current_url();
```

### Actions

Some often used actions that you can perform on the page - modifying inputs, clicking links and buttons, etc. have shortcut methods, to make your code more readable and robust.

Here are all these actions:

- ``click_on($selector, array $filters = array())``: Find a node using a CSS selector and click on it
- ``click_link($selector, array $filters = array())``: Find a node using the "link" locator type and click on it
- ``click_button($selector, array $filters = array())``: Find a node using the "button" locator type and click on it
- ``fill_in($selector, $with, array $filters = array())``: Find a node using the "field" locator type and set its value with "$with".
- ``choose($selector, array $filters = array())``: Choose a specific radio input tag, finding it with the "field" locator type.
- ``check($selector, array $filters = array())``: Find a checkbox input tag using the "field" locator and "check" it.
- ``uncheck($selector, array $filters = array())``: Find a checkbox input tag using the "field" locator and "uncheck" it.
- ``attach_file($selector, $file, array $filters = array())``: Find a file input tag using the "field" locator and set $file to it.
- ``select($select, $option_filters, array $filters = array())``: Find a select tag using the "field" locator, and mark one or more of its options as "selected". If $option_filters is a string then the option with the value of the string will be set, otherwise all the options matching the filters will be set. This allows selecting by value, text or even position.
- ``unselect($select, $option_filters, array $filters = array())``: The same as "select" but the matched options are "unselected"
- ``hover_on($select, array $filters = array())``: Move the mouse over an element, found by css selector
- ``hover_link($select, array $filters = array())``: Move the mouse over an element, found by the link locator type
- ``hover_field($select, array $filters = array())``: Move the mouse over an element, found by the "field" locator type
- ``hover_button($select, array $filters = array())``: Move the mouse over an element, found by the "button" locator type


Using these methods you can make your code very readable. Also all of these actions return ``$this``, allowing you to chain them easily. Consider the previous example in the __Finders__ section - you can rewrite it like this:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-form');

$page
  ->fill_in('Name', 'New Name')
  ->fill_in('Description', 'some description')
  ->click_button('Submit');

// This will return the submited action of the form, e.g. http://example.com/submit
echo $page->current_url();
```

A more complicated example is in order. We will be using the following HTML:

```html
<html>
  <body>
    <form action="/submit" method="post">
      <div class="row">
        <label for="text-input">Name:</label>
        <input type="text" id="text-input" name="name" />
      </div>
      <div class="row">
        <label>Features:</label>
        <ul>
          <li>
            <input type="checkbox" id="feature-input-1" name="feature_1" checked />
            <label for="feature-input-1">Feature One</label>
          </li>
          <li>
            <input type="checkbox" id="feature-input-2" name="feature_2" />
            <label for="feature-input-2">Feature Two</label>
          </li>
        </ul>
      </div>
      <div class="row">
        <label>State:</label>
        <ul>
          <li>
            <input type="radio" id="state-input-1" name="state" checked />
            <label for="state-input-1">Open</label>
          </li>
          <li>
            <input type="radio" id="state-input-2" name="state" />
            <label for="state-input-2">Closed</label>
          </li>
        </ul>
      </div>
      <div class="row">
        <label for="type-input">Type:</label>
        <select name="type" id="type-input">
          <option>Select an Option</option>
          <option value="big">Type Big</option>
          <option value="small">Type Small</option>
        </select>
      </div>
      <div class="row">
        <label for="text-input">Name:</label>
        <input type="text" id="text-input" name="name" />
      </div>
      <div class="row">
        <label for="description-input">Description:</label>
        <textarea name="description" id="description-input" cols="30" rows="10">
          Some text
        </textarea>
      </div>
      <button>
        <img src="/img/submit-form.png" alt="Submit" />
      </button>
    </form>
  </body>
</html>
```

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-big-form');

$page
  ->fill_in('Name', 'New Name')
  ->uncheck('Feature One')
  ->check('Feature Two')
  ->choose('Closed')
  ->select('Type', array('text' => 'Type Small'))
  ->fill_in('Description', 'some description')
  ->click_button('Submit');

// This will return the submited action of the form,
// e.g. http://example.com/submit
echo $page->current_url();
```

### Nesting

When there are multiple elements on the page you might want to be more specific, Spiderling allows you to do this by nesting the nodes - you can call all the actions and finders from "within" a node - so that finders will search only in the children on the node.

For example:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-big-form');

$page
  ->fill_in('Name', 'New Name')
  ->find('.row', array('text' => 'Type'))
    ->choose('Closed')
  ->end()
  ->click_button('Submit');
```

Notice the "end()" method - this allows you to return to the previous level and continue your work from there. Also you can nest multiple times without any problem (you will have to use "end()" multiple times too to "get out of" the nesting)

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-big-form');

$page
  ->fill_in('Name', 'New Name')
  ->find('.row', array('text' => 'Type'))
    ->find('ul')
      ->choose('Closed')
    ->end()
  ->end()
  ->click_button('Submit');
```

### Misc

There are some more additional methods as part of the DSL:

- ``confirm($confirm)``: If an alert, or confirm dialogs is open on the page you can use this method to dismiss it (by providing FALSE) or approve it (for confirm dialogs, providing TRUE)
- ``execute($script, $callback = NULL)``: Perform an arbitrary JavaScript on the page, in the context of a given node. You will be able to access it as the first argument of the callback, e.g. ``arguments[0]``. The result of the JavaScript execution will be returned by the method (by passing through JSON serialization). Optionally you could provide a callback, and the result will be the first argument of the callback (the secound will be the node itself)
- ``screenshot($file)``: Take a screenshot of the current state of the page, placing it in the $file as a PNG image.

## Handling AJAX

Spiderling follows the same philosophy as Capybara in that it does not explicitly support or wait for AJAX calls to finish, however each finder does not immidiately conclude failure if the element is not loaded, but waits a bit (default 2 seconds) before throwing an exception. To take advantage of that when writing your crawlers when you have an AJAX request you need to search for the change the AJAX is about to do:

For example:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->visit('http://example.com/the-big-form');

$page
  ->click_button('Edit')
  // This will wait for the appearance of the "edit" form, loaded via AJAX
  ->find('h1', array('text' => 'Edit Form'))
    // Enter a new name inside the form
    ->fill_in('Name', 'New Name');
    ->click_button('Save')
  ->end();
  // We wait a bit to make sure the form is closed,
  // also as it might take longer than normal,
  // we extend the wait time from 2 to 4 seconds.
$page
  ->next_wait_time(4000)
  ->find('.notification', array('text' => 'Saved Successfully'));
```

## Drivers

A great strength of Spiderling is the ability to use different drivers for your code. This allows switching from PHP-only curl parsing of the page to a PhantomJS without modification of the code. For example if we wanted to use a PhantomJS driver instead of the default "Simple" one then we'd need to do this:

```php
use Openbuildings\Spiderling\Page;

$page = new Page(new Driver_Phantomjs);

$page->visit('http://example.com/the-big-form');

$page
  ->fill_in('Name', 'New Name')
  ->find('.row', array('text' => 'Type'))
    ->choose('Closed')
  ->end()
  ->click_button('Submit');
```

There are 4 drivers at present:

- __Driver_Simple__: Uses PHP curl to load pages. Does not support JavaScript or browser alert dialogs
- __Driver_Kohana__: Uses Kohana framework's native Internal Request class, without opening internet connections at all - very performant if your code already uses Kohana framework.
- __Driver_Phantomjs__: Start a PhantomJS server. You would need to have PhantomJS installed and accessible in your PATH. Picks a new port at random so its possible to have multiple PhantomJS browsers open simultaneously.

You can easily write your own Drivers by extending the Driver class and implementing methods yourself. Some drivers do not support all the features, so it's OK to not implement every method.

Now for each driver in detail:

### Driver_Simple

Loads the HTML page with curl and then parses it using PHP's native DOM and XPath. All finders are quite fast, so it's your best bet to use this if you do not rely on JavaScript or other browser specific features. It's also very easy to extend in order to make a "native" version for a specific web framework - the only thing you need to implement is the loading part, an example of which you can see with the "Driver_Kohana" class.

Before each request $_GET, $_POST and $_FILES are saved, filled in with appropriate values and later restored, mimicking a real PHP request.

Apart from loading the HTML through curl, you could set the content directly, if you've loaded it by other means.

Here's how that looks:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$big_form_content = file_get_contents('big_content.html');

$page->content($big_form_content);

$page
  ->fill_in('Name', 'New Name')
  ->find('.row', array('text' => 'Type'))
    ->choose('Closed')
  ->end()
  ->click_button('Submit');
```

Generally performing post requests yourself is discouraged as they are not supported by all the drivers. But with Driver_Simple you can perform arbitrary requests, for testing API calls for example.
This is accomplished directly through the driver like this:

```php
use Openbuildings\Spiderling\Page;

$page = new Page();

$page->driver()->post('http://example.com/api/endpoint', array(), array('name' => 'some post value'));
```

### Driver_Kohana

Uses Kohana framework's native Internal Request (slightly modifying it to trick the framework into thinking its an initial request). It extends __Driver_Simple__.

Also it handles redirects capping them to maximum 8 (configurable) and uses Request::$user_agent as its User Agent.

Example Use
```php
use Openbuildings\Spiderling\Page;

$page = new Page(new Driver_Kohana);
```

### Driver_Phantomjs

Using this driver you can perform all the finds and actions with PhantomJS, using a real WebKit engine with JavaScript, without the need for any graphical environment (headless). You need to have it installed in your PATH, accessaible by invoking "phantomjs".

You can download it from here: http://phantomjs.org/download.html

By default it spawns a new server on a random port from 4445 and 5000.

This should work if you have PhantomJS installed.

```php
use Openbuildings\Spiderling\Page;

$page = new Page(new Driver_Phantomjs);
```

If you want to start the server from independently, you can modify the PhantomJS connection, you can also set it up to output messages to a log file as well as have, tweak other parameters.

```php
use Openbuildings\Spiderling\Page;

$connection = new Driver_Phantomjs_Connection;
$connection->port(5500);
$connection->start('pid_file', 'log_file');

$driver = new Driver_Phantomjs($connection);

$page = new Page();
```

Setting the "pid file" argument on start, allows the driver to save the pid of the phantomjs server process to that file, and then try to clean up the server when started again, thus making sure you don't have running PhantomJS process all over the place.

## License

Copyright (c) 2012-2013, OpenBuildings Ltd. Developed by Ivan Kerin as part of [clippings.com](http://clippings.com)

Under BSD-3-Clause license, read LICENSE file.
