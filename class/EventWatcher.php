<?php
namespace org\opencomb\development\toolkit ;

use org\jecat\framework\util\EventManager;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class EventWatcher extends ControlPanel
{
	protected $arrConfig = array(
		'title' => '事件管理器' ,	
	) ;
	
	public function process()
	{
		$arrEventHandlers = array() ;
		foreach( EventManager::singleton()->registeredEventClasses() as $sClass)
		{
			foreach( EventManager::singleton()->registeredEventNames($sClass) as $sEventName )
			{
				foreach( EventManager::singleton()->registeredEventObjectIds($sClass,$sEventName) as $sObjectId )
				{
					foreach( EventManager::singleton()->registeredHandleIterator($sClass,$sEventName,$sObjectId) as $arrHandler )
					{
						$arrEventHandlers[$sClass][$sEventName][$sObjectId][] = $arrHandler ;
					}
				}
			}	
		}
		
		$this->view->variables()->set('aEventManager',EventManager::singleton()) ;	
	}

}