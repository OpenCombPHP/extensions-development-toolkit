<?php
namespace org\opencomb\development\toolkit\zip ;

use net\phpconcept\pclzip\PclZip;

class ZipAdapter{
	const Type_PclZip = 'PclZip';
	const Type_ZipArchive = 'ZipArchive';
	public function __construct($type=null){
		if( self::Type_ZipArchive === $type ){
			$this->sZipType = self::Type_ZipArchive;
		}elseif( self::Type_PclZip === $type ){
			$this->sZipType = self::Type_PclZip;
		}elseif (function_exists('zip_open')){
			$this->sZipType = self::Type_ZipArchive;
		}else{
			$this->sZipType = self::Type_PclZip;
		}
		
		switch($this->sZipType){
		case self::Type_ZipArchive:
			$this->aZipObject = new \ZipArchive() ;
			break;
		case self::Type_PclZip;
			$this->aZipObject = null ;
			break;
		}
	}
	
	const CREATE = 0x01;
	const OVERWRITE = 0x02;
	const EXCL = 0x04;
	public function open($filename,$flags=0){
		switch($this->sZipType){
		case self::Type_ZipArchive:
			$inFlags = 0;
			if( $flags & self::CREATE ){
				$inFlags |= \ZIPARCHIVE::CREATE;
			}
			if( $flags & self::OVERWRITE ){
				$inFlags |= \ZIPARCHIVE::OVERWRITE;
			}
			if( $flags & self::EXCL ){
				$inFlags |= \ZIPARCHIVE::EXCL;
			}
			$rst = $this->aZipObject->open($filename,$inFlags);
			if( $rst !== TRUE ){
				throw new ZipException(
					'打开Zip文件失败，错误码：%d:%s',
					array(
						$rst,
						$this->aZipObject->getStatusString()
					)
				);
			}
			break;
		case self::Type_PclZip;
			if( $flags & self::OVERWRITE ){
				if(file_exists($filename))
				{
					unlink($filename) ;
				}
			}
			$this->aZipObject = new PclZip($filename);
			break;
		}
	}
	
	public function addFile($filename,$localname=NULL){
		
		if( ! file_exists($filename) ){
			throw new ZipException(
				'添加文件到Zip文件中失败:文件%s不存在',
				$filename
			);
		}
		if( is_dir( $filename ) ){
			throw new ZipException(
				'添加文件到Zip文件中失败:%s是一个目录',
				$filename
			);
		}
		switch($this->sZipType){
		case self::Type_ZipArchive:
			$rst = $this->aZipObject->addFile($filename,$localname);
			if( $rst !== TRUE ){
				throw new ZipException(
					'添加文件到Zip文件中失败'
				);
			}
			break;
		case self::Type_PclZip;
			$this->aZipObject->add(
				$filename,
				substr($localname,0,strrpos($localname,'/')),
				substr($filename,0,strrpos($filename,'/'))
			);
			break;
		}
	}
	
	public function addEmptyFolder($sSourcePath , $sTargetPath){
		switch($this->sZipType){
		case self::Type_ZipArchive:
			$rst = $this->aZipObject->addEmptyDir($sTargetPath);
			if( $rst !== TRUE ){
				throw new ZipException(
					'在Zip文件中建立目录失败:`%s`:%s',
					array(
						$sTargetPath,
						$this->aZipObject->getStatusString()
					)
				);
			}
			break;
		case self::Type_PclZip;
			$this->aZipObject->add($sSourcePath,$sTargetPath,$sSourcePath);
			break;
		}
	}
	
	public function close(){
		switch($this->sZipType){
		case self::Type_ZipArchive:
			$rst = $this->aZipObject->close();
			if( $rst !== TRUE ){
				throw new ZipException(
					'关闭Zip文件失败，错误码：%s',
					$this->aZipObject->getStatusString()
				);
			}
			break;
		case self::Type_PclZip;
			break;
		}
	}
	
	private $sZipType ;
	private $aZipObject;
}
