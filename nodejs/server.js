#!/usr/local/bin/node

var sys=require('sys');
var http=require('http');
var fs=require('fs');
var path=require('path');
var parseUrl=require('url').parse;
var chat=require('./handler');
var ChatHandler=chat.ChatHandler;

chat.setupStorage();

var basePath=path.join(__dirname, '..');
var ctypes={
	'.css': 'text/css',
	'.js': 'text/javascript'
};

function serveFile(filepath, response, contentType) {
	fs.readFile(filepath, function(err, data) {
		if(err) {
			response.writeHead(500);
			response.end(err);
		}
		else {
			response.writeHead(200, {'Content-Type': contentType || 'text/html'});
			response.end(data);
		}
	});
}

http.createServer(function (request, response) {
	var url=parseUrl(request.url);
	
	if(url.pathname=='/'  || url.pathname=='/index.html') {
		serveFile(path.join(basePath, 'index.html'), response);
	}
	else if(new RegExp('^/shared/').test(url.pathname)) {
		serveFile(path.join(basePath, 'shared', path.basename(url.pathname)), response, ctypes[path.extname(url.pathname)]);
	}
	else if(url.pathname=='/favicon.ico') {
		response.writeHead(404);
	}
	else {
		var handler=new ChatHandler(request, response);
		handler.dispatch();
	}
}).listen(8124);
