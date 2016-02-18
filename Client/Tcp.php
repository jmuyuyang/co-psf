<?php
namespace Client;

class Tcp extends Base{

	public $host;

	public $port;

	public $timeout;

	protected $_client;

	protected $_status;

	const TASK_QUEUE = "TCP";
	
	const CONNECT_WAIT = "wait";

	const CONNECT_SUCCESS = "success";

	const CONNECT_FAIL = "fail";

	public function __construct($host,$port,$timeout = 0.5){
		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;
		$this->_client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
		$this->_status = self::CONNECT_WAIT;
		$this->connect();
	}

	public function send($data){
		if($this->_status !== self::CONNECT_SUCCESS){
			throw new \Exception("tcp not connect success");
		}
		$this->_client->send($data);
		return $this;
	}

	public function read(){
		return $this;
	}

	public function connect(){
		$this->_client->on("error",array($this,"onError"));
		$this->_client->on("connect",array($this,"onConnect"));
		$this->_client->on("receive",array($this,"onReceive"));
		$this->_client->on("close",function(){

		});
		$this->_client->connect($this->host,$this->port,$this->timeout);
		return $this;
	}

	public function onError($cli){

	}

	public function onReceive($cli,$data){
		$this->executeCoroutine($data);
	}

	public function onConnect($cli){
		if($this->_status == self::CONNECT_WAIT){
			$this->_status = self::CONNECT_SUCCESS;
			$this->executeCoroutine($this);
		}
	}

	public function executeCoroutine($resp = null,$exception = null){
		parent::executeCoroutine($resp,$exception);
		$this->next();
	}
}