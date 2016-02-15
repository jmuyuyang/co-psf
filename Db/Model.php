<?php
namespace Db;

class Model{
	public $_module;

	protected $_conn;

	protected $_instance;

	public function getInstance(){
		if(!static::$_instance){
			static::$_instance = new self();
		}
		return static::$_instance;
	}

	public function query($sql){
		$this->_conn = null;
		$this->_conn = yield \Coroutine::DB => $this->_module;
		$resp = yield $this->_conn->queryAsync($sql);
		return $resp;
	}

	public function db(){
		$this->_conn = null;
		$this->_conn = yield \Coroutine::DB => $this->_module;
		return $this->_conn;
	}
}