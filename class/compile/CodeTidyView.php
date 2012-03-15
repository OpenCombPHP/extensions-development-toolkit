<?php
namespace org\opencomb\development\toolkit\compile ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\fs\Folder ;
use org\jecat\framework\io\OutputStreamBuffer ;

class CodeTidyView extends ControlPanel{
	public function createBeanConfig(){
		return array(
			'view:view' => array(
				'template' => 'CodyTidyView.html' ,
			)
		);
	}
	
	public function process(){
		$sPath = $this->params['path'];
		$arrConf = $this->params['conf'];
		$bWriteFile = $this->params['writefile'];
		
		$aFile = Folder::singleton()->find($sPath);
		if(!$aFile){
			return ;
		}
		$aTidy = SourceCodeTidy::singleton();
		
		if(!is_array($arrConf)){
			$arrConf = array() ;
		}
		
		$aBuffer = new OutputStreamBuffer;
		$aTidy->tidy($aFile->openReader(),$aBuffer,$arrConf);
		
		$this->view->variables()->set('source',$aBuffer);
		
		if($bWriteFile){
			$aFile->openWriter()->write($aBuffer);
		}
	}
}
