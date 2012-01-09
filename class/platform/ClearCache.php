<?php
namespace org\opencomb\development\toolkit\platform ;

use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class ClearCache extends ControlPanel
{

	public function createBeanConfig()
	{
		return array(
			'view:form' => array(
				'template' => 'ClearCache.html' ,
			),
			'controller:removeCache' => array(
					'class' => 'org\\opencomb\\development\\toolkit\\platform\\RemoveCache' ,
			) ,
			'title' => '清理缓存'
		) ;
	}

	public function process()
	{
		if( $this->params->has('clear_system_cache') )
		{
			if( FileSystem::singleton()->delete('/data/cache/platform/system/objects',true,true) )
			{
				$this->form->createMessage(Message::success,'系统缓存 已经被清除') ;				
			}
			else
			{
				$this->form->createMessage(Message::failed,'系统缓存失败') ;
			}
		}
		
		if( $this->params->has('clear_class_compiled') )
		{
			if( FileSystem::singleton()->delete('/data/compiled/class',true,true) )
			{
				$this->form->createMessage(Message::success,'类编译缓存 已经被清除') ;
			}
			else
			{
				$this->form->createMessage(Message::failed,'清除类编译缓存失败') ;
			}	
		}
		
		if( $this->params->has('clear_template_compiled') )
		{
			if( FileSystem::singleton()->delete('/data/compiled/template',true,true) )
			{
				$this->form->createMessage(Message::success,'模板编译缓存 已经被清除') ;	
			}
			else
			{
				$this->form->createMessage(Message::failed,'清除模板编译缓存失败') ;
			}
		}
	}
}