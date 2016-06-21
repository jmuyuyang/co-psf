<?php

class Config
{

    const CONFIG_PATH = "conf";

    protected static $_config = array();

    public static function load($name, $node)
    {
        if (! isset(self::$_config[$name])) {
            self::$_config[$name] = require self::CONFIG_PATH . "/" . $name . ".ini.php";
        }
        if (isset(self::$_config[$name][$node])) {
            return self::$_config[$name][$node];
        }
        return array();
    }
}
?>