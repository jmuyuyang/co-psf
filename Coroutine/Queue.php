<?php
namespace Coroutine;

/**
 * 基础队列类
 * @author yuyang
 *
 */
class Queue extends Base
{

    protected $_waitRead = false;

    protected $_dataQueue;

    public function __construct()
    {
        $this->_dataQueue = new \SplQueue();
    }

    /**
     * pop 队列数据
     * @return mixed
     */
    public function pop()
    {
        $this->_waitRead = true;
        while (true) {
            if ($this->_dataQueue->isEmpty()) {
                yield $this;
            } else {
                $this->_waitRead = false;
                return $this->_dataQueue->pop();
            }
        }
    }

    /**
     * push 队列数据
     * @param unknown $data
     */
    public function push($data)
    {
        if($this->_dataQueue){
            $this->_dataQueue->push($data);
            if ($this->_waitRead) {
                $this->executeCoroutine(true);
                if($this->_coroutine->valid()){
                    $this->next();
                }else{
                    $this->_dataQueue = null;
                }
            }
        }
    }
}
?>