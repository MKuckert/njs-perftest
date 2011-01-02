<?php

/**
 * ChatServer
 * 
 * PHP implementation of the chat server
 * 
 * @author Martin Kuckert
 * @license Licensed under the MIT license.
 */
class ChatServer {
	
	const DEFAULT_MAX_MESSAGES=20;
	const DEFAULT_TIMEOUT=10;
	const DEFAULT_SLEEP_TIMEOUT=200000;
	const DEFAULT_CACHENAME='chat_messages';
	const CACHE_ZEND=1;
	const CACHE_APC=2;
	
	/**
	 * @var integer Max number of messages to store
	 */
	private $_maxMessages=self::DEFAULT_MAX_MESSAGES;
	
	/**
	 * @var integer Timeout for long polling requests
	 */
	private $_timeout=self::DEFAULT_TIMEOUT;
	
	/**
	 * @var integer Timeout for sleeping during polling requests.
	 */
	private $_sleepTimeout=self::DEFAULT_SLEEP_TIMEOUT;
	
	/**
	 * @var string The cache name.
	 */
	private $_cachename=self::DEFAULT_CACHENAME;
	
	/**
	 * @var integer The cache method to use
	 */
	private $_cachetype;
	
	/**
	 * Returns the max number of messages to store.
	 * 
	 * @return integer
	 */
	public function getMaxMessages() {
		return $this->_maxMessages;
	}
	
	/**
	 * Sets the max number of messages to store.
	 * 
	 * @return ChatServer
	 * @param integer
	 */
	public function setMaxMessages($value) {
		$this->_maxMessages=(int)$value;
		return $this;
	}
	
	/**
	 * Returns the timeout for long polling requests.
	 * 
	 * @return integer
	 */
	public function getTimeout() {
		return $this->_timeout;
	}
	
	/**
	 * Sets the timeout for long polling requests.
	 * 
	 * @return ChatServer
	 * @param integer
	 */
	public function setTimeout($value) {
		$this->_timeout=(int)$value;
		return $this;
	}
	
	/**
	 * Returns the timeout for sleeping during polling requests.
	 * 
	 * @return integer
	 */
	public function getSleepTimeout() {
		return $this->_sleepTimeout;
	}
	
	/**
	 * Sets the timeout for sleeping during polling requests.
	 * 
	 * @return ChatServer
	 * @param integer
	 */
	public function setSleepTimeout($value) {
		$this->_sleepTimeout=(int)$value;
		return $this;
	}
	
	/**
	 * Returns the cache name.
	 * 
	 * @return string
	 */
	public function getCachename() {
		return $this->_cachename;
	}
	
	/**
	 * Sets the cache name.
	 * 
	 * @return ChatServer
	 * @param string
	 */
	public function setCachename($value) {
		$this->_cachename=(string)$value;
		return $this;
	}
	
	/**
	 * Returns the cache method to use.
	 * 
	 * @return integer
	 */
	public function getCachetype() {
		return $this->_cachetype;
	}
	
	/**
	 * Sets the cache method to use.
	 * 
	 * @return ChatServer
	 * @param integer
	 */
	public function setCachetype($value) {
		$this->_cachetype=(int)$value;
		return $this;
	}
	
	/**
	 * Constructor
	 */
	public function __construct() {
		if(function_exists('zend_shm_cache_fetch')) {
			$this->setCachetype(self::CACHE_ZEND);
		}
		else if(function_exists('apc_fetch')) {
			$this->setCachetype(self::CACHE_APC);
		}
		else {
			throw new RuntimeException('ChatServer requires the zend shm or apc extensin');
		}
	}
	
	/**
	 * Clears the chat cache
	 * 
	 * @return ChatServer
	 */
	public function clearCache() {
		$name=$this->getCachename();
		switch($this->getCachetype()) {
			case self::CACHE_ZEND:
				zend_shm_cache_delete($name);
				break;
			case self::CACHE_APC:
				apc_delete($name);
				break;
		}
		return $this;
	}
	
	/**
	 * Returns the entire chat cache
	 * 
	 * @return array
	 */
	public function getAllMessages() {
		$name=$this->getCachename();
		switch($this->getCachetype()) {
			case self::CACHE_ZEND:
				$retval=zend_shm_cache_fetch($name);
				break;
			case self::CACHE_APC:
				$retval=apc_fetch($name);
				break;
		}
		
		if(!is_array($retval)) {
			$retval=array();
		}
		
		return $retval;
	}
	
	/**
	 * Sets the entire chat cache
	 * 
	 * @return void
	 * @param array
	 */
	protected function setAllMessages(array $messages) {
		$name=$this->getCachename();
		switch($this->getCachetype()) {
			case self::CACHE_ZEND:
				zend_shm_cache_store($name, $messages);
				break;
			case self::CACHE_APC:
				apc_store($name, $messages);
				break;
		}
	}
	
	/**
	 * Posts a new chat message.
	 * 
	 * @return array The new message
	 * @param string The chat message
	 * @param string The username to use
	 */
	public function postMessage($message, $username) {
		$messages=$this->getAllMessages();
		$message=array(
			'm' => $message,
			'u' => $username,
			't'	=> sha1(microtime(true))
		);
		$messages[]=$message;
		
		$maxMessages=$this->getMaxMessages();
		if(count($messages)>$maxMessages) {
			array_shift($messages);
		}
		
		$this->setAllMessages($messages);
		
		return $message;
	}
	
	/**
	 * Checks for new chat messages since the given token.
	 * 
	 * @return array or NULL
	 * @param string Token of the last new message
	 */
	public function getNewMessages($token) {
		$messages=$this->getAllMessages();
		$c=count($messages);
		
		if($c<=0 or $messages[$c-1]['t']==$token) {
			return NULL;
		}
		
		$newMessages=array();
		for($i=$c-1; $i>=0; $i--) {
			if($messages[$i]['t']==$token) {
				break;
			}
			array_unshift($newMessages, $messages[$i]);
		}
		
		return $newMessages;
	}
	
	/**
	 * Polls for new messages and returns with new messages or with an empty
	 * array after the timeout.
	 * 
	 * @return array
	 * @param string Token of the last new message
	 */
	public function pollNewMessages($token) {
		$timeout=$this->getTimeout();
		$start=microtime(true);
		$newMessages=array();
		
		set_time_limit($timeout+3);
		while($start+$timeout>microtime(true)) {
			$tmp=$this->getNewMessages($token);
			if($tmp!==NULL) {
				$newMessages=$tmp;
				break;
			}
			
			usleep($this->getSleepTimeout());
		}
		
		return $newMessages;
	}
	
}
