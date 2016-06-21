<?php
namespace Client;

class WebSocket extends Base{

	const VERSION = '0.1.4';
    const TOKEN_LENGHT = 16;

	const OP_TEXT     =  1;
    const OP_BINARY   =  2;
    const OP_CLOSE    =  8;
    const OP_PING     =  9;
    const OP_PONG     = 10;

    const TASK_QUEUE = "WebSocket";
    const CONNECT_WAIT = "wait";
    const CONNECT_SUCCESS = "success";
    const CONNECT_FAIL = "fail";
    const CONNECT_SLEEP = "sleep";

    public $host;
    public $port;
    public $path;
    public $timeout;

    protected $key;
    protected $_client;

    protected $_buffer;

    protected $_bufferOffset;

    protected $_recvWait = false;
    
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const UserAgent = 'SwooleWebsocketClient';

    public static function factory($host,$port,$path,$timeout){
        return new self($host,$port,$path,$timeout);
    }

    public static function new($host,$port,$path = '/', $timeout = 0.5){
        $taskData = ["client_key" => $host.":".$port,"init_data" => array($host,$port,$path,$timeout)];
        $client = yield self::TASK_QUEUE => $taskData;
        $client = yield $client->connect();
        return $client;
    }

    /**
     * @param string $host
     * @param int $port
     * @param string $path
     */
    function __construct($host, $port, $path = '/', $timeout = 0.5)
    {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->timeout = $timeout;
        $this->key = $this->generateToken(self::TOKEN_LENGHT);
        $this->_client = new \swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);
    }

    /**
     * Connect client to server
     * @return $this
     */
    public function connect()
    {
        if(! $this->_client->isConnected()){
            $this->_client->on("connect",array($this,'onConnected'));
            $this->_client->on("Receive",array($this,"onReceive"));
            $this->_client->on("close",array($this,"onClose"));
            $this->_client->on("error",array($this,"onError"));
            
            $this->_status = self::CONNECT_WAIT;
            $this->_client->connect($this->host, $this->port, $this->timeout);
        }
        return $this;
    }

    public function onConnected(\swoole_client $cli){
        $cli->send($this->createHeader());
    }

    public function onReceive(\swoole_client $cli, $data){
        if($this->_status == self::CONNECT_SUCCESS){
            if(!$this->_buffer){
                $this->_buffer = new \swoole_buffer(strlen($data));
            }
            if($this->_buffer->capacity < strlen($data)){
                $this->_buffer->expand(strlen($data));
            }
            $this->_buffer->append($data);
            while(true){
                if($this->_recvWait){
                    if($this->_buffer->length >= $this->_bufferOffset){
                        $isContinue = false;
                        if($this->_buffer->length > $this->_bufferOffset){
                            $data = $this->_buffer->substr(0,$this->_bufferOffset,true);
                            $isContinue = true;
                        }else{
                            $data = $this->_buffer->read(0,$this->_bufferOffset);
                            $this->_buffer->clear();
                        }
                        $recv = \swoole_websocket_server::unpack($data);
                        $this->_recvWait = false;
                        $this->_bufferOffset = 0;
                        $this->executeCoroutine($recv);
                        if(!$this->next() || !$isContinue){
                            //当前中断不是websocket io中断或者buffer数据为空
                            break;
                        }
                    }
                }else{
                    $packageLength = $this->getPackageLength($this->_buffer);
                    if(!$packageLength){
                        break;
                    }
                    if($this->_buffer->capacity < $packageLength){
                        $this->_buffer->expand($packageLength);
                    }
                    $this->_bufferOffset = $packageLength;
                    $this->_recvWait = true;
                    if($this->_buffer->length < $this->_bufferOffset){
                        break;
                    }        
                }
            }
        }else{
            try{
            	$recv = $this->parseData($data);
            	if($recv){
            		$this->executeCoroutine($this);
                    $this->next();
            	}
        	}catch(\Exception $e){
	            $this->executeCoroutine(null,$e);
                $this->next();
        	}
        }
    }

    public function onClose(\swoole_client $cli){
        $this->_status = self::CONNECT_WAIT;
    }

    public function onError(\swoole_client $cli){
        $error = socket_strerror($cli->errCode);
        $e = new \Exception("connect to server failed: ".$error);
        $this->executeCoroutine(null,$e);
        $this->next();
    }

    /**
     * Disconnect from server
     */
    public function close($code = 1000){
        if($this->_client->isConnected()){
            if($this->_status == self::CONNECT_SLEEP){
                //如果当前client在沉睡中,先唤醒
                $this->wakeup();
            }
            $this->_client->close();
        }
        $this->_status = self::CONNECT_WAIT;
    }

    public function wakeup(){
        if($this->_status == self::CONNECT_SLEEP){
            $this->_client->wakeup();
            $this->_status = self::CONNECT_SUCCESS;
        }
    }

    public function sleep(){
        if($this->_status == self::CONNECT_SUCCESS){
            $this->_client->sleep();
            $this->_status = self::CONNECT_SLEEP;
        }
    }

    public function read(){
        $this->wakeup();
    	return $this;
    }

    /**
     * send string data
     * @param $data
     * @param string $type
     * @param bool $masked
     * @throws \Exception
     */
    public function send($data, $type = self::OP_TEXT){
        $data = \swoole_websocket_server::pack($data,$type);
        $this->_client->send($data);
    }

    /**
     * send json object
     * @param $data
     * @param bool $masked
     */
    public function sendJson($data){
        $this->send(json_encode($data), self::OP_TEXT);
    }

    public function next(){
        if($this->_coroutine->valid()){
            $coValue = $this->_coroutine->current();
            if(! $coValue instanceof \Client\Websocket){
                \Coroutine::resume($this->_coroutine);
                $this->sleep(); //当前协程不再监听读事件,则移除读事件
                return false;
            }else{
                return true;
            }
        }
    }

    /**
     * Parse received data
     * @param $response
     * @return string
     * @throws \Exception
     */
    protected function parseData($response)
    {
        if($this->_status == self::CONNECT_WAIT){
            if(strpos($response,'Sec-WebSocket-Accept') !== false){
                if ((strpos($response, base64_encode(pack('H*', sha1($this->key . self::GUID)))) !== false)) {
                    $this->_status = self::CONNECT_SUCCESS;
                    return true;
                }else{
                    throw new \Exception("error response key.");
                }
            }else{
                throw new \Exception("websocket connection failed");
            }
        }
        return false;
    }

    public function getPackageLength($buffer){
        if($buffer->length < 2){
            return 0;
        }
        $secondByte = $buffer->read(1,2);
        $defPayLen = ord($secondByte) & 127;

        if($defPayLen < 125){
            $header_length = 2;
            $payload_length = $defPayLen;
        }else{
            $payload_bytes = 1;
            if($defPayLen >= 126){
                $payload_bytes += 2;
            }
            if($defPayLen === 127){
                $payload_bytes += 6;
            }
            $header_length = 1 + $payload_bytes;
            $data = $buffer->read(0,$header_length);
            $len = 0;
            for ($i = 2; $i <= $payload_bytes; $i++) {
                $len <<= 8;
                $len += ord($data[$i]);
            }
            $payload_length = $len;
        }
        $isMask = 128 === (ord($secondByte) & 128);
        if($isMask){
            $header_length += 4;
        }
        return $header_length + $payload_length;
    }

    /**
     * Create header for websocket client
     * @return string
     */
    private function createHeader()
    {
        $host = $this->host;
        if ($host === '127.0.0.1' || $host === '0.0.0.0')
        {
            //$host = 'localhost';
        }

        return "GET {$this->path} HTTP/1.1" . "\r\n" .
        "Origin: null" . "\r\n" .
        "Host: {$host}:{$this->port}" . "\r\n" .
        "Sec-WebSocket-Key: {$this->key}" . "\r\n" .
        "User-Agent: ".self::UserAgent."/" . self::VERSION . "\r\n" .
        "Upgrade: Websocket" . "\r\n" .
        "Connection: Upgrade" . "\r\n" .
        "Sec-WebSocket-Protocol: wamp" . "\r\n" .
        "Sec-WebSocket-Version: 13" . "\r\n" . "\r\n";
    }

    /**
     * Generate token
     *
     * @param int $length
     * @return string
     */
    private function generateToken($length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';

        $useChars = array();
        // select some random chars:
        for ($i = 0; $i < $length; $i++) {
            $useChars[] = $characters[mt_rand(0, strlen($characters) - 1)];
        }
        // Add numbers
        array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
        shuffle($useChars);
        $randomString = trim(implode('', $useChars));
        $randomString = substr($randomString, 0, self::TOKEN_LENGHT);

        return base64_encode($randomString);
    }
}