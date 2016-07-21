<?php
namespace Client;

class Mysql extends Base{
    
    const RETRY = 3;

    const TASK_QUEUE = "Mysql";

    protected $_conn;
    protected $_module;
    protected $_config;

    protected $_isConnected = false;

    public static function factory($module){
        return new self($module);
    }

    public static function new($module){
        $taskData = [
            "client_key" => $module,
            "init_data" => array(
                $module
            )
        ];
        $client = yield self::TASK_QUEUE => $taskData;
        if (! $client->isConnected()) {
        	$client = yield $client->connect();
        }
        return $client;
    }

    public function __construct($module){
        $this->_config = \Config::load("db",$module);
        $this->_module = $module;
    }

    public function connect(){
        if(!$this->_conn || !$this->_isConnected){
            $this->_conn = new \swoole_mysql();
            $this->_conn->connect($this->_config,array($this,"onConnect"));
        }
        return $this;
    }
    
    public function onConnect($conn,$r){
        if($r === false){
            if($conn->connect_error == 2013 || $conn->connect_error == 2006){
                $this->reconnect();
                return;
            }
            $exception = new \Exception($conn->connect_error);
            $this->executeCoroutine(null,$exception);
        }
        $this->_conn = $conn;
        $this->_isConnected = true;
        $this->executeCoroutine($this);
    }

    public function reconnect(){
        $this->close();
        return $this->connect();
    }

    public function close(){
        if($this->_isConnected){
            $this->_conn->close();
        }
        $this->_conn = null;
        $this->_isConnected = false;
    }
    
    public function query($sql){
        $this->_conn->query($sql,array($this,"sqlOnReady"));
        return $this;
    }

    public function module(){
        return $this->_module;
    }

    public function db(){
        return $this->_conn;
    }

    public function isConnected(){
        return $this->_isConnected;
    }

    public function sqlOnReady($db,$r){
        if($r === false){
            if($db->errno == 2013 || $db->errno == 2006){
                $this->close();
            }
            $exception = new \Exception($db->error,$db->errno);
            $this->executeCoroutine(null,$exception);
        }else{
            if($r === true){
                if($db->insert_id){
                    $r = $db->insert_id;
                }else{
                    $r = $db->affected_rows;
                }
            }
            $this->executeCoroutine($r,null);
        }
        $this->next();
    }
}
