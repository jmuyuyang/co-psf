<?php
namespace Http;

class Adapter extends \Coroutine\Task{

	protected $_swClient;

	protected $_clientKey;

	protected $_headers;

	public function __construct($host,$port){
		$this->_clientKey = $host.":".$port;
		$this->_swClient = new \swoole_http_client($host,$port);
	}

	public function setHeaders($headers){
		$this->_headers = $headers;
	}

	public function getClientKey(){
		return $this->_clientKey;
	}

	public function get($uri){
		$this->_swClient->setHeaders($this->_headers);
		$this->_swClient->get($uri,array($this,"httpOnReady"));
	}

	public function httpOnReady($swClient){
		$this->executeCoroutine($swClient->body);
		Connection::release($this);
		$this->next();
	}
}