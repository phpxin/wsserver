<?php
include 'func.inc.php' ;
$errmsg = getRequestString('errmsg');
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
<div id="err-main">
	<p><?php echo $errmsg ; ?></p>
	<button onclick='retIndex()'>返回首页</button>
</div>
<script type="text/javascript" language="JavaScript">
function retIndex(){
	window.location.href="./index.php" ;
}
</script>
</body>
</html>