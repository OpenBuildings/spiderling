/**
 * A very simple phantomjs server, that's used for testing.
 * Returns the content of all the requests sent to it.
 * 
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */

var server, server_port;

system = require('system');
server = require('webserver').create();

server_port = parseInt(system.args[1]);

/**
 * Start the echo server
 */
server.listen(server_port, function (request, response)
{
	console.debug('echoing post', request.postRaw);
	response.write(request.postRaw);
	response.close();
});