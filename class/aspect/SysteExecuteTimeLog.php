<?php
namespace org\opencomb\development\toolkit\aspect ;

use org\jecat\framework\lang\Object;

class SysteExecuteTimeLog extends Object
{ 
	/**
	 * @advice around 
	 * @use org\opencomb\platform\debug\ExecuteTimeWatcher
	 */
	private function executeTimeLogger()
	{
		$sObjId = spl_object_hash($this) ;
		
		$aCallState = aop_calling_state() ;
		$aExecuteTimeWatcher = \org\opencomb\platform\debug\ExecuteTimeWatcher::singleton() ;
		
		// 记录 controller 初始化的时间
		if( $aCallState->originMethod() == 'org\\jecat\\framework\\mvc\\controller\\Controller->__construct()' )
		{
			$this->setName($sName) ;
			$sName = $this->name() ;
			
			$aExecuteTimeWatcher->start("/controller/{$sName}/{$sObjId}/initialize") ;
			
			aop_call_origin($params,$sName,$bBuildAtonce) ;
			
			$aExecuteTimeWatcher->finish("/controller/{$sName}/{$sObjId}/initialize") ;

			return  ;
		}
		else if( $aCallState->originMethod() == 'org\\jecat\\framework\\mvc\\controller\\Response->process()' )
		{
			$sName = $aController->name() ;	
			$sObjId = spl_object_hash($aController) ;
			
			$aExecuteTimeWatcher->start("/controller/{$sName}/{$sObjId}/response") ;
			
			aop_call_origin($aController) ;
			
			$aExecuteTimeWatcher->finish("/controller/{$sName}/{$sObjId}/response") ;
			
			return ;			
		}
		
		else if( $aCallState->sOriginMethod == 'process' )
		{
			$sName = $this->name() ;
			$aExecuteTimeWatcher->start("/controller/{$sName}/{$sObjId}/process") ;
		
			aop_call_origin() ;
		
			$aExecuteTimeWatcher->finish("/controller/{$sName}/{$sObjId}/process") ;
		
			return ;
		}
		
		else if( $aCallState->originMethod() == 'org\\jecat\\framework\\ui\\UI->render()' )
		{
			$sFilename = $aCompiledFile->name() ;			
			$aExecuteTimeWatcher->start("/template/{$sFilename}/render}") ;
			
			aop_call_origin($aCompiledFile,$aVariables,$aDevice) ;
			
			$aExecuteTimeWatcher->start("/template/{$sFilename}/render") ;
			
			return ;			
		}
	}
}
