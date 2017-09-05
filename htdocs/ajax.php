<?php
header('Content-Type:application/json') ;
include 'func.inc.php' ;

$userInfo = getLoginUserInfo();
//var_dump($userInfo);
if (empty($userInfo)) {
	//jump('./login.php');
	echo json_encode(['code'=>'403', 'msg'=>'用户未登录']) ;
	exit();
}

$act = getRequestString('act') ;


switch($act){
	case 'joinRoomCheck': $retJson = joinRoomCheck() ; break;
	case 'createRoom' : $retJson = createRoom() ; break;
	default: $retJson = json_encode(['code'=>500, 'msg'=>'没有这个方法']) ; 
}


if (!is_string($retJson)) {
	$retJson = json_encode($retJson) ;
}



echo $retJson ;

///////   单元函数

function createRoom(){
	global $userInfo;
	global $db;

	$createdUid = $userInfo['id'] ;
	$createdTime = time();
	$status = ROOM_STATUS_WAITING ;
	$word = '大蒜' ;

	$sql = "insert into room(created_uid, created_time, status, word) values({$createdUid}, {$createdTime}, {$status}, '{$word}')" ;
	if(!execSql($sql)){
		writeLog('insert statement failed sql : '.$sql) ;
		return ['code'=>500, 'msg'=>'ERR:3000'] ;
	}

	$roomId = $db->lastInsertId() ;

	return ['code'=>0, 'msg'=>'ok', 'roomId'=>$roomId] ;
}

function joinRoomCheck(){
	$rid = getRequestInt('rid') ;
	$sql = "select * from room where id={$rid} limit 1" ;
	$roomInfo = querySql($sql);
	if (!$roomInfo) {
		return array('code'=>500, 'msg'=>'房间不存在');
	}
	$roomInfo = $roomInfo[0];
	//var_dump($roomInfo);
	if ($roomInfo['status']!=ROOM_STATUS_WAITING) {
		# code...
		return array('code'=>500, 'msg'=>'该房间已经开始或结束游戏');
	}
	return array('code'=>0, 'msg'=>'ok');
}