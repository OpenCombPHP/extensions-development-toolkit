<?php
namespace org\opencomb\development\toolkit ;

use org\opencomb\platform\service\Service;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension;
use org\opencomb\development\toolkit\aspect\SysteExecuteTimeLog ;

class Toolkit extends Extension
{
	public function load()
	{
		$aAop = AOP::singleton() ;
		$aAop->register('org\\opencomb\\development\\toolkit\\aspect\\ControlPanelFrameAspect') ;
		
		if(Service::singleton()->isDebugging())
		{
			$aAop->register('org\\opencomb\\development\\toolkit\\aspect\\ModelDataUsefulDetecter') ;
			
			$aAop->registerBean(array(
					// jointponts
					'org\\jecat\\framework\\mvc\\controller\\Controller::__construct' ,
					'org\\jecat\\framework\\mvc\\controller\\Controller::process[derived]' ,
					// advices
					array('org\\opencomb\\development\\toolkit\\aspect\\SysteExecuteTimeLog','executeTimeLogger') ,
			),__FILE__) ;
			
			// 
			
			
		}
	}
	
}

?>