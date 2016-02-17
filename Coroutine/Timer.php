<?php
namespace Coroutine;

class Timer extends Base{

	protected $_timerId;

	public function after($millisecond){
		$this->_timerId = swoole_timer_after($millisecond,array($this,"timerCallback"));
		return $this;
	}

	public function timerCallback(){
		$this->executeCoroutine();
		$this->next();
		swoole_timer_clear($this->_timerId);
	}
}