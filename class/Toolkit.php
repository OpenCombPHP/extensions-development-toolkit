<?php
namespace org\opencomb\development\toolkit ;

use org\opencomb\platform\mvc\view\widget\Menu;
use org\opencomb\platform\service\Service;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension;
use org\opencomb\development\toolkit\aspect\SysteExecuteTimeLog ;

class Toolkit extends Extension
{
	public function load()
	{
		// 注册菜单build事件的处理函数
		Menu::registerBuildHandle(
				'org\\opencomb\\coresystem\\mvc\\controller\\ControlPanelFrame'
				, 'frameView'
				, 'mainMenu'
				, array(__CLASS__,'buildControlPanelMenu')
		) ;

		if(Service::singleton()->isDebugging())
		{
			AOP::singleton()->register('org\\opencomb\\development\\toolkit\\aspect\\ModelDataUsefulDetecter') ;
			
			/*$aAop->registerBean(array(
					// jointponts
					'org\\jecat\\framework\\mvc\\controller\\Controller::__construct()' ,
					'org\\jecat\\framework\\mvc\\controller\\Controller::process()[derived]' ,
					'org\\jecat\\framework\\mvc\\controller\\Response::process()' ,
					'org\\jecat\\framework\\ui\\UI::render()' ,
					// advices
					array('org\\opencomb\\development\\toolkit\\aspect\\SysteExecuteTimeLog','executeTimeLogger') ,
			),__FILE__) ;
			*/
			// 
			
			
		}
	}
	
	static public function buildControlPanelMenu(array & $arrConfig)
	{
		// 合并配置数组，增加菜单
		BeanFactory::mergeConfig(
				$arrConfig
				, BeanFactory::singleton()->findConfig('widget/control-panel-frame-menu','development-toolkit')
		) ;
		
	}
	
}

?>