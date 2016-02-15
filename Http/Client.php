<?php
namespace Http;

class Client{
	public static function get($host,$uri){
		$client = yield \Coroutine::HTTP => array("host" => $host,"port" => 80);
		$resp = yield $client->get($uri);
		return $resp;
	}
}