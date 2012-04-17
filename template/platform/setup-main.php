<?php
	if(isset($_GET['step'])){
		$step = (int)$_GET['step'] ;
	}else{
		$step = 0;
	}
	
	// extract public
	$fZip = null ;
	$sName = '' ;
	$sInstallPath = '';
	
	function processLine1($sLine){
		global $fZip ;
		global $sName ;
		global $sInstallPath ;
		$sZipKey = '{=$sZipKey}';
		$nLenKey = strlen($sZipKey);
		if( substr( $sLine , 0 , $nLenKey ) === $sZipKey ){
			$arrLine = explode(':',$sLine);
			if( 3 === count($arrLine)){
				$sName = $arrLine[1];
				$sFileName = $sName.'.zip' ;
				if($sName === 'public'){
					$fZip = fopen($sFileName,'w');
				}
				$sInstallPath = $arrLine[2];
			}else if( 2 === count($arrLine)){
				if( null !== $fZip ){
					fclose($fZip);
					$fZip = null ;
				}
				
				if($sName === 'public'){
					$sFileName = $sName.'.zip' ;
					$zip = new ZipArchive;
					if ($zip->open($sFileName) === TRUE) {
						$zip->extractTo($sInstallPath);
						$zip->close();
						unlink($sFileName);
					} else {
					}
				}
			}
		}else if( null !== $fZip ){
			fwrite($fZip , base64_decode($sLine) );
		}
	}

	if(!file_exists('public/')){
		$fp = fopen(__FILE__,'r');
		if(!$fp){
			echo 'Could not open file ',__FILE__;
		}else{
			$sLine = '' ;
			while( false !== ($char = fgetc($fp))){
				if($char === "\n"){
					processLine1($sLine);
					$sLine = '';
				}else{
					$sLine .= $char ;
				}
			}
		}
	}
	
	switch ($step){
	case 0:
		checkEnv();
		break;
	case 1:
		licence();
		break;
	case 2:
		inputBaseInfo();
		break;
	case 3:
		install();
		break;
	}
	
?>
{=$code_checkEnv}
{=$code_licence}
{=$code_input}
{=$code_install}
<?
<foreach for='$arrZips' item='arrZip'>
/*
{=$sZipKey}:{=$arrZip['name']}:{=$arrZip['path']}
<while "! $arrZip['reader']->isEnd()">{=base64_encode($arrZip['reader']->read(80))}
</while>{=$sZipKey}:{=$arrZip['name']}
*/
</foreach>
