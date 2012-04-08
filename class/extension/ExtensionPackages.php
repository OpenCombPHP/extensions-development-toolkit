<?php
namespace org\opencomb\development\toolkit\extension ;

use org\opencomb\platform\service\Service;

use org\opencomb\coresystem\auth\Id;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\message\Message ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\fs\Folder ;
use org\jecat\framework\fs\FSIterator ;
use org\jecat\framework\fs\imp\LocalFSO ;
use org\opencomb\platform\service\Service ;

// /?c=org.opencomb.development.toolkit.extension.ExtensionPackages

class ExtensionPackages extends ControlPanel{
	public function createBeanConfig()
	{
		return array(
			'title'=>'扩展打包',
			'view:extensionPackages' => array(
				'template' => 'ExtensionPackages.html' ,
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
		
		$this->doActions() ;
		
		$aPackageFolder = $this->getPackageFolder();
		
		$this->extensionPackages->variables()->set('packageFolder',$aPackageFolder->path());
		$this->extensionPackages->variables()->set('packageList',$this->packageList()) ;
	}
	
	protected function actionPackage(){
		$name = $this->params['name'];
		$includeGit = $this->params['includeGit'];
		
		$debug = $this->getDebug();
		$packageList = $this->packageList();
		$package = $packageList[$name];
		$bSuccess = true;
		if(!empty($package)){
			$aPackagedFSO = $this->getPackagedFSO($package['name'],$package['version'],$includeGit);
			$aZip = new \ZipArchive();
			$filename = $aPackagedFSO->name();
			$filePath = $aPackagedFSO->path();
			if($debug){
				$this->extensionPackages->createMessage(Message::notice,'即将创建压缩文件:%s : %s',array($aPackagedFSO->path(),$filePath));
			}else{
				$this->extensionPackages->createMessage(Message::notice,'创建扩展包，在:%s',$aPackagedFSO->path());
			}
			if($aZip->open($filePath,\ZIPARCHIVE::CREATE) !== TRUE){
				$this->extensionPackages->createMessage(Message::notice,"can not open file <$filePath>");
			}else{
				$installFolder = Folder::singleton()->findFolder($package['installPath']);
				if($debug){
					$this->extensionPackages->createMessage(Message::notice,'扩展安装目录:%s',$installFolder->path());
				}
				if($debug){
					if($includeGit){
						$this->extensionPackages->createMessage(Message::notice,'包含git\svn\cvs目录');
					}
				}
				foreach($installFolder->iterator(FSIterator::FILE | FSIterator::FOLDER | FSIterator::RECURSIVE_SEARCH | FSIterator::RETURN_FSO) as $it){
					if(preg_match('`/\\.(git|svn|cvs)(/|$)`',$it->path())){
						if(empty($includeGit)){
							continue;
						}
					}
					if($it instanceof Folder){
						$path = $it->path();
						$path = Folder::relativePath($installFolder,$it);
						$bSuccess = $bSuccess and $aZip->addEmptyDir($path);
						if($debug){
							$this->extensionPackages->createMessage(Message::notice, '创建目录：%s : %s',array($path,$aZip->getStatusString()));
						}
					}else{
						$path = $it->path();
						$path = Folder::relativePath($installFolder,$it);
						$bSuccess = $bSuccess and $aZip->addFile($it->path(),$path);
						if($debug){
							$this->extensionPackages->createMessage(Message::notice, '压缩文件 %s 来自 %s : %s',array($path,$it->path(),$aZip->getStatusString()));
						}
					}
				}
				$bSuccess = $bSuccess and $aZip->close();
				if($debug){
					$this->extensionPackages->createMessage(Message::notice,'关闭压缩文件:%s',array($aPackagedFSO->path()));
				}
				if($debug){
					if($bSuccess){
						$this->extensionPackages->createMessage(Message::notice,'%s打包成功',array($name));
					}else{
						$this->extensionPackages->createMessage(Message::notice,'%s打包失败',array($name));
					}
				}else{
					if($bSuccess){
						$this->extensionPackages->createMessage(Message::notice,'%s打包成功',array($name));
					}else{
						$this->extensionPackages->createMessage(Message::notice,'%s打包失败',array($name));
					}
				}
			}
			
			// disable tempsave
			$this->arrPackageList = null;
		}
	}
	
	private function packageList(){
		if(empty($this->arrPackageList)){
			$this->arrPackageList = array();
			$aExtensionManager = ExtensionManager::singleton();
			foreach($aExtensionManager->metainfoIterator() as $ext){
				$name = (string)($ext->name());
				$this->arrPackageList[$name] =
					array(
						'name' => $ext->name(),
						'title' => $ext->title(),
						'version' => $ext->version(),
						'installPath' => $ext->installPath(),
						'hasPackaged' => $this->hasPackaged($ext->name(),$ext->version(),0),
						'hasPackagedVl' => $this->hasPackaged($ext->name(),$ext->version(),1),
						'metainfo' => $ext,
						'link' =>
							array(
								'package' => $this->createLink('package',$ext->name()),
								'packageVl' => $this->createLink('package',$ext->name(),'',1),
								'download' => $this->createLink('download',$ext->name(),$ext->version()),
								'downloadVl' => $this->createLink('download',$ext->name(),$ext->version(),1),
							),
					);
			}
		}
		return $this->arrPackageList;
	}
	
	static private function getDebug(){
		return Service::singleton()->isDebugging();
	}
	
	static private function getPackageFolder(){
		return Extension::flyweight('development-toolkit')->publicFolder()->findFolder('extensionPackages',Folder::FIND_AUTO_CREATE);
	}
	
	/**
	 * 扩展打包之后的文件名：
	 * 1.不含版本库
	 * <extension name>-<version>.ocp.zip
	 * 2.包含版本库
	 * <extension name>-<version>-repos.ocp.zip
	 */
	static public function getPackagedFSO($name,$version , $vl){
		$sVl = '';
		if(empty($vl)){
			$sVl = '';
		}else{
			$sVl = '-repos';
		}
		return self::getPackageFolder()->findFile($name.'-'.$version.$sVl.'.ocp.zip',Folder::FIND_AUTO_CREATE_OBJECT);
	}
	
	static public function hasPackaged($name,$version , $vl){
		return self::getPackagedFSO($name,$version , $vl)->exists();
	}
	
	static public function createLink($type,$name,$version='',$vl=''){
		switch($type){
		case 'package':
			if(empty($vl)){
				return '/?c=org.opencomb.development.toolkit.extension.ExtensionPackages&act=package&name='.$name;
			}else{
				return '/?c=org.opencomb.development.toolkit.extension.ExtensionPackages&act=package&name='.$name.'&includeGit=on';
			}
			break;
		case 'download':
			return Folder::relativePath(Folder::singleton(),self::getPackagedFSO($name,$version,$vl)->path());
			break;
		}
	}
	
	private $arrPackageList = null;
}
