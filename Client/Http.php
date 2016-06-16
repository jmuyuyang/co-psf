<?php
namespace Client;

class Http extends Base{

	protected $_swClient;

	protected $_headers;

	protected $_host;

	protected $_port;

	protected $_timeout;

	const TASK_QUEUE = "Http";

	public static function factory($host,$port = 80,$timeout = 10){
        return new self($host,$port,$timeout);
    }

    public static function new($host,$port = 80,$timeout = 10){
        $taskData = ["client_key" => $host.":".$port,"init_data" => array($host,$port,$timeout)];
        $client = yield self::TASK_QUEUE => $taskData;
        return $client;
    }

	public function __construct($host,$port,$timeout){
		$this->_host = $host;
		$this->_port = $port;
		$this->_timeout = $timeout;
	}

	public function setHeaders($headers){
		$this->_headers = $headers;
	}

	public function request($method,$uri,$data = array()){
		$request = array(
			"method" => $method,
			"uri" => $uri,
			"data" => $data
		);
		if($this->_swClient){
			$this->_request($request);
		}else{
			$self = $this;
			\swoole_async_dns_lookup($this->_host,function($host,$ip) use($self,$request){
				$self->_swClient = new \swoole_http_client($ip,$self->_port);
				$self->_request($request);
			});
		}
		return $this;
	}

	protected function _request($request){
		if($this->_timeout){
			$this->addTimer($this->_timeout,array($this,"onTimeout"));
		}
		$this->_headers['host'] = $this->_host;
		$this->_swClient->setHeaders($this->_headers);
		switch($request['method']){
			case "GET":
				$this->_swClient->get($request['uri'],array($this,"httpOnReady"));
				break;
			case "POST":
				$this->_swClient->post($request['uri'],$request['data'],array($this,"httpOnReady"));
				break;
			case "PUT":
				$this->_swClient->setData($request['data']);
				$this->_swClient->setMethod("PUT");
				$this->_swClient->execute($uri,array($this,"httpOnReady"));
				break;
		}
	}

	public function onTimeout(){
		$this->_swClient->close();
		$e = new \Exception("connect to http server:".$this->_host.":".$this->_port." timeout");
		$this->closeTimer();
		$this->executeCoroutine($e,null);
		$this->next();
	}

	public function httpOnReady($swClient){
		$response = array(
			"code" => $swClient->statusCode,
			"headers" => $swClient->headers,
			"body" => $swClient->body
		);
		$this->executeCoroutine($response);
		$this->next();
	}
}