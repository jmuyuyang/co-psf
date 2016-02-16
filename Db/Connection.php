<?php
/**
 * db connection管理类
 * @author yuyang
 *
 */
namespace Db;

class Connection
{

    public static $instance;

    private static $_connnections = array();

    private static $_adapter = "Mysql";
    
    const CONN_TIMEOUT = 600;//10分钟

    const MAX_POOL_SIZE = 5;

    /**
     * 设置数据库类型
     *
     * @param unknown $adapter            
     */
    public static function setAdapter($adapter)
    {
        self::$_adapter = $adapter;
    }
    
    protected static function _initConnectionPool($module){
        if(!isset(self::$_connnections[$module])){
            self::$_connnections[$module] = array("connection_pools" => new \SplQueue());
            $adapterClass = "\Db\\" . self::$_adapter . "\Adapter";
            for($i=0;$i<self::MAX_POOL_SIZE;$i++){
                $adapter = new $adapterClass($module);
                self::$_connnections[$module]['connection_pools']->push($adapter);
            }
        }
        return self::$_connnections[$module]['connection_pools'];
    }
    
    public static function get($module){
        $connectionPool = self::_initConnectionPool($module);
        if($connectionPool->isEmpty()){
            return false;
        }
        $connObj = $connectionPool->pop();
        if($connObj && $connObj instanceof \Db\Mysql\Adapter){
            if(!$connObj->isConnected()){
                $connObj->connect();
            }
            return $connObj;
        }
    }
    
    public static function release($conn){
        self::$_connnections[$conn->getClientKey()]['connection_pools']->push($conn);
    }
}