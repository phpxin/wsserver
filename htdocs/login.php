<?php
//var_dump($_SERVER['REMOTE_ADDR']) ;
//exit();


include 'func.inc.php' ;
//监测如果cookie没有用户标志，显示输入姓名，加入游戏，跳转到当前页面
$userInfo = getLoginUserInfo();
if (!empty($userInfo)) {
	jump('./index.php');
	exit();
}

$opt = getRequestInt('opt');
if ($opt) {
	# code...
	$nickname = getRequestString('nickname');
	if (empty($nickname)) {
		# code...
		jump('./login.php?err=请填写昵称');
	}


	$cookieStr = md5(time().uniqid());
	setcookie(COOKIE_U_FLAG, $cookieStr, time()+60*60*24) ; //保存一天
	$cip = getClientIp();
	$now = time();

	$sql = "insert into user(name, cookie_str, created_time, client_ip) values('{$nickname}', '{$cookieStr}', {$now}, '{$cip}')" ;
	if(!execSql($sql)){
		echo 'ERR: 201' ;
		exit();
	}else{
		jump('./index.php');
	}

}


?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<link href="./style.css" type="text/css" rel="stylesheet" >
<title>draw game</title>
</head>
<body>
<div id="login-main">
	<form action="?opt=1" method="post">
		<input type="text" name="nickname" placeholder="请输入昵称" >
		<button id="begin" type='submit'>开始游戏</button>
	</form>
	
	<?php
	$err = getRequestString('err');
	if($err){
		echo '<div class="err-area">*'.$err.'</div>' ;
	}
	?>
	
</div>
</body>
</html>