<?php

$clientFds = [] ;

$server = new swoole_websocket_server("0.0.0.0", 10002);
 
$server->on('open', function (swoole_websocket_server $server, $request) use(&$clientFds) {
	$clientFds[] = $request->fd ;
    echo "server: handshake success with fd{$request->fd}\n";
});
 
$server->on('message', function (swoole_websocket_server $server, $frame) use(&$clientFds) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
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