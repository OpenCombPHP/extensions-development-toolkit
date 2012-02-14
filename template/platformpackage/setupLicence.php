<?php

function licence(){
	$sCode = {='<<<'}CODE
<div class="main">
<div class="content">
	<div class="topbar">
		<h1>蜂巢平台 <span class="azxd">安装向导</span><span class="topversion">版本号</span></h1>
	</div>
	
	<div class="stepbar">
		<ul>
			<li>1. 检查运行环境</li>
			<li class="this-step">2. 确认协议</li>
			<li>3. 填入必要信息</li>
			<li>4. 完成</li>
		</ul>
	</div>
	
	<div class="bottombar step1">
		<h1><span>您是否同意以下协议</span></h1>
		<!--xy-->
	<div class="xycontent">
		<h2 class="xytit">协议一</h2>
		<div class="xyinner">Copyright (C) 2012  JeCat.org
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.
		</div>
	</div>
	<!--xy-->
	<div class="xycontent">
		<h2 class="xytit">协议一</h2>
		<div class="xyinner">Copyright (C) 2012  JeCat.org
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.
		</div>
	</div>
		<div class="xycontent">
		<h2 class="xytit">协议一</h2>
		<div class="xyinner">Copyright (C) 2012  JeCat.org
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.
		</div>
	</div>



<script type="text/javascript">
$(".xytit").click(function(){
    $(this).next().slideToggle("slow");
});
function agreeLicense(c){
	var btnNext = document.getElementById('btnNext');
	if(c){
		btnNext.style.display='block';
	}else{
		btnNext.style.display='none';
	}
}
window.onload = function(){
	var chkAgree = document.getElementById('chkAgree');
	agreeLicense(chkAgree.checked);
}

</script> 
<div class="xy_check"><label><input id="chkAgree" type="checkbox" onChange="agreeLicense(this.checked);" />已阅读GNU General Public License version 3并同意全部内容</label></div>
<a id="btnNext" href="/setup.php?step=2" class="step_btn">下一步</a>
<!---->

  </div>
</div>
</div>
CODE;
	echo $sCode ;
}
?>
