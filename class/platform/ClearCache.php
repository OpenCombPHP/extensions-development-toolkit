<?php
namespace org\opencomb\development\toolkit\platform ;

use jc\message\Message;

use jc\system\Application;

use org\opencomb\coresystem\mvc\controller\ControlPanel;

class ClearCache extends ControlPanel
{

	public function createBeanConfig()
	{
		return array(
			'view:form' => array(
				'template' => 'ClearCache.html' ,
			)		
		) ;
	}

	public function process()
	{
		if( $this->params->has('clear_class_signture') )
		{
			Application::singleton()->setting()->deleteItem('/platform/class','signture') ;
			$this->form->createMessage(Message::success,'系统类库　签名的缓存已经被清除') ;
		}
		
		if( $this->params->has('clear_template_signture') )
		{
			Application::singleton()->setting()->deleteItem('/platform/template','signture') ;
			$this->form->createMessage(Message::success,'系统模板库签名的缓存已经被清除') ;
		}
	}

}

