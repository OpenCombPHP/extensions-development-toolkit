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
	<foreach for='$arrLicenceList' item='licence'>
		<div class="xycontent">
			<h2 class="xytit">
				{=$licence['exttitle']} ({=$licence['extname']}:{=$licence['extversion']})
				协议：{=$licence['title']}
			</h2>
			<div class="xyinner">
				<pre>{=$licence['contents']}</pre>
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
 
	<div class="xy_check">
		<label>
			<input id="chkAgree" type="checkbox" onclick="agreeLicense(this.checked);" />
			已阅读并同意以上全部内容
		</label>
	</div>


	<a id="btnNext" href="setup.php?action=input" class="step_btn">下一步</a>

</div>