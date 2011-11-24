<?php
namespace org\opencomb\development\toolkit ;

use org\jecat\framework\lang\aop\AOP;
use org\opencomb\ext\Extension;

class Toolkit extends Extension
{
	public function load()
	{
		AOP::singleton()->register('org\\opencomb\\development\\toolkit\\aspect\\ControlPanelFrameAspect') ;
	}
}

?>