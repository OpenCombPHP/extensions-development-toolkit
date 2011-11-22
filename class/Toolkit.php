<?php
namespace org\opencomb\development\toolkit ;

use jc\lang\aop\AOP;
use oc\ext\Extension;

class Toolkit extends Extension
{
	public function load()
	{
		AOP::singleton()->register('org\\opencomb\\development\\toolkit\\aspect\\ControlPanelFrameAspect') ;
	}
}

?>