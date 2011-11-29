<?php
namespace org\opencomb\development\toolkit\struct\ui\filter ;

use org\jecat\framework\ui\xhtml\Node;
use org\jecat\framework\lang\Assert;
use org\jecat\framework\ui\ICompiler;
use org\jecat\framework\ui\TargetCodeOutputStream;
use org\jecat\framework\ui\CompilerManager;
use org\jecat\framework\ui\IObject;
use org\jecat\framework\ui\xhtml\compiler\NodeCompiler;

class LinkCompiler extends NodeCompiler
{
	public function compile(IObject $aObject, TargetCodeOutputStream $aDev, CompilerManager $aCompilerManager)
	{
		Assert::type ( "org\\jecat\\framework\\ui\\xhtml\\Node", $aObject, 'aObject' );

		// 置换属性
		$aAttributes = $aObject->attributes() ;
		if( $aHref = $aAttributes->object('href') )
		{
			$aHrefLink = new AttributeValueLink() ;
			$aHrefLink->cloneOf($aHref) ;
			
			$aAttributes->remove($aHref) ;
			$aAttributes->add($aHrefLink) ;
		}
		
		return parent::compile($aObject,$aDev,$aCompilerManager) ;
	}
}

?>