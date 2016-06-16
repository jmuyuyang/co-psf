<?php
namespace Client;

class Base extends \Coroutine\Base{

	protected $_clientKey;

	protected $_timerId;

	public function setClientKey($clientKey){
		$this->_clientKey = $clientKey;
	}

	public function getClientKey(){
		return $this->_clientKey;
	}

	public function addTimer($timeout,$callback){
		$this->_timerId = \swoole_timer_after($timeout*1000,$callback);
	}

	public function closeTimer(){
		\swoole_timer_clear($this->_timerId);
	}

	public function next(){
		$coroutine = $this->_coroutine;
		$taskQueue = static::TASK_QUEUE;
		if($coroutine->valid()){
			 \Coroutine::resume($coroutine);
		}else{
			//协程结束自动释放可用链接
			$this->release($this);
		}
	}

	public static function release($client){
		\Pool::release(static::TASK_QUEUE,$client);
		\Coroutine::next(static::TASK_QUEUE);
	}
}

