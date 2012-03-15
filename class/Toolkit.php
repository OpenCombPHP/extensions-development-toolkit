<?php
namespace org\opencomb\development\toolkit ;

use org\opencomb\platform\Platform;

use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension;

class Toolkit extends Extension
{
	public function load()
	{
		AOP::singleton()->register('org\\opencomb\\development\\toolkit\\aspect\\ControlPanelFrameAspect') ;
		
		if(Platform::singleton()->isDebugging())
		{
			AOP::singleton()->register('org\\opencomb\\development\\toolkit\\aspect\\ModelDataUsefulDetecter') ;
		}
	}
}

?>