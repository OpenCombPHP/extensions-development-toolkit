<?php
namespace org\opencomb\development\toolkit\extension\createsetup ;

use org\opencomb\coresystem\auth\Id;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\db\DB;
use org\jecat\framework\ui\xhtml\UIFactory;
use org\jecat\framework\io\OutputStreamBuffer;
use org\jecat\framework\setting\IKey;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\message\Message;

class CreateSetup extends ControlPanel{
	protected $arrConfig = array(
			'title'=>'生成安装程序',
			'view' => array(
				'template' => 'CreateSetup.html' ,
			),
			'perms' => array(
				// 权限类型的许可
				'perm.purview'=>array(
					'namespace'=>'coresystem',
					'name' => Id::PLATFORM_ADMIN,
				) ,
			) ,
		) ;

	public function process()
	{
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
		// input
		$extName = $this->params['extName'];
		$struct = $this->params['struct']?:array();
		$data = $this->params['data']?:array();
		$bContainConf = $this->params['conf'];
		$bContainFile = $this->params['file'];
		$bUpdateMetainfo = $this->params['updatemetainfo'];
		
		// calc
		$this->sExtName = $extName ;
		foreach($struct as $s){
			$tableInfo = $this->getShowCreateTable($s);
			$this->arrTableInfoList[$s] = $tableInfo ;
		}
		foreach($data as $d)
		{
			list($this->arrTableInfoList[$d]['keys'],$this->arrTableInfoList[$d]['data']) = $this->getTableData($d) ;
			
			if(empty($this->arrTableInfoList[$d]['data']))
			{
				$this->arrTableInfoList[$d]['data'] = '1';// 此表不包含数据
			}
			else
			{
				foreach($this->arrTableInfoList[$d]['keys'] as $nIdx=>&$col)
				{
					$col = "`{$col}`" ;
					$this->arrTableInfoList[$d]['factors'][] = '@'.($nIdx+1) ;
				}
			}
		}
		$this->aExtension = Extension::flyweight($extName);
		// namespace 
		$aPackageIterator = $this->aExtension->metainfo()->packageIterator();
		$arrPackage = $aPackageIterator->current();
		$this->ns = $arrPackage[1] ;
		// conf
		if($bContainConf){
			$this->setting = $this->getSettings();
		}
		// file
		if($bContainFile and $this->aExtension->filesFolder()->exists() ){
			$this->sDataFolder = $this->aExtension->metainfo()->installPath().'/data/public';
			try{
				$aToFolder = Folder::singleton()->findFolder($this->sDataFolder,Folder::FIND_AUTO_CREATE_OBJECT );
				if($aToFolder->exists()){
					$aToFolder->delete(true);
				}
				//$this->aExtension->filesFolder()->copy($this->sDataFolder);
				$this->createMessage(
					Message::notice,
					'由于 FSO 中的 copy() 方法被删除，暂时不提供打包文件功能'
				);
			}catch(\Exception $e){
				$this->createMessage(Message::error,'copy folder error :%s',$e->message());
			}
		}
		$strSetupCode = $this->createSetup() ;
		// save code to file
		$aClassLoader = ClassLoader::singleton();
		foreach($aClassLoader -> packageIterator() as $aPackage){
			if( $aPackage->ns() == $this->ns){
				break;
			}
		}
		
		$aCodeFile = $aPackage->folder()->findFile('setup/DataInstaller.php',Folder::CREATE_RECURSE_DIR | Folder::FIND_AUTO_CREATE );
		try{
			@$aWriter = $aCodeFile->openWriter();
			$aWriter->write($strSetupCode);
			$aWriter->flush();
			$this->createMessage(Message::success,"生成了扩展 %s 的数据安装类：%s",array($extName,$aCodeFile->path())) ;
		}catch(\org\jecat\framework\lang\Exception $e){
			$this->createMessage(
				Message::error,
				"无法写入文件：%s ， 请检查是否有写入权限",
				array(
					$aCodeFile->path()
				)
			) ;
		}
		// update meta info
		if($bUpdateMetainfo){
			$sMetainfoFilePath = $this->aExtension->metainfo()->installPath().'/metainfo.xml';
			$aSimpleXML = simplexml_load_file($sMetainfoFilePath) ;
			$aSimpleXML->data->installer = $this->ns.'\setup\DataInstaller';
			@$SaveResult = $aSimpleXML->asXML($sMetainfoFilePath);
			if( $SaveResult ){
				$this->createMessage(Message::success,"更型了扩展 %s 的 metainfo 文件：%s",array($extName,$sMetainfoFilePath)) ;
			}else{
				$this->createMessage(
					Message::error,
					"无法更新扩展 %s 的 metainfo 文件：%s ， 请检查是否有写入权限",
					array(
						$extName,
						$sMetainfoFilePath
					)
				) ;
			}
		}
		// template
		$this->view()->variables()->set('extName',$extName);
		$this->view()->variables()->set('arrTableInfoList',$this->arrTableInfoList);
		$this->view()->variables()->set('setting',$this->setting);
		$this->view()->variables()->set('dataFolder',$this->sDataFolder);
		$this->view()->variables()->set('setupCode',$strSetupCode);
	}
	
	private function getShowCreateTable($tableName){
		
		$aDB = DB::singleton() ;
		$arrRes = $aDB->query("SHOW CREATE TABLE `".$aDB->transTableName($tableName)."`")->fetch() ;
		
		// 去掉数据表前缀
		if($sTablePrefix=$aDB->tableNamePrefix())
		{
			$sRealTablename = $sTablePrefix.$tableName ;
			$arrRes['Create Table'] = str_replace($sRealTablename,$tableName,$arrRes['Create Table']) ;
		}
		
		// 加上transTableName
		$arrRes['Create Table'] = str_replace(
			'`'.$tableName.'`'
			,'`".$aDB->transTableName("'.$tableName.'")."`'
			,$arrRes['Create Table']) ;
		
		// 加入 "if not exists"
		$arrRes['Create Table'] = str_replace('CREATE TABLE','CREATE TABLE IF NOT EXISTS',$arrRes['Create Table']) ;
		
		return $arrRes ;
	}
	
	private function getTableData($tableName)
	{
		$arrData = $arrCols = array() ;
		$aDB = DB::singleton() ;
		$aRecordset = $aDB->query("select * from `" . $aDB->transTableName( $tableName )."`");
		
		foreach($aRecordset as $row)
		{
			if(!$arrCols)
			{
				$arrCols = array_keys($row) ;
			}
			
			foreach($row as &$cell)
			{
				$cell = $cell===null? 'NULL': ('"'.addslashes($cell).'"') ;
			}
			$arrData[] = $row ;
		}
		return array($arrCols,$arrData) ;
	}
	
	private function getSettings(IKey $aKey = null,$parentPath=''){
		$arrSetting = array();
		if( null === $aKey ){
			$aKey = $this->aExtension->setting()->key('/');
		}
		if( $aKey ){
			$path = $parentPath.'/'.$aKey->name();
			$arrSeting[$path] = array();
			// sub keys
			foreach($aKey->keyIterator() as $aSubKey){
				$arrSubSetting = $this->getSettings($aSubKey,$path);
				$arrSetting = array_merge( $arrSetting, $arrSubSetting);
			}
			// items
			foreach($aKey->itemIterator() as $itemName){
				$arrSetting[$path][$itemName] = $aKey->item($itemName);
			}
		}
		return $arrSetting ;
	}
	
	private function createSetup(){
		$aUI = UIFactory::singleton()->create();
		$aBuffer = new OutputStreamBuffer;
		$className = $this->aExtension->metainfo()->className();
		$variables = array(
			'extName' => $this->sExtName,
			'className' => $className,
			'namespace' => $this->ns,
			'arrTableInfoList' => $this->arrTableInfoList,
			'dataFolder' => $this->sDataFolder,
			'setting' => $this->setting,
		);
		$aUI->display('development-toolkit:createsetup.php.tpl',$variables,$aBuffer);
		return (string)$aBuffer ;
	}
	
	// namespace 是关键字
	private $ns = '' ;
	private $aExtension = null ;
	private $arrTableInfoList = array();
	private $sDataFolder = '';
	private $setting = array();
}

