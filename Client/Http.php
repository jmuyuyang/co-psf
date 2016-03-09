<?php
namespace Client;

class Http extends Base{

	protected $_swClient;

	protected $_headers;

	const TASK_QUEUE = "HTTP";

	public static function factory($host,$port){
        return new self($host,$port);
    }

    public static function new($host,$port){
        $taskData = ["client_key" => $host.":".$port,"init_data" => array($host,$port)];
        $client = yield self::TASK_QUEUE => $taskData;
        return $client;
    }

	public function __construct($host,$port){
		$this->_swClient = new \swoole_http_client($host,$port);
	}

	public function setHeaders($headers){
		$this->_headers = $headers;
	}

	public function get($uri){
		$this->_swClient->setHeaders($this->_headers);
		$this->_swClient->get($uri,array($this,"httpOnReady"));
	}

	public function httpOnReady($swClient){
		$this->executeCoroutine($swClient->body);
		$this->next();
	}
}