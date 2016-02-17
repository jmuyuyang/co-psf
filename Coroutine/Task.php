<?php
namespace Coroutine;

abstract class Task{

	protected $_coroutine;

	public function setCoroutine($coroutine){
		$this->_coroutine = $coroutine;
	}

	public function executeCoroutine($resp = null,$exception = null){
		if($this->_coroutine){
			if($exception){
	            $this->_coroutine->throw($exception);
	        }else{
	        	if($resp){
	        		$this->_coroutine->send($resp);
	        	}else{
	        		$this->_coroutine->next();
	        	}
	        }
    	}
	}

	public function next(){
		$coroutine = $this->_coroutine;
		$taskQueue = static::TASK_QUEUE;
		if($coroutine->valid()){
			$taskQueue = \Coroutine::resume($coroutine);
		}
		if($taskQueue !== static::TASK_QUEUE){
            //执行当前任务队列
            \Coroutine::next(static::TASK_QUEUE);
        }
	}
}

