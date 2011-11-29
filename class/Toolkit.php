<?php
namespace org\opencomb\development\toolkit ;

use org\opencomb\development\toolkit\struct\ui\filter\UILinkHrefFilter;
use org\jecat\framework\system\Request;
use org\jecat\framework\lang\aop\AOP;
use org\opencomb\ext\Extension;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\mvc\view\UIFactory as MvcUIFactory ;

class Toolkit extends Extension
{
	public function load()
	{
		AOP::singleton()->register('org\\opencomb\\development\\toolkit\\aspect\\ControlPanelFrameAspect') ;
		AOP::singleton()->register('org\\opencomb\\development\\toolkit\\aspect\\ControllerAspect') ;
	}
	
	public function active()
	{
		if(Request::singleton()->bool('toolkit_mvcbrowser'))
		{
			// 为 ui 安装 链接 href属性的过滤器 
			UILinkHrefFilter::setupUiFilter(UIFactory::singleton()) ;
			UILinkHrefFilter::setupUiFilter(MvcUIFactory::singleton()) ;
		}
		
	}
}

?>