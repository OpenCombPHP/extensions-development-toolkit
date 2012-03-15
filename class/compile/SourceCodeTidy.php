<?php
namespace org\opencomb\development\toolkit\compile ;

use org\jecat\framework\io\IInputStream;
use org\jecat\framework\io\IOutputStream;
use org\jecat\framework\lang\Object ;

class SourceCodeTidy extends Object{
	/**
	 * @param $arrConf array(
								'tidyUse' => true ,
								'tidyCloseTag' => true ,
								'addCopyRight' => true ,
								'copyRight' => text ,
								'tidyBOM' => true
							);
	 */
	public function tidy(IInputStream $aInputStream , IOutputStream $aOutputStream,array $arrConf = array() ){
		$sContent = $aInputStream->read();
		if( isset($arrConf['tidyUse']) and $arrConf['tidyUse'] ){
			$sContent = $this->tidyUse($sContent);
		}
		
		if( isset($arrConf['tidyCloseTag']) and $arrConf['tidyCloseTag'] ){
			$sContent = $this->tidyCloseTag($sContent);
		}
		
		if( isset($arrConf['addCopyRight']) and $arrConf['addCopyRight'] ){
			$sContent = $this->addCopyRight($sContent , $arrConf['copyRight'] );
		}
		
		if( isset($arrConf['tidyBOM']) and $arrConf['tidyBOM'] ){
			$sContent = $this->tidyBOM($sContent);
		}
		
		$aOutputStream->write($sContent) ;
	}
	
	private function tidyUse($sContent){
		$sCleanContent = $this->cleanContent($sContent);
		
		// use area
		$sPregUseArea = '`(use\s*([a-zA-Z0-9\\\\]*)\\\\([a-zA-Z0-9]*)(\s*as\s*([a-zA-Z0-9]*))?\s*;(\n|\r|\s)*)+`';
		preg_match_all($sPregUseArea,$sCleanContent,$arrUseArea,PREG_OFFSET_CAPTURE);
		// use
		$arrAlreadyUsed = array() ;
		$sContentTidyUse = $sContent ;
		for($i=0;$i<count($arrUseArea[0]);++$i){
			$sUseArea = $arrUseArea[0][$i][0] ;
			$iOffset = $arrUseArea[0][$i][1] ;
			
			$arrUseMap = $this->getUseMap($sUseArea);
			
			// words
			preg_match_all('`(?:[ :(){}\\\\]|\n|\r|\t)([a-zA-Z][a-zA-Z0-9]*)`',$sCleanContent,$arrMatch,PREG_OFFSET_CAPTURE,$iOffset + strlen($sUseArea) );
			$arrWords = $arrMatch[1];
			
			foreach($arrWords as $arrWord){
				$sWord = $arrWord[0];
				$nPos = $arrWord[1];
				if( isset($arrUseMap[$sWord]) && !in_array($sWord,$arrAlreadyUsed) ){
					$arrUseMap[$sWord]['useful'] = true ;
					$arrAlreadyUsed [] = $sWord ;
				}
			}
			
			// use content
			$sUseContent = '';
			foreach($arrUseMap as $key=>$arrUse){
				if(isset($arrUse['useful'])){
					if($arrUse['as']){
						$sUseContent .= 'use '.$arrUse['word'].' as '.$key.";\n";
					}else{
						$sUseContent .= 'use '.$arrUse['word'].";\n";
					}
				}
			}
			
			// insert use after namespace
			$sSlash = preg_replace('`\\\\`','\\\\\\\\',$sUseArea);
			$sContentTidyUse = preg_replace("`(\\n|\\r|\\s)*$sSlash(\\n|\\r|\\s)*`","\n\n$sUseContent\n",$sContentTidyUse);
		}
		
		$sContent = '';
		$sContent .= $sContentTidyUse."\n";
		
		return $sContent ;
	}
	
	private function tidyCloseTag($sContent){
		$sContent = preg_replace('`(\n|\r|\s)*\?>(\n|\r|\s)*$`','',$sContent);
		return $sContent ;
	}
	
	private function addCopyRight($sContent,$sCopyRight){
		$sContent = preg_replace('`(<\?php)`',"\\1\n/*\n$sCopyRight\n*/",$sContent);
		return $sContent ;
	}
	
	private function tidyBOM($sContent){
		$sFirst3 = substr($sContent,0,3);
		
		if($sFirst3 === pack("CCC",0xef,0xbb,0xbf)){
			$sContent = substr($sContent,3);
		}
		
		return $sContent ;
	}
	
	private function cleanContent($sContent){
		// 删除单引号 字符串;
		// $sContent = preg_replace('`\'(.*?)[^\\\\](\\\\\\\\)*\'`','',$sContent);
		$sContent = preg_replace_callback(
			'`\'(.*?)[^\\\\](\\\\\\\\)*\'`',
			function($arrMatch){
				return "'".base64_encode($arrMatch[0])."'";
			},
			$sContent
		);
		
		// 删除双引号 字符串;
		//$sContent = preg_replace('`"(.*?)[^\\\\](\\\\\\\\)*"`','',$sContent);
		$sContent = preg_replace_callback(
			'`"(.*?)[^\\\\](\\\\\\\\)*"`',
			function($arrMatch){
				return '"'.base64_encode($arrMatch[0]).'"';
			},
			$sContent
		);
		
		// 删除<<<字符串
		$sPreg = '`<<<(.*)([\n\r]+)((.|\n|\r)*)\\1`';
		//$sContent = preg_replace($sPreg,'',$sContent);
		$sContent = preg_replace_callback(
			$sPreg,
			function($arrMatch){
				return '<<<'.$arrMatch[1].$arrMatch[2].base64_encode($arrMatch[3])."\n".$arrMatch[1];
			},
			$sContent
		);
		
		// 单行注释
		//$sContent = preg_replace('`//(.*)($|\r|\n)`','',$sContent);
		$sContent = preg_replace_callback(
			'`//(.*)($|\r|\n)`',
			function($arrMatch){
				return '//'.base64_encode($arrMatch[1])."\n";
			},
			$sContent
		);
		
		/*
			多行注释
		*/
		//$sContent = preg_replace('`/\*(.*?)\*/`s','',$sContent);
		$sContent = preg_replace_callback(
			'`/\*(.*?)\*/`s',
			function($arrMatch){
				return '/*'.base64_encode($arrMatch[1]).'*/';
			},
			$sContent
		);
		
		return $sContent ;
	}
	
	private function getUseMap($sContent){
		// use map
		$arrUseMap = array();
		// use without as
		$sPregUse = '`use\s*(([a-zA-Z0-9\\\\]*)\\\\([a-zA-Z0-9]*))\s*;`' ;
		preg_match_all($sPregUse,$sContent,$arrMatch);
		for($i=0;$i<count($arrMatch[0]);++$i){
			$key = $arrMatch[3][$i];
			$value = $arrMatch[1][$i];
			
			$arrUseMap[$key] = array(
				'word'=>$value,
				'as' => false,
			);
		}
		
		// use with as
		$sPregWithAs = '`use\s*([a-zA-Z0-9\\\\]*)\s*as\s*([a-zA-Z0-9]*)\s*;`' ;
		preg_match_all($sPregWithAs,$sContent,$arrMatch);
		for($i=0;$i<count($arrMatch[0]);++$i){
			$key = $arrMatch[2][$i];
			$value = $arrMatch[1][$i];
			
			$arrUseMap[$key] = array(
				'word' => $value ,
				'as' => true,
			);
		}
		
		return $arrUseMap ;
	}
}
