/**
 * Copyright 2011, Martin Kuckert
 * Licensed under the MIT license.
 */
/**
 * ChatClient
 * 
 * @constructor
 * @param Element Node to write chat messages into them
 * @param integer Timeout für Pollinganfragen
 * @param string Pfad zum Chatserver
 */
var ChatClient=function(el, timeout, path) {
	var currentRequest=null;
	this.messages=[];
	this.username='';
	var chatlogEl=$(el)[0]
	var self=this;
	
	/**
	 * Writes a log item.
	 * 
	 * @return void
	 * @param string Log messages
	 * @param string The class for the log message to use
	 */
	this.write=function(msg, cls) {
		var listitem;
		if(msg instanceof Element) {
			listitem=msg;
		}
		else {
			listitem=document.createElement('li');
			listitem.appendChild(document.createTextNode(msg));
		}
		
		listitem.className=cls;
		
		chatlogEl.appendChild(listitem);
		chatlogEl.scrollTop=chatlogEl.scrollHeight;
	};
	
	/**
	 * Writes a chat message.
	 * 
	 * @return void
	 * @param Object The chat message.
	 */
	this.writeItem=function(msg) {
		var item=document.createElement('li');
		
		var username=document.createElement('span');
		username.className='username';
		username.appendChild(document.createTextNode(msg.u));
		item.appendChild(username);
		
		var message=document.createElement('span');
		message.className='message';
		message.appendChild(document.createTextNode(msg.m));
		item.appendChild(message);
		
		self.write(item, 'entry');
	};
	
	/**
	 * Listener for the error event of the data loading.
	 * 
	 * @return void
	 * @param XMLHttpRequest The http request object
	 * @param string Reason for the error event
	 */
	function onDataLoadError(xhr, reason) {
		self.write('polling failed: '+reason, 'error');
		if(reason!='error') {
			dispatchCall();
		}
	};
	
	/**
	 * Listener for the success event of the data loading.
	 * 
	 * @return void
	 * @param Object The response data
	 */
	function onDataLoaded(data) {
		for(var i=0; i<data.length; i++) {
			self.messages.push(data[i]);
			self.writeItem(data[i]);
		}
		dispatchCall();
	};
	
	/**
	 * Dispatches a new message polling.
	 * 
	 * @return void
	 */
	function dispatchCall() {
		var t=null;
		if(self.messages.length) {
			t=self.messages[self.messages.length-1].t;
		}
		currentRequest=$.ajax({
			url: path,
			type: 'POST',
			data: {t: t},
			timeout: timeout,
			error: onDataLoadError,
			success: onDataLoaded
		});
	};
	
	/**
	 * Listener for the error event of the message posting.
	 * 
	 * @return void
	 * @param XMLHttpRequest The http request object
	 * @param string Reason for the error event
	 */
	function onPostMessageError(xhr, reason) {
		self.write('Unable to post message: '+reason);
	};
	
	/**
	 * Listener for the success event of the message posting.
	 * 
	 * @return void
	 */
	function onPostMessage() {
		$('#message').removeClass('activity').removeAttr('disabled').focus();
	};
	
	/**
	 * Posts a chat message.
	 * 
	 * @return void
	 * @param string The message to post
	 */
	function postMessage(msg) {
		$.ajax({
			url: path,
			type: 'POST',
			data: {m: msg, u: self.username},
			error: onPostMessageError,
			success: onPostMessage
		});
	};
	
	/**
	 * Listener for the keypress event of the chat input element.
	 * 
	 * @return void
	 * @param Event
	 */
	function onMessageInput(event) {
		var el=$(event.currentTarget);
		if(event.which==10 || event.which==13) {
			postMessage(el.attr('value'));
			el.attr('value', '').blur().addClass('activity').attr('disabled', 'disabled');
		}
	};
	
	/**
	 * Stops the current request.
	 * 
	 * @return void
	 */
	this.stopPolling=function() {
		currentRequest.abort();
	};
	
	/**
	 * Starts a new request.
	 * 
	 * @return void
	 */
	this.startPolling=function() {
		dispatchCall();
	};
	
	/**
	 * Binds the onMessageInput event listener to the given input element.
	 * 
	 * @return void
	 * @param Element
	 */
	this.bindMessageInput=function(el) {
		$(el).keypress(onMessageInput);
	};
};
