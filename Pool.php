<?php
class Pool{

	protected static $_pools = array();

	public static function init($adapter,$maxPoolSize = 1){
		self::$_pools[$adapter] = new \Connection($adapter,$maxPoolSize);
	}

	public static function get($adapter,$initData){
		if(!isset(self::$_pools[$adapter])){
			self::init($adapter);
		}
		return self::$_pools[$adapter]->get($initData);
	}

	public static function release($adapter,$connection){
		if(isset(self::$_pools[$adapter])){
			self::$_pools[$adapter]->put($connection);
		}
	}
}