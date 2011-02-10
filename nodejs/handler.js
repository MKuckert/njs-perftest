#!/usr/local/bin/node

var querystring=require('querystring');

exports.setupStorage=function() {
	exports.storage=[];
};

exports.timeout=13000;

exports.ChatHandler=function(request, response) {
	var self=this;
	var postMessage=function(message, user) {
		console.log('new message from '+user+': '+message);
		exports.storage.push({
			m: message,
			u: user,
			t: new Date().getTime()
		});
	};
	var pollNewMessages=function(token, startTimestamp) {
		var c=exports.storage.length;
		var newMessages=[];
		for(var i=c-1; i>=0; i--) {
			if(exports.storage[i].t==token) {
				break;
			}
			newMessages.unshift(exports.storage[i]);
		}
		
		if(newMessages.length>0 || startTimestamp+exports.timeout<new Date().getTime()) {
			response.writeHead(200, {
				'Content-Type': 'application/json'
			});
			response.write(JSON.stringify(newMessages));
			response.end();
			return;
		}
		
		setTimeout(function() {
			pollNewMessages(token, startTimestamp);
		}, 200);
	};
	var onRequestData=function(data) {
		var post=querystring.parse(data);
		
		if(post.m!=undefined && post.u!=undefined) {
			postMessage(post.m, post.u);
			response.writeHead(200);
			response.end();
			return;
		}
		
		if(!post.t) {
			response.writeHead(400, 'Bad Request');
			response.end();
			return;
		}
		
		pollNewMessages(post.t, new Date().getTime());
	};
	this.dispatch=function() {
		request.setEncoding('utf8');
		request.on('data', onRequestData);
	};
};
