<?php
namespace org\opencomb\development\toolkit\extension\createsetup ;

use org\opencomb\coresystem\auth\Id;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\db\DB ;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\io\OutputStreamBuffer ;
use org\jecat\framework\setting\IKey ;
use org\jecat\framework\lang\oop\ClassLoader ;
use org\jecat\framework\fs\Folder ;
use org\jecat\framework\message\Message;

class CreateSetup extends ControlPanel{
	public function createBeanConfig(){
		return array(
			'title'=>'生成安装程序',
			'view:createSetup' => array(
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
	}

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
		foreach($data as $d){
			$data = $this->getTableData($d);
			if(empty($data)){
				$this->arrTableInfoList[$d]['data'] = '1';// 此表不包含数据
			}else{
				$this->arrTableInfoList[$d]['data'] = $data;
				$this->arrTableInfoList[$d]['keys'] = array_keys($data[0]);
			}
		}
		$this->aExtension = Extension::flyweight($extName);
		// namespace 
		$aPackageIterator = $this->aExtension->metainfo()->packageIterator();
		$arrPackage = $aPackageIterator->current();
		$this->ns = $arrPackage[0] ;
		// conf
		if($bContainConf){
			$this->setting = $this->getSettings();
		}
		// file
		if($bContainFile and $this->aExtension->filesFolder()->exists() ){
			$this->sDataFolder = $this->aExtension->metainfo()->installPath().'/data/public';
			try{
				$aToFolder = Folder::singleton()->findFolder($this->sDataFolder);
				if($aToFolder->exists()){
					$aToFolder->delete(true);
				}
				$this->aExtension->filesFolder()->copy($this->sDataFolder);
			}catch(\Exception $e){
				$this->createSetup->createMessage(Message::error,'copy folder error :%s',$e->message());
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
		$aCodeFile = $aPackage->folder()->findFile('setup/Setup.php',Folder::CREATE_RECURSE_DIR | Folder::FIND_AUTO_CREATE );
		$aWriter = $aCodeFile->openWriter();
		$aWriter->write($strSetupCode);
		$aWriter->flush();
		// update meta info
		if($bUpdateMetainfo){
			$sInstallPath = $this->aExtension->metainfo()->installPath() ;
			$sMetainfoFilePath = Folder::singleton()->find($sInstallPath.'/metainfo.xml')->path();
			$aSimpleXML = simplexml_load_file($sMetainfoFilePath) ;
			$aSimpleXML->data->setup = $this->ns.'\setup\Setup';
			$aSimpleXML->asXML($sMetainfoFilePath);
		}
		// template
		$this->createSetup->variables()->set('extName',$extName);
		$this->createSetup->variables()->set('arrTableInfoList',$this->arrTableInfoList);
		$this->createSetup->variables()->set('setting',$this->setting);
		$this->createSetup->variables()->set('dataFolder',$this->sDataFolder);
		$this->createSetup->variables()->set('setupCode',$strSetupCode);
	}
	
	private function getShowCreateTable($tableName){
		$aDB = DB::singleton() ;
		$aRecordset = $aDB->query("SHOW CREATE TABLE `$tableName`");
		$arr = $aRecordset->current();
		return $arr ;
	}
	
	private function getTableData($tableName){
		$aDB = DB::singleton() ;
		$aDriver = $aDB->driver(true);
		$aRecordset = $aDriver->query("select * from `$tableName`");
		$arr = array();
		foreach($aRecordset as $v){
			$arr [] = $v;
		}
		return $arr ;
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
		$aUI->display('development-toolkit:createsetup.php',$variables,$aBuffer);
		return (string)$aBuffer ;
	}
	
	// namespace 是关键字
	private $ns = '' ;
	private $aExtension = null ;
	private $arrTableInfoList = array();
	private $sDataFolder = '';
	private $setting = array();
}
