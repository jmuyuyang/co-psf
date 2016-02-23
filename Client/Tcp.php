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

	const CONNECT_SLEEP = "sleep";

	public function __construct($host,$port,$timeout = 0.5){
		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;
		$this->_client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
		$this->_status = self::CONNECT_WAIT;
	}

	public function send($data){
		if($this->_status !== self::CONNECT_SUCCESS && $this->_status !== self::CONNECT_SLEEP){
			throw new \Exception("tcp not connect success");
		}
		$this->_client->send($data);
		return $this;
	}

	public function read(){
		$this->wakeup();
		return $this;
	}

	public function connect(){
		if(!$this->_client->isConnected()){
			$this->_client->on("error",array($this,"onError"));
			$this->_client->on("connect",array($this,"onConnect"));
			$this->_client->on("receive",array($this,"onReceive"));
			$this->_client->on("close",array($this,"onClose"));
			$this->_client->connect($this->host,$this->port,$this->timeout);
			return $this;
		}
	}

	public function close(){
		if($this->_client->isConnected()){
			if($this->_status == self::CONNECT_SLEEP){
				//如果当前client在沉睡中,先唤醒
				$this->wakeup();
			}
			$this->_client->close();
		}
		$this->_status = self::CONNECT_WAIT;
	}

	public function wakeup(){
		if($this->_status == self::CONNECT_SLEEP){
			$this->_client->wakeup();
			$this->_status = self::CONNECT_SUCCESS;
		}
	}

	public function sleep(){
		if($this->_status == self::CONNECT_SUCCESS){
			$this->_client->sleep();
			$this->_status = self::CONNECT_SLEEP;
		}
	}

	public function onConnect($cli){
		if($this->_status == self::CONNECT_WAIT){
			$this->_status = self::CONNECT_SUCCESS;
			$this->executeCoroutine($this);
		}
	}

	public function onError($cli){
		$exception = new \Exception("tcp connect failed");
		$this->executeCoroutine(null,$exception);
	}

	public function onReceive($cli,$data){
		if($this->_status == self::CONNECT_SUCCESS){
			$this->executeCoroutine($data);
		}
	}

	public function onClose($cli){
		$this->_status = self::CONNECT_WAIT;
	}

	public function executeCoroutine($resp = null,$exception = null){
		parent::executeCoroutine($resp,$exception);
		$this->next();
	}

	public function next(){
		if($this->_coroutine->valid()){
			$coValue = $this->_coroutine->current();
			if(! $coValue instanceof \Client\Tcp){
				\Coroutine::resume($this->_coroutine);
				$this->sleep(); //当前协程不再监听读事件,则移除读事件
			}
		}
	}
}