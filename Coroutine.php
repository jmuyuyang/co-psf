<?php
class Coroutine{

    const DB = "DB";

    const HTTP = "HTTP";
    
    public static function wrap($coroutine){
        if($coroutine instanceof \Generator){
            $coKey = $coroutine->key();
            $coValue = $coroutine->current();
            if($coKey){
                try{
                    $connectionClass = "\\" . $coKey . "\Connection";
                    $client = $connectionClass::get($coValue);
                    if($client){
                        $client->setCoroutine($coroutine);
                        $coroutine->send($client);
                    }else{
                        Queue::push($coKey,$coroutine);
                    }
                }catch(exception $e){
                    $coroutine->throw($e);
                }
            }else{
                if($coValue instanceof \Coroutine\Task){
                    $coValue->setCoroutine($coroutine);
                }
            }
        }
        return $coroutine;
    }
    
    public static function next($coroutine){
        $coKey = $coroutine->key();
        if($coKey){
            if(!Queue::isEmpty($coKey)){
                //如果不为空则将当前协程入栈
                Queue::push($coKey,$coroutine);
                $coroutine = Queue::pop($coKey);
            }
        }
        self::wrap($coroutine);
    }

    public static function wait(){
    	swoole_event_wait();
    }

    public static function exit(){
    	swoole_event_exit();
    }
}
?>