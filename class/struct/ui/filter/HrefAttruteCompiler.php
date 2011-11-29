<?php
namespace org\opencomb\development\toolkit\struct\ui\filter ;

use org\jecat\framework\ui\xhtml\compiler\TextCompiler;
use org\jecat\framework\lang\Type;
use org\jecat\framework\ui\ICompiler;
use org\jecat\framework\ui\TargetCodeOutputStream;
use org\jecat\framework\ui\CompilerManager;
use org\jecat\framework\ui\IObject;

class HrefAttruteCompiler extends TextCompiler
{
	public function compile(IObject $aObject,TargetCodeOutputStream $aDev,CompilerManager $aCompilerManager)
	{
		Type::check("org\\jecat\\framework\\ui\\xhtml\\AttributeValueLink",$aObject) ;

		$aDev->write("\$__oriDeviceForLinkHrefStrategy = \$aDevice ;");
		$aDev->write("\$aDevice = \\org\\opencomb\\development\\toolkit\\struct\\ui\\filter\\UILinkHrefFilter::singleton() ;");

		parent::compile($aObject, $aDev, $aCompilerManager) ;
		
		$aDev->write("\$__oriDeviceForLinkHrefStrategy->write( \$aDevice->output() ) ;") ;
		$aDev->write("\$aDevice = \$__oriDeviceForLinkHrefStrategy ;") ;
	}
}

?>