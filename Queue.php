<?php
class Queue{

	protected static $_queues = array();

	public static function init($queueName){
		self::$_queues[$queueName] = new \SplQueue();
	}

	public static function push($queueName,$object){
		if(!isset(self::$_queues[$queueName])){
			self::init($queueName);
		}
		self::$_queues[$queueName]->push($object);
	}

	public static function pop($queueName){
		if(isset(self::$_queues[$queueName])){
			if(!self::$_queues[$queueName]->isEmpty()){
				return self::$_queues[$queueName]->pop();
			}
		}
		return null;
	}

	public static function isEmpty($queueName){
		if(isset(self::$_queues[$queueName])){
			return self::$_queues[$queueName]->isEmpty();
		}
		return false;
	}
}