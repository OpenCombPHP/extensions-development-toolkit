<?php
namespace org\opencomb\development\toolkit ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\platform\service\Service;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension;

class Toolkit extends Extension
{
	public function load()
	{
		// 注册菜单build事件的处理函数
		ControlPanel::registerMenuHandler( array(__CLASS__,'buildControlPanelMenu') ) ;

		if(Service::singleton()->isDebugging())
		{
			AOP::singleton()
				->registerBean(array(
						// jointpoint
						'org\\jecat\\framework\\mvc\\model\\db\\Model::data()' ,
						// advice
						array('org\\opencomb\\development\\toolkit\\aspect\\ModelDataUsefulDetecter','data')						
				),__FILE__)
				
				->registerBean(array(
						// jointpoint
						'org\\jecat\\framework\\mvc\\model\\db\\Model::printStructData()' ,
						// advice
						array('org\\opencomb\\development\\toolkit\\aspect\\ModelDataUsefulDetecter','printStructData')
				),__FILE__) ;
				
			$aAop = AOP::singleton() ;
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
