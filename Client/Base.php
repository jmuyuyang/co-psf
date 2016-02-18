<?php
namespace Client;

class Base extends \Coroutine\Base{

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

