<?php
/**
 * connection管理类
 * @author yuyang
 *
 */

class Connection
{
    private static $_connnectionPools = array();

    protected $_adapter;

    protected $_maxPoolSize;

    public function __construct($adapter,$maxPoolSize = 0){
    	$this->_adapter = $adapter;
    	$this->_maxPoolSize = $maxPoolSize;
    }
    
    protected function _borrowConnection($clientKey,$initData){
    	if(!isset(self::$_connnectionPools[$clientKey])){
    		self::$_connnectionPools[$clientKey] = array(
    			"idlePool" => new \SplQueue(),
    			"createCount" => 0
    		);
    	}
    	if(!self::$_connnectionPools[$clientKey]['idlePool']->isEmpty()){
    		return self::$_connnectionPools[$clientKey]['idlePool']->pop();
    	}else{
    		if(!$this->_maxPoolSize || self::$_connnectionPools[$clientKey]["createCount"] < $this->_maxPoolSize){
    			self::$_connnectionPools[$clientKey]['createCount']++;
                $adapterObject = call_user_func_array(__NAMESPACE__."\Client\\".$this->_adapter."::factory",$initData);
    			$adapterObject->setClientKey($clientKey);
                return $adapterObject;
    		}
    	}
    	return null;
    }
    
    public function get($initData){
        $clientKey = $initData['client_key'];
        $initData = $initData['init_data'];
    	$adapterObject = $this->_borrowConnection($clientKey,$initData);
        if($adapterObject && $adapterObject instanceof \Client\Base){
            return $adapterObject;
        }
       	return null;
    }
    
    public function put($connection){
        self::$_connnectionPools[$connection->getClientKey()]['idlePool']->push($connection);
    }
}