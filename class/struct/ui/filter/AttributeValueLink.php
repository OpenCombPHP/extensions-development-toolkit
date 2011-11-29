<?php
namespace org\opencomb\development\toolkit\struct\ui\filter ;

use org\jecat\framework\ui\xhtml\AttributeValue;

class AttributeValueLink extends AttributeValue
{
	public function __construct() 
	{}
	
	public function cloneOf(AttributeValue $aAttributeValue)
	{
		$this->setPosition($aAttributeValue->position()) ;
		$this->setEndPosition($aAttributeValue->endPosition()) ;
		$this->setLine($aAttributeValue->line()) ;
		$this->setSource($aAttributeValue->source()) ;
		$this->setQuoteType($aAttributeValue->quoteType()) ;
		$this->setName($aAttributeValue->name()) ;
		
		foreach($aAttributeValue->iterator() as $aChild)
		{
			$this->add($aChild) ;
		}
	}
}

?>