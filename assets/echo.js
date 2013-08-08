/**
 * A very simple phantomjs server, that's used for testing.
 * Returns the content of all the requests sent to it.
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