<?php
<<<<<<< Updated upstream
namespace org\opencomb\development\toolkit ;

use org\jecat\framework\bean\BeanFactory;
use org\opencomb\platform\Platform;
use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension;
use org\opencomb\development\toolkit\aspect\SysteExecuteTimeLog ;

=======
namespace org\opencomb\development\toolkit ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\platform\service\Service;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension;

>>>>>>> Stashed changes
class Toolkit extends Extension
{
	public function load()
	{
<<<<<<< Updated upstream
		$aAop = AOP::singleton() ;
		$aAop->register('org\\opencomb\\development\\toolkit\\aspect\\ControlPanelFrameAspect') ;
		
		if(Platform::singleton()->isDebugging())
=======
		ControlPanel::registerMenuHandler( array(__CLASS__,'buildControlPanelMenu') ) ;
		
		if(Service::singleton()->isDebugging())
>>>>>>> Stashed changes
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
