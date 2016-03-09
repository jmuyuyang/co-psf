# co-psf
## coroutine based php server framework
## 示例

```
spl_autoload_register(function ($className)
{
    if (! class_exists($className)) {
        $className = str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $className)));
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        return include_once $className . ".php";
    }
});


function loadData($data){
    $client = yield from \Client\Mysql::new("material");
    $resp = yield $client->query("select * from statistic_device limit 1000");
    return $resp;
}

function test(){
    try{
        $client = yield from \Client\WebSocket::new("127.0.0.1","4000");
        $client->send("hello");
        for($i=0;$i<100;$i++){
            $data = yield $client->read();
            \Coroutine::task(loadData($data));
            $client->send("hello");
        }
        $client->close();
    }catch(Exception $e){
        var_dump($e);
    }
}

\Coroutine::init();
\Coroutine::task(test());
\Coroutine::wait();

```