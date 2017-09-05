<?php
include 'func.inc.php' ;
//游戏主程序
$userInfo = getLoginUserInfo();
if (empty($userInfo)) {
	jump('./login.php');
	exit();
}

//查询房间信息
$roomId = getRequestString('rid') ;
$sql = "select * from room where id={$roomId} limit 1" ;
$roomInfo = querySql($sql);
if (!$roomInfo) {
	echo '房间不存在' ;
	exit();
}

$roomInfo = $roomInfo[0];
if ($roomInfo['status']!=ROOM_STATUS_WAITING) {
	echo '该房间已经开始或结束游戏';
	exit();
}


//var_dump($roomInfo) ;


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
<div id="game-main">
	<!--游戏界面-->
	<div class="game-block" id="prepare" >
		<button id="btn-begin">开始游戏</button>
	</div>
	<canvas class="game-block" id="player-master">
		a
	</canvas>
	<canvas class="game-block" id="player-slaver">
		b
	</canvas>

	<!--消息显示框-->
	<div id="msg-list">
		<p>消息1</p>
	</div>

	<!--消息输入框-->
	<div id="msg-input">
		<input type="text" name="msg-content" />
		<button>发送</button>
	</div>

</div>
<script src="./jquery-1.8.0.min.js" type="text/javascript" language="javascript"></script>
<script src="./layer.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" language="javascript">
	//console.log($(window).height());

	var userInfo = $.parseJSON('<?php echo json_encode($userInfo)?>');
	var roomInfo = $.parseJSON('<?php echo json_encode($roomInfo)?>');

	//初始化界面
	var docHeight = $(document).height() ;
	$('#game-main').css('height', docHeight);
	$('.game-block').css('height', docHeight*50/100);
	$('#msg-list').css('height', docHeight*40/100);


	$('#prepare').show();


	var cdom = document.getElementById('player-master');
    var cobj = cdom.getContext('2d');//获取一个canvas的2d上下文

    var cdom_copy = document.getElementById('player-slaver');
    var cobj_copy = cdom_copy.getContext('2d');

    var m_x = 0;
    var m_y = 0;
    var raw_movex = 0 ;
    var raw_movey = 0 ;
    var flag_draw = false;


    var pointData = [];
    var _pointData = [];



	//游戏主程序
	var ws = new WebSocket("ws://192.168.2.168:10002");

    ws.onopen = function(){
        console.log("握手成功");
    };

    ws.onclose = function () {
        console.log("closed") ;
    }

    ws.onmessage = function(e){
        console.log("message:" + e.data);
        //var points = JSON.parse(e.data) ;//eval("("++")");
        var data = JSON.parse(e.data) ;
        switch(data.protocol){
        	case 'begin': protocolBegin(data) ; break;
        	case 'draw' : protocolDraw(data); break;
        	case 'msg' : protocolMsg(data) ; break;
        	case 'end' : protocolEnd(data) ; break; 
        	default : 
        }
        
    };

    ws.onerror = function(){
        console.log("error");
    };


    function senddata_do(senddata){
        var _debug = JSON.stringify(senddata);
        console.log(_debug);

        if(ws.readyState == WebSocket.OPEN){
            console.log("ok send ...") ;
            try{
                //var content = document.getElementById('content').value ;
                ws.send(_debug);
            }catch(ex){
                console.log(ex.toString()) ;
            }

        }
        
    }

    $('#btn-begin').click(function(){
    	var sendJson = {
    		"uid" : userInfo.id ,
    		"rid" : roomInfo.id ,
    		"protocol" : "begin"
    	} ;
    	senddata_do(sendJson) ;
    }) ;


    function showErr(errmsg){
    	layer.msg(errmsg);
    }


    function protocolBegin(data){
    	$('.game-block').hide();
    	if(userInfo.id==roomInfo.created_uid){
    		$('#player-master').show();
    	}else{
    		$('#player-slaver').show();
    	}
    }

    function protocolDraw(data){
    	var points = data.points;
		if(points.length>1) {
            cobj_copy.beginPath();
            cobj_copy.moveTo(points[0][0], points[0][1]);
            for (var i = 1, c = points.length; i < c; i++) {
                cobj_copy.lineTo(points[i][0], points[i][1]) ;
            }
            cobj_copy.stroke();
        }
    }

    function protocolMsg(data){

    }

    function protocolEnd(data){

    }

    cdom.addEventListener('touchstart', function(event){



		var touch = event.targetTouches[0]; //touches数组对象获得屏幕上所有的touch，取第一个touch
		console.log(touch.clientX);
		console.log(touch.pageX);

        flag_draw = true;
        m_x = touch.pageX;
        m_y = touch.pageY;

        cobj.beginPath();
        cobj.moveTo(m_x, m_y);

        addData(m_x, m_y);
        _addData(m_x, m_y);
    }) ;


    cdom.addEventListener('touchmove', function(event){

		var touch = event.targetTouches[0]; //touches数组对象获得屏幕上所有的touch，取第一个touch
		var touchX = touch.pageX ;
		var touchY = touch.pageY ;

        if(flag_draw){

        	raw_movex = touchX;
        	raw_movey = touchY ;

            cobj.lineTo(touchX,touchY) ;
            cobj.stroke();

            if(Math.abs(m_x-touchX)>5 && Math.abs(m_y-touchY)>5){
                m_x = touchX;
                m_y = touchY;
                addData(touchX, touchY);
            }
            _addData(touchX, touchY);
        }
    }) ;

    cdom.addEventListener('touchend', function(event){

		//var touch = event.targetTouches[0]; //touches数组对象获得屏幕上所有的touch，取第一个touch
		//console.log(touch);
		//var touchX = touch.pageX ;
		//var touchY = touch.pageY ;

        flag_draw = false;

        addData(raw_movex, raw_movey);
        _addData(raw_movex, raw_movey);
        raw_movex = 0 ;
        raw_movey = 0 ;


        var sendJson = {
    		"uid" : userInfo.id ,
    		"rid" : roomInfo.id ,
    		"protocol" : "draw" ,
    		"points" : pointData
    	} ;
        senddata_do(sendJson);

        pointData = [];

        var _debug2 = JSON.stringify(_pointData);
        console.log(_debug2);
        _pointData = [] ;
    }) ;

    function addData(x, y){
        pointData.push([x,y]) ;
    }

    function _addData(x,y){
        _pointData.push([x,y]) ;
    }
</script>
</body>
</html>









