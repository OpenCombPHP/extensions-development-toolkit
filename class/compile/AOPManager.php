<?php
namespace org\opencomb\development\toolkit\compile ;

use org\opencomb\coresystem\auth\Id;

use org\jecat\framework\lang\oop\ClassLoader;

use org\jecat\framework\message\Message;

use org\jecat\framework\lang\aop\Pointcut;

use org\jecat\framework\lang\aop\jointpoint\JointPoint;
use org\jecat\framework\lang\aop\AOP;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class AOPManager extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'title'=>'AOP管理',
			'view:aopManager' => array(
				'template' => 'AOPManager.html' ,
			),
			'perms' => array(
				// 权限类型的许可
				'perm.purview'=>array(
					'namespace'=>'coresystem',
					'name' => Id::PLATFORM_ADMIN,
				) ,
			) ,
		) ;
	}

	public function process()
	{
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
		
		parent::doActions() ;
		
		$arrAopDetail = array() ;

		foreach(AOP::singleton()->aspectIterator() as $aAspect)
		{
			foreach($aAspect->pointcuts()->iterator() as $aPointcut)
			{
				$aPointcut instanceof Pointcut ;
				
				foreach($aPointcut->jointPoints()->iterator() as $aJointPoint)
				{
					$aJointPoint instanceof JointPoint ;
					
					$sClass = $aJointPoint->weaveClass() ;
					$sDeclare = $aJointPoint->exportDeclare(false) ;
					
					if(empty($arrAopDetail[$sClass][$sDeclare]))
					{
						$arrAopDetail[$sClass][$sDeclare]['aspects'] = array() ;
						$arrAopDetail[$sClass][$sDeclare]['advices'] = array() ;
					}
					
					foreach( $aPointcut->advices()->iterator() as $aAdvice )
					{
						if( !in_array($aAdvice,$arrAopDetail[$sClass][$sDeclare]['advices'],true) )
						{
							$arrAopDetail[$sClass][$sDeclare]['advices'][] = $aAdvice ;
							$arrAopDetail[$sClass][$sDeclare]['aspects'][] = $aAspect ;
						}
					}
				} 
			}
		}
		
		$this->aopManager->variables()->set('arrAopDetail',$arrAopDetail) ;
	}
	
	protected function actionClearClassCompliled()
	{
		if( empty($this->params['class']) )
		{
			$this->aopManager->createMessage(Message::error,'缺少参数') ;
			return ;
		}
		
		if( !$aCompiledFile = ClassLoader::singleton()->searchClass($this->params['class'],ClassLoader::SEARCH_COMPILED) )
		{
			$this->aopManager->createMessage(Message::failed,'没有在系统中找到 class %s 的编译缓存',$this->params['class']) ;
			return ;
		}
		
		if( $aCompiledFile->delete() )
		{
			$this->aopManager->createMessage(Message::success,'class %s 的编译缓存:%s已经删除',array($this->params['class'],$aCompiledFile->url())) ;
			return ;			
		}
		else
		{
			$this->aopManager->createMessage(Message::failed,'无法删除class %s 的编译缓存:%s',array($this->params['class'],$aCompiledFile->url())) ;
			return ;
		}
	}
}


?>