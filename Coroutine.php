<?php
class Coroutine{

    const DB = "DB";

    const HTTP = "HTTP";

    protected static $_ioQueue;

    protected static $_maxTaskId = 0;

    protected static $_coroutines = array();

    public static function init(){
        self::$_ioQueue = array(self::DB,self::HTTP);
    }

    public static function task($coroutine){
        self::wrap(self::_wrap($coroutine));
    }

    public static function wrap($coroutine){
        if($coroutine instanceof \Generator){
            $coKey = $coroutine->key();
            $coValue = $coroutine->current();
            if($coKey && in_array($coKey,self::$_ioQueue)){
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
                if($coValue instanceof \Coroutine\Base){
                    $coValue->setCoroutine($coroutine);
                }
            }
        }
        return $coroutine;
    }

    protected static function _wrap($coroutine){
        $taskId = self::newTaskId();
        $coroutine = (function($taskId,$coroutine){
            $resp = yield from $coroutine;
            \Coroutine::unregister($taskId);
        })($taskId,$coroutine);
        self::register($taskId,$coroutine);
        return $coroutine;
    }

    public static function newTaskId(){
        return ++self::$_maxTaskId;
    }

    public static function register($taskId,$coroutine){
        self::$_coroutines[$taskId] = $coroutine;
    }

    public static function unregister($taskId){
        unset(self::$_coroutines[$taskId]);
        if(!self::$_coroutines){
            self::exit();
        }
    }
    
    public static function resume($coroutine){
        $wrapCurrent = true;
        $coKey = $coroutine->key();
        if($coKey && in_array($coKey,self::$_ioQueue)){
            //io中断,执行队列不为空则将当前协程入栈
            if(!Queue::isEmpty($coKey)){
                Queue::push($coKey,$coroutine);
                $wrapCurrent = false;
            }
        }
        if($wrapCurrent){
            //是否立即执行当前协程
            self::wrap($coroutine);
        }
        return $coKey;
    }

    public static function next($queueName){
        $wrapCoroutine = Queue::pop($queueName);
        if($wrapCoroutine){
            self::wrap($wrapCoroutine);
        }
    }

    public static function wait(){
    	swoole_event_wait();
    }

    public static function exit(){
    	swoole_event_exit();
    }
}
?>