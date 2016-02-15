<?php
namespace Db\Mysql;

class Adapter extends \Coroutine\Task{
    
    const RETRY = 3;

    protected $_conn;
    protected $_module;
    protected $_config;

    protected $_isConnected = false;

    public function __construct($module){
        $this->_config = array("host" => "127.0.0.1","port" => "3306","user" => "root","password" => "huanleguang123","dbname" => "hlg","module" => "material");
        $this->_module = $module;
    }

    public function getClientKey(){
        return $this->_module;
    }

    public function connect(){
        if(!$this->_conn){
            $hostKey = $this->_config['host'].":".$this->_config['port'];
            $conn = new \mysqli($hostKey,$this->_config['user'],$this->_config['password'],$this->_config['dbname']);
            if(mysqli_connect_errno()){
                throw new \exception("mysql connect failed: ".mysqli_connect_errno());
            }
            $this->_conn = $conn;
            $this->_isConnected = true;
        }
        return $this->_conn;
    }

    public function reconnect(){
        $this->close();
        return $this->connect();
    }

    public function close(){
        $this->_conn->close();
        $this->_conn = null;
        $this->_isConnected = false;
    }

    public function queryAsync($sql){
        for($i=0;$i<self::RETRY;$i++){
            $r = swoole_mysql_query($this->_conn,$sql,array($this,"sqlOnReady"));
            if($r === false){
                if ($this->_conn->errno == 2013 or $this->_conn->errno == 2006) {
                    if($this->reconnect()){
                        continue;
                    }
                }
            }
            break;
        }
        return $r;
    }

    public function query(){
        for($i=0;$i<self::RETRY;$i++){
            $r = $this->_conn->query($sql);
            if($r === false){
                if ($this->_conn->errno == 2013 or $this->_conn->errno == 2006) {
                    if($this->reconnect()){
                        continue;
                    }
                }
            }
            break;
        }
        return $r;
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
            $error = $db->_error;
            $exception = new \exception($error);
            $this->exceuteCoroutine($r,$exception);
        }else{
            if($r === true){
                if($db->_insert_id){
                    $r = $db->_insert_id;
                }else{
                    $r = $db->_affected_rows;
                }
            }
            $this->executeCoroutine($r,null);
        }
        \Db\Connection::release($this);
        $this->next();
    }
}
