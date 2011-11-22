<?php
namespace org\opencomb\development\toolkit\aspect ;

use jc\bean\BeanFactory;

use jc\lang\aop\jointpoint\JointPointMethodDefine;

class ControlPanelFrameAspect
{
	/**
	 * @pointcut
	 */
	public function pointcutCreateBeanConfig()
	{
		return array(
			new JointPointMethodDefine('org\\opencomb\\coresystem\\mvc\\controller\\ControlPanelFrame','createBeanConfig') ,
		) ;
	}
	
	/**
	 * @advice around
	 * @for pointcutCreateBeanConfig
	 */
	private function createBeanConfig()
	{
		// 调用原始原始函数
		$arrConfig = aop_call_origin() ;

		// 增加菜单
		$arrConfig['frameview:frameView']['widget:mainMenu']['items'][] = array(
				'title' => '开发' ,
				'menu' => array(
					'direction' => 'v' ,
					'independence' => true ,
					'items' => array(	
						array(
							'title'=>'创建扩展' ,
							'link' => '?c=org.opencomb.development.toolkit.extension.CreateExtension' ,
						) ,
						array(
							'title'=>'清空缓存' ,
							'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
						) ,
					) ,
				) ,
		) ;

		return $arrConfig ;
	}
}

?>