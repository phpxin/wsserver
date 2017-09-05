<?php
include 'func.inc.php' ;

//如果未登录，跳转到登录页
//test    drawdemo.com
$userInfo = getLoginUserInfo();
//var_dump($userInfo);
if (empty($userInfo)) {
	jump('./login.php');
	exit();
}

//如果已经开房，或有游戏中的房间，跳转到房间
$roomInfo = getPlayingRoom($userInfo['id']) ;
if ($roomInfo) {
	jump('./game.php?rid='.$roowInfo['id']);
	exit();
}


//显示按钮，创建房间，输入房间号加入房间
?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1" id="viewport" name="viewport" />
<link href="./style.css" type="text/css" rel="stylesheet" >
<link href="./layer.css" type="text/css" rel="stylesheet" >
<title>draw game</title>
</head>
<body>
<div id="index-main">
	<div class="join-room">
	<p class="text">加入一个已经存在的房间</p>
	<p><input type="text" name="roomid" placeholder="请输入房间号" ><button id="join" type='button' onclick='joinRoom()'>加入房间</button></p>
	</div>
	<div class="create-room">
	<p class="text">创建一个新房间</p>
	<button id="begin" type='button'>创建房间</button>
	</div>
</div>
<script src="./jquery-1.8.0.min.js" type="text/javascript" language="javascript"></script>
<script src="./layer.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" language="javascript">

$('#begin').click(function(){
	//window.location.href = "./game.php?rid=0";
	$.getJSON('./ajax.php?act=createRoom', {}, function(jsonRet){
		if(jsonRet.code){
			layer.msg(jsonRet.msg);
		}else{
			window.location.href = "./game.php?rid="+jsonRet.roomId ;
		}
	}) ;
}) ;

function joinRoom(){
	//var roomId = document.getElementsByName('nickname')[0].value;
	//window.location.href=
	var roomId = $('[name=roomid]').val();
	//alert(roomId);
	$.getJSON('./ajax.php?act=joinRoomCheck&rid='+roomId, {}, function(jsonRet){
		if(jsonRet.code){
			layer.msg(jsonRet.msg);
		}else{
			window.location.href = "./game.php?rid="+roomId ;
		}
	}) ;
}
</script>
</body>
</html>