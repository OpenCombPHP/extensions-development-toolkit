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
		<foreach for='$licenceList' item='licence'>
			<div class="xycontent">
				<h2 class="xytit">{=$licence['title']} ( {=$licence['extname']} version : {=$licence['extversion']} ) 协议：{=$licence['licencename']}</h2>
				<div class="xyinner">
					<pre>{=$licence['licencereader']->read()}</pre>
				</div>
			</div>
		</foreach>

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
