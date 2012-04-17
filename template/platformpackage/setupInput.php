?>
<script type="text/javascript">
function checkInput(input)
{
	var div_check = jQuery(input).closest('tr').find('.checkinput') ;
	if(input.value == ''){
		div_check.removeClass('check_right');
		div_check.html('不能为空');
		div_check.addClass('check_error');
		return false ;
	}else{
		div_check.removeClass('check_error');
		div_check.html('');
		div_check.addClass('check_right');
		return true ;
	}
}
function next_step(){
	var bIsValid = false ;
	jQuery('.in').each(function(){
		bIsValid = checkInput(this) ;
	});
	if(bIsValid){
		document.getElementById('form_1').submit();
	}
}
jQuery(function(){
	jQuery('.in').change(function (){
		checkInput(this) ;
	});
});
</script>

<div class="stepbar">
	<ul>
		<li>1. 检查运行环境</li>
		<li>2. 确认协议</li>
		<li class="this-step">3. 填入必要信息</li>
		<li>4. 完成</li>
	</ul>
</div>

<div class="bottombar step2">
	<h1>
		<span>请填入必要的信息</span>
	</h1>
	<form id="form_1" method="get">
		<input type="hidden" name="action" value="install" />
		<table class="inner">
			<tbody>
				<tr>
					<th>管理员用户</th>
					<td class="tit">用户名：</td>
					<td><input name="adminName" value="root" class="in" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
				<tr>
					<th></th>
					<td class="tit">密码：</td>
					<td><input type="password" name="adminPswd" value="111111" class="in" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
				<tr>
					<th></th>
					<td class="tit">安全模式密码：</td>
					<td>
						<input type="password" name="safeAdminPswd" value="111111" class="in" />
						当系统遇到错误时用于恢复系统
					</td>
					<td><div class="checkinput"></div></td>
				</tr>
				<tr>
					<th>数据库</th>
					<td class="tit">数据库地址：</td>
					<td><input name="dbAddress" value="192.168.1.1" class="in" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
				<tr>
					<th></th>
					<td class="tit">数据库名：</td>
					<td><input name="dbName" value="oc4" class="in" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
				<tr>
					<th></th>
					<td class="tit">用户名：</td>
					<td><input name="dbUsername" class="in" value="root" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
				<tr>
					<th></th>
					<td class="tit">密码：</td>
					<td><input type="password" name="dbPswd" value="1" class="in" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
				<tr>
					<th></th>
					<td class="tit">数据表前缀：</td>
					<td><input type="text" name="dbPrefix" value="oc_" class="in" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
				
				<tr>
					<th>网站信息</th>
					<td class="tit">名称：</td>
					<td><input name="websiteName" class="in" value="蜂巢系统" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
				<tr>
					<th>访问域名</th>
					<td class="tit">名称：</td>
					<td><input name="websiteHost" class="in" value="{='<?php'} echo $_SERVER['HTTP_HOST'] ;{='?>'}" /></td>
					<td><div class="checkinput"></div></td>
				</tr>
			</tbody>
		</table>
		<a id="btnNext" href="#" class="step_btn" onclick="next_step()">下一步</a>
	</form>
</div>
<?