<?php
namespace Coroutine;

class Task{

	protected $_coroutine;

	public function setCoroutine($coroutine){
		$this->_coroutine = $coroutine;
	}

	public function executeCoroutine($resp,$exception = null){
		if($this->_coroutine){
			if($exception){
	            $this->_coroutine->throw($exception);
	        }else{
	            $this->_coroutine->send($resp);
	        }
    	}
	}

	public function next(){
		if($this->_coroutine->valid()){
			\Coroutine::next($this->_coroutine);
		}
	}
}