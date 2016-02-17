<?php
namespace Coroutine;

class Timer extends Task{

	protected $_timerId;

	const TASK_QUEUE = "timer";

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