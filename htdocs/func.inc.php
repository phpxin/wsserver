<?php
// session_start() ;
// echo session_id();
// exit();

date_default_timezone_set('Asia/ChongQing');

//公用函数
define('COOKIE_U_FLAG', 'authcode');
define('MYSQL_SQL_SUCC', '00000');

//房间相关
define('ROOM_STATUS_WAITING', 1) ; //等待玩家加入
define('ROOM_STATUS_PLAYING', 2) ; //游戏中
define('ROOM_STATUS_DONE', 3) ; // 已结束

try{
	$db = new PDO('mysql:host=127.0.0.1;dbname=drawgame', 'root', 'lixinxin');
	$db->query('set names utf8mb4');
}catch(Exception $e){
	writeLog($e->getMessage());
	echo '系统异常 1';
	exit();
}

function writeLog($msg){
	if(!is_string($msg)){
		$msg = var_export($msg, true);
	}
	file_put_contents('./log.txt', date('Y-m-d H:i:s')." ".$msg.PHP_EOL);
}

function jump($url){
	header("Location: {$url}");
	return true;
}

function getClientIp(){
	if (isset($_SERVER['REMOTE_ADDR'])) {
		# code...
		return $_SERVER['REMOTE_ADDR'] ;
	}
	return '' ;
}

function getRequestString($name, $default=''){
	if (!isset($_REQUEST[$name])) {
		# code...
		return $default ;
	}


	$value = $_REQUEST[$name] ;
	$value = trim($value) ;
	$value = addslashes($value) ;
	$value = htmlspecialchars($value) ;


	return $value ;
}

function getRequestInt($name, $default = 0){

	if (!isset($_REQUEST[$name])) {
		# code...
		return $default ;
	}



	return intval(trim($_REQUEST[$name])) ;
}


//获取当前用户信息
function getLoginUserInfo()
{
	if (!isset($_COOKIE[COOKIE_U_FLAG])) {
		return false;
	}


	$cookieStr = $_COOKIE[COOKIE_U_FLAG] ;
	$sql = "select * from user where cookie_str='{$cookieStr}'" ;
	$userInfo = querySql($sql) ;
	if (!$userInfo) {
		return false;
	}
	return $userInfo[0];
}

function getPlayingRoom($uid){
	$sql = 'select r.* from room_user as ru left join room as r on r.id=ru.room_id where (r.status='.ROOM_STATUS_WAITING.' or r.status='.ROOM_STATUS_PLAYING.') and ru.user_id='.$uid.' limit 1' ;

	$roomInfo = querySql($sql) ;
	if (!$roomInfo) {
		return false;
	}

	return $roomInfo[0];
}

function execSql($sql){
	global $db ;
	$ret = true ;
	try{
		$statement = $db->exec($sql);
		if ($db->errorCode()!=MYSQL_SQL_SUCC) {
			writeLog($db->errorInfo());
			$ret = false ;
		}
	}catch(Exception $e){
		writeLog($e->getMessage());
		$ret = false ;
	}

	return $ret;
}


function querySql($sql)
{
	global $db ;
	$ret = [] ;
	try{
		$statement = $db->query($sql);
		if ($db->errorCode()!=MYSQL_SQL_SUCC) {
			writeLog($db->errorInfo());
		}else{
			if($statement->rowCount() > 0){
				$ret = $statement->fetchAll(PDO::FETCH_ASSOC);
			}
		}
		
	}catch(Exception $e){
		writeLog($e->getMessage());
	}
	

	return $ret;
}
