<?php
class FdsAdmin{

	private static $clientFds = [] ;

	public static function addClientFd($fd){
		//array_push(self::$clientFds, $fd) ;
		$GLOBALS['fd'][] = $fd;
	}

	public static function getClientFds(){
		//return self::$clientFds ;
		return $GLOBALS['fd'];
	}

}

$server = new swoole_websocket_server("0.0.0.0", 10002);
 
$server->on('open', function (swoole_websocket_server $server, $request) {
	//$clientFds[] = $request->fd ;
	FdsAdmin::addClientFd($request->fd);
    echo "server: handshake success with fd{$request->fd}\n";
});
 
$server->on('message', function (swoole_websocket_server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    $clientFds = FdsAdmin::getClientFds();
    var_dump($clientFds) ;
    foreach($clientFds as $_item){
		$server->push($_item, $frame->data);
    }
    //$server->push($frame->fd, "this is server");
});
 
$server->on('close', function ($ser, $fd) {
    echo "client {$fd} closed\n";
});
 
$server->start();