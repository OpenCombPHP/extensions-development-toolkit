<?php
namespace org\opencomb\development\toolkit\struct\ui\filter ;

use org\jecat\framework\lang\Object;
use org\jecat\framework\ui\xhtml\parsers\ParserStateTag;
use org\jecat\framework\ui\UIFactory;

class UILinkHrefFilter extends Object
{
	static public function setupUiFilter(UIFactory $aUIFactory)
	{
		// 注册 a node 的 编译器
		ParserStateTag::singleton()->addTagNames('a') ;
		$aUIFactory->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Node')->addSubCompiler('a',__NAMESPACE__."\\LinkCompiler") ;
			
		// 注册 href 属性的 编译器
		$aUIFactory->compilerManager()->add(__NAMESPACE__.'\\AttributeValueLink',__NAMESPACE__.'\\HrefAttruteCompiler') ;
		
		// 重新计算 ui 的编译策略签名
		$aUIFactory->calculateCompileStrategySignture() ;
	}
	
	public function write($data)
	{
		$this->sHrefValue.= (string)$data ;
	}
	
	public function output()
	{
		$sRetHref = $this->sHrefValue ; 
		$this->sHrefValue = '' ;
		
		if( strstr($sRetHref,'?')===false )
		{
			$sRetHref.= '?' ;
		}
		if( strstr($sRetHref,'toolkit_mvcbrowser')===false )
		{
			$sRetHref.= '&toolkit_mvcbrowser=1' ;
		}		
		
		return $sRetHref ;
	}
	
	private $sHrefValue = '' ;
}

?>