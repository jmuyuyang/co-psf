<?php
namespace Http;

class Connection{
	
	const MAX_POOL_SIZE = 5;

	protected static $_connectionPools = array();

	public static function _initConnection($host,$port){
		$key = $host.":".$port;
		if(!isset(self::$_connectionPools[$key])){
			self::$_connectionPools[$key] = new \SplQueue();
		}
		if(!self::$_connectionPools[$key]->isEmpty()){
			return self::$_connectionPools[$key]->pop();
		}else{
			return new \Client\Http($host,$port);
		}
	}

	public static function get($hostArr){
		$client = self::_initConnection($hostArr['host'],$hostArr['port']);
		return $client;
	}

	public static function release($client){
		self::$_connectionPools[$client->getClientKey()]->push($client);
	}
}