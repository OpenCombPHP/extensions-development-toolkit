<?php

function inputBaseInfo(){
	$sCode = {='<<<'}CODE

<script type="text/javascript">
function next_step(){
	document.getElementById('form_1').submit();
}
</script>
<div class="main">
<div class="content">
	<div class="topbar">
		<h1>蜂巢平台 <span class="azxd">安装向导</span><span class="topversion">版本号</span></h1>
	</div>
	
	<div class="stepbar">
		<ul>
			<li>1. 检查运行环境</li>
			<li>2. 确认协议</li>
			<li class="this-step">3. 填入必要信息</li>
			<li>4. 完成</li>
		</ul>
	</div>
	
	<div class="bottombar step2">
	<h1><span>请填入必要的信息</span></h1>
	<form id="form_1" method="get">
		<input type="hidden" name="step" value="3" />
		<table class="inner">
		<tbody>
		<tr>
		<th>管理员用户</th><td class="tit">用户名：</td><td><input name="adminName" class="in" /></td><td><div class="check_right"></div></td>
		</tr>
		<tr>
		<th></th><td class="tit">密码：</td><td><input type="password" name="adminPswd" class="in" /></td><td><div class="check_error">用户已存在</div></td>
		</tr>
		<tr>
		<th>数据库</th><td class="tit">数据库地址：</td><td><input name="dbAddress" class="in" /></td><td></td>
		</tr>
		<tr>
		<th></th><td class="tit">数据库名：</td><td><input name="dbName" class="in" /></td><td></td>
		</tr>
		<tr>
		<th></th><td class="tit">登录名：</td><td><input name="dbUsername" class="in" /></td><td></td>
		</tr>
		<tr>
		<th></th><td class="tit">密码：</td><td><input type="password" name="dbPswd" class="in" /></td><td></td>
		</tr>
		<tr>
		<th>网站</th><td class="tit">名称：</td><td><input name="websiteName" class="in" /></td><td></td>
		</tr>
		</tbody>
		</table>
		<a id="btnNext" href="#" class="step_btn" onclick="next_step()">下一步</a>
	</form>
</div>
</div>
</div>

CODE;
	echo $sCode ;
}

?>
