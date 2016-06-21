<?php
class Pool{

	protected static $_pools = array();

	public static function init($adapter,$maxPoolSize = 0){
		self::$_pools[$adapter] = new \Connection($adapter,$maxPoolSize);
	}

	public static function get($adapter,$initData){
		if(!isset(self::$_pools[$adapter])){
			self::init($adapter);
		}
		return self::$_pools[$adapter]->get($initData);
	}

	/**
	 * 释放单个链接进连接池
	 * @param unknown $adapter
	 * @param unknown $connection
	 */
	public static function release($adapter,$connection){
		if(isset(self::$_pools[$adapter])){
			self::$_pools[$adapter]->put($connection);
		}
	}
	
	/**
	 * 释放连接池中所有的链接
	 */
	public static function releasePool($adapter){
	    if(isset(self::$_pools[$adapter])){
	        foreach(self::$_pools[$adapter] as $connection){
	            $connection->close();
	        }
	        unset(self::$_pools[$adapter]);
	    }
	}
}