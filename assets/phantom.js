/**
 * Start a phantomjs server for handling Spiderling Phantomjs Driver requests
 * 
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
var server, page, system, errors, messages, server_port;

page = require('webpage').create();

system = require('system');

server_port = parseInt(system.args[1] || '4445', 10);

console.log('Starting server on port ' + server_port);

/**
 * Viewport size with large height so we do not need to scroll around for click events
 */
page.viewportSize = { width: 1024, height: 4000 };

/**
 * Save javascript errors to be returned later
 */
page.onError = function (msg, trace) {
	errors.push({
		errorMessage: msg,
		sourceName: trace[0].file,
		lineNumber: trace[0].line
	});
};

/**
 * Save resource loading errors to be returned later
 */
page.onResourceError = function(resourceError) {
	errors.push({
		errorMessage: 'Error loading resource',
		sourceName: resourceError.url,
		lineNumber: resourceError.errorString
	});
};

/**
 * Save console messages to be returned later
 */
page.onConsoleMessage = function (msg) {
	messages.push(msg);
};

/**
 * Check if the PhantomjsConnection has been loaded for the given page and inject it if not
 * The file is added as a second argument to this script
 */
page.initConnection = function(){
	if (page.evaluate(function(){ return (typeof PhantomjsConnection === 'undefined');})) {
		page.injectJs(system.args[2]);
	}
};

server = require('webserver').create();
/**
 * Start the webserver on port 
 */
server.listen(server_port, function (request, response)
{
	var connect;

	/**
	 * Send the response, finishing the request
	 * @param  {mixed} value send a value with the response
	 */
	response.send = function (value) {
		response.write(JSON.stringify(value));
		response.close();
	};

	/**
	 * Execute a callback if the method and url match
	 * @param  {string}   method   http method
	 * @param  {string}   url      url request
	 * @param  {callback} callback the callback to be executed, receives matched url paramters from the regex
	 * @return {response}            this
	 */
	request.connect = function (method, url, callback) {
		if (this.method == method && this.url.match(url)) {
			console.log('  Executing command ', this.method, this.url, this.post ? this.post.value : '');
			callback.apply(page, this.url.match(url).slice(1));
			this.connected = true;
		}
		return this;
	};

	console.log('Received command ', request.method, request.url, request.post ? request.post.value : '');

	response.statusCode = 200;

	request

		/**
		 * Get the current page settings
		 * 
		 * @example GET /settings
		 * @return {object} settings object
		 */
		.connect('GET', /^\/settings$/, function () {
			response.send(page.settings);
		})

		/**
		 * Get full page sourcecode
		 * 
		 * @example GET /source
		 * @return {string} html source
		 */
		.connect('GET', /^\/source$/, function () {
			response.send(page.content);
		})


		/**
		 * Check if phantomjs has started
		 * 
		 * @example GET /session
		 */
		.connect('GET', /^\/session$/, function () {
			response.send(true);
		})

		/**
		 * Close the session by exitint this script
		 * 
		 * @example DELETE /session
		 */
		.connect('DELETE', /^\/session$/, function () {
			response.send();
			phantom.exit();
		})

		/**
		 * Get all the stored errors sofar
		 * 
		 * @example GET /errors
		 * @return {array} errors array
		 */
		.connect('GET', /^\/errors$/, function () {
			response.send(errors);
		})

		/**
		 * Get all console messages stored sofar
		 * 
		 * @example GET /messages
		 * @return {array} messages array
		 */
		.connect('GET', /^\/messages$/, function () {
			response.send(messages);
		})

		/**
		 * Add a cookie, the post must contain name, value, domain. Optionals are path, httponly, secure, expires
		 * 
		 * @example POST /cookie name=autologin&value=123123&domain=localhost
		 */
		.connect('POST', /^\/cookie$/, function () {
			page.addCookie(request.post);
			response.send();
		})

		/**
		 * Delete all cookies
		 * 
		 * @example DELETE /cookies
		 */
		.connect('DELETE', /^\/cookies$/, function () {
			phantom.clearCookies();
			response.send();
		})

		/**
		 * Get all cookies
		 * 
		 * @example GET /cookies
		 */
		.connect('GET', /^\/cookies$/, function () {
			response.send(phantom.cookies);
		})

		/**
		 * Post a screenshot of the current page to the given file address (as a post value)
		 * 
		 * @example POST /screenshot value=/path/to/file
		 * @value {string} filename
		 */
		.connect('POST', /^\/screenshot$/, function () {
			page.viewportSize = { width: 1024, height: 800 };
			page.render(request.post.value);
			page.viewportSize = { width: 1024, height: 4000 };
			response.send();
		})

		/**
		 * Get the current url
		 * 
		 * @example GET /url
		 * @return {string} url
		 */
		.connect('GET', /^\/url$/, function () {
			response.send(page.url);
		})

		/**
		 * Open the requested in the post value url
		 * errors and messages arrays are emptied
		 * 
		 * @example POST /url value=http://google.com
		 * @value {string} url
		 */
		.connect('POST', /^\/url$/, function () {
			errors = [];
			messages = [];

			page.open(request.post.value, function(status) {
				console.log(request.post.value, status);
				response.send();
			});
		})

		/**
		 * Execute arbitrary javascript
		 * Optionally post an id of the element to pass to the method scope as "item"
		 * 
		 * @example POST /execute value=console.debug("test")&id=10
		 * @value {string} url
		 */
		.connect('POST', /^\/execute$/, function () {
			page.initConnection();
			response.send(page.evaluate(function (callback_string, id) {
				var callback = new Function(callback_string );
				return callback(PhantomjsConnection.item(id));
			}, request.post.value, request.post.id));
		})

		/**
		 * Get all the elements with a given xpath (sent via post value), 
		 * 
		 * @example POST /elements value=.//h1/span
		 * @value  {xpath} xpath filter for elements
		 * @return {array} element ids array
		 */
		.connect('POST', /^\/elements$/, function () {
			page.initConnection();
			response.send(page.evaluate(function (xpath) {
				return PhantomjsConnection.ids(xpath);
			}, request.post.value));
		})

		/**
		 * Get all the elements, matching the xpath (sent via post value) of a specific element
		 * 
		 * @example POST /elements/1/elements value=.//li
		 * @param  {integer} id element id
		 * @value  {xpath}   xpath filter for elements
		 * @return {array}    elements array
		 */
		.connect('POST', /^\/element\/(\d+)\/elements$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (xpath, parent) {
				return PhantomjsConnection.ids(xpath, parent);
			}, request.post.value, id));
		})

		/**
		 * Get the tag name of a given element
		 * 
		 * @example
		 * @param  {integer} id element id
		 * @return {string}    lower case tag name
		 */
		.connect('GET', /^\/element\/(\d+)\/name$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id) {
				return PhantomjsConnection.item(id).tagName.toLowerCase();
			}, id));
		})

		/**
		 * Get an attribute from a given element, specify the attribute by name
		 * 
		 * @example GET /elements/1/attribute/value
		 * @param  {integer} id   element id
		 * @param  {string} name attribute name
		 * @return {string}      content of the attribute
		 */
		.connect('GET', /^\/element\/(\d+)\/attribute\/([a-zA-Z0-9]+)$/, function (id, name) {
			page.initConnection();
			response.send(page.evaluate(function (id, name) {
				return PhantomjsConnection.item(id).getAttribute(name);
			}, id, name));
		})

		/**
		 * Get the html of a given element
		 * 
		 * @example GET /elements/1/html
		 * @param  {integer} id element id
		 * @return {string}    element outer html
		 */
		.connect('GET', /^\/element\/(\d+)\/html$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id) {
				return PhantomjsConnection.item(id).outerHTML;
			}, id));
		})

		/**
		 * Get the text representation of a given element
		 * 
		 * @example GET /elements/1/text
		 * @param  {integer} id element id
		 * @return {string}    text content
		 */
		.connect('GET', /^\/element\/(\d+)\/text$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id) {
				return PhantomjsConnection.item(id).textContent;
			}, id));
		})

		/**
		 * Get the value of a given form element
		 * 
		 * @example GET /elements/1/value
		 * @param  {integer} id element id
		 * @return {string}    element value
		 */
		.connect('GET', /^\/element\/(\d+)\/value$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id) {
				return PhantomjsConnection.value(id);
			}, id));
		})

		/**
		 * Change the value of given form element, by providing a post value
		 * 
		 * @example POST /elements/1/value value=newtext
		 * @param  {integer} id element id
		 * @return {string}   the changed value
		 */
		.connect('POST', /^\/element\/(\d+)\/value/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id, value) {
				return PhantomjsConnection.setValue(id, value);
			}, id, request.post.value));
		})

		/**
		 * Upload a file on a given file input element
		 * 
		 * @example POST /elements/1/upload value=/path/to/file
		 * @param  {integer} id element id
		 */
		.connect('POST', /^\/element\/(\d+)\/upload/, function (id) {
			page.initConnection();
			var selector = page.evaluate(function(id){
				return PhantomjsConnection.uniqueSelector(id);
			}, id);
			page.uploadFile(selector, request.post.value);

			response.send();
		})

		/**
		 * Check if an element is visible
		 * 
		 * @example GET /elements/1/visible
		 * @param  {integer} id element id
		 * @return {boolean}    is visible?
		 */
		.connect('GET', /^\/element\/(\d+)\/visible$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id) {
				return PhantomjsConnection.isVisible(id);
			}, id));
		})

		/**
		 * Click on a given element. 
		 * This calculates the position of the element, and then sends a native click event to that coordinates.
		 * So make sure there are no elements on top of this one
		 * 
		 * @example POST /elements/1/click
		 * @param  {integer} id element id
		 */
		.connect('POST', /\/element\/(\d+)\/click$/, function (id) {
			page.initConnection();
			var rect = page.evaluate(function(id){
				return PhantomjsConnection.item(id).getBoundingClientRect();
			}, id);

			page.sendEvent('click', rect.left + rect.width / 2, rect.top + rect.height / 2);
			response.send();
		})

		/**
		 * Check if a given option element is selected
		 * 
		 * @example GET /elements/1/selected
		 * @param  {integer} id option element id
		 * @return {boolean}    is selected?
		 */
		.connect('GET', /^\/element\/(\d+)\/selected$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id) {
				return PhantomjsConnection.item(id).selected;
			}, id));
		})

		/**
		 * Check if a given input element is checked
		 * 
		 * @example GET /elements/1/checked
		 * @param  {integer} id input element id
		 * @return {boolean}    is checked?
		 */
		.connect('GET', /^\/element\/(\d+)\/checked$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id) {
				return PhantomjsConnection.item(id).checked;
			}, id));
		})

		/**
		 * Change the status of a option element (selected or not selected). The new value is past as post value.
		 * 
		 * @example POST /elements/1/selected value=1
		 * @param  {integer} id option element id
		 */
		.connect('POST', /^\/element\/(\d+)\/selected$/, function (id) {
			page.initConnection();
			response.send(page.evaluate(function (id, value) {
				return PhantomjsConnection.setSelected(id, value);
			}, id, request.post.value));
		});

		if ( ! request.connected) {
			console.log('  Request ' + request.method + ' ' + request.url + ' not recognized by server');
			response.statusCode = 404;
			response.send();
		}
});