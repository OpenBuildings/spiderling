
var server, server_port;

system = require('system');

server_port = parseInt(system.args[1]);

server = require('webserver').create();

/**
 * Start the echo server
 */
server.listen(server_port, function (request, response)
{
	console.debug('echoing post', request.postRaw);
	response.write(request.postRaw);
	response.close();
});