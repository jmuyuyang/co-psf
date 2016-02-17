<?php
namespace Client;

class Http extends Base{

	protected $_swClient;

	protected $_clientKey;

	protected $_headers;

	const TASK_QUEUE = "HTTP";

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
		\Http\Connection::release($this);
		$this->next();
	}
}