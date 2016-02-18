<?php
namespace Coroutine;

abstract class Base{

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

	public function unsetCoroutine(){
		$this->_coroutine = null;
	}

	public function next(){
		$coroutine = $this->_coroutine;
		if($coroutine->valid()){
			\Coroutine::resume($coroutine);
		}
	}
}

