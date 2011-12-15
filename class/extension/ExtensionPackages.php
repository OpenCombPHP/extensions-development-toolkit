<?php
namespace org\opencomb\development\toolkit\extension ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\message\Message ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\fs\FileSystem ;
use org\jecat\framework\fs\FSIterator ;
use org\jecat\framework\fs\IFolder ;
use org\jecat\framework\session\Session ;

// /?c=org.opencomb.development.toolkit.extension.ExtensionPackages
// 扩展打包之后的文件名：
// <extension name>-<version>.ocp.zip

class ExtensionPackages extends ControlPanel{
    public function createBeanConfig()
	{
		return array(
			'view:extensionPackages' => array(
				'template' => 'ExtensionPackages.html' ,
			),
		);
	}
	
	public function process(){
        $this->doActions() ;
	    $this->extensionPackages->variables()->set('packageList',$this->packageList()) ;
	}
	
	protected function actionPackage(){
	    $name = $this->params['name'];
	    $debug = $this->getDebug();
	    $packageList = $this->packageList();
	    $package = $packageList[$name];
	    if(!empty($package)){
	        $aZip = new \ZipArchive();
			$filename = $this->getPackagedFSO($package['name'],$package['version'])->name();
            $filePath = $this->getPackagedFSO($package['name'],$package['version'])->url(false);
	        switch($debug){
	        case '1':
	        	$this->extensionPackages->createMessage(Message::notice,'创建压缩文件:%s',$filename);
	        	break;
            case '2':
                $this->extensionPackages->createMessage(Message::notice,'即将创建压缩文件:%s',$filePath);
                break;
            default:
                break;
	        }
	        if($aZip->open($filePath,\ZIPARCHIVE::CREATE) !== TRUE){
	            $this->extensionPackages->createMessage(Message::notice,"can not open file <$filePath>");
	        }else{
                $installFolder = FileSystem::singleton()->findFolder($package['installPath']);
                switch($debug){
                case '1':
                    $this->extensionPackages->createMessage(Message::notice,'扩展安装目录:%s',$installFolder->path());
                    break;
                case '2':
                    $this->extensionPackages->createMessage(Message::notice,'扩展安装目录:%s',$installFolder->url(false));
                    break;
                default:
                    break;
                }
                foreach($installFolder->iterator(FSIterator::FILE | FSIterator::FOLDER | FSIterator::RETURN_ABSOLUTE_PATH | FSIterator::RECURSIVE_SEARCH | FSIterator::RETURN_FSO) as $it){
                    if(preg_match('/.git/',$it->path())){
                        
                    }else{
                        if($it instanceof IFolder){
                            $aZip->addEmptyDir($it->path());
                            switch($debug){
                            case '1':
                                $this->extensionPackages->createMessage(Message::notice, '创建目录：%s',array($it->path()));
                                break;
                            case '2':
                                $this->extensionPackages->createMessage(Message::notice, '创建目录：%s:%s',array($it->path(),$aZip->getStatusString()));
                                break;
                            default:
                                break;
                            }
                            
                        }else{
                            $aZip->addFile($it->url(false),$it->path());
                            switch($debug){
                            case '1':
                                $this->extensionPackages->createMessage(Message::notice, '压缩文件%s',array($it->path()));
                                break;
                            case '2':
                                $this->extensionPackages->createMessage(Message::notice, '压缩文件%s来自%s:%s',array($it->path(),$it->url(false),$aZip->getStatusString()));
                                break;
                            default:
                                break;
                            }
                        }
                    }
                }
                switch($debug){
                case '1':
                default:
                    $this->extensionPackages->createMessage(Message::notice,'%s打包成功',array($name));
                    break;
                case '2':
                    $this->extensionPackages->createMessage(Message::notice,'%s打包成功:%s',array($name,$aZip->getStatusString()));
                    break;
                }
                $aZip->close();
                switch($debug){
                case '1':
                    $this->extensionPackages->createMessage(Message::notice,'关闭压缩文件:%s',$filename);
                    break;
                case '2':
                    $this->extensionPackages->createMessage(Message::notice,'关闭压缩文件:%s',$filePath);
                    break;
                default:
                    break;
	            }
	        }
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
	                    'version' => $ext->version(),
	                    'installPath' => $ext->installPath(),
	                    'hasPackaged' => $this->hasPackaged($ext->name(),$ext->version()),
	                    'metainfo' => $ext,
	                    'link' =>
	                        array(
	                            'package' => $this->createLink('package',$ext->name()),
	                            'download' => $this->createLink('download',$ext->name(),$ext->version()),
	                        ),
	                );
	        }
	        
	    }
	    return $this->arrPackageList;
	}
	
	private function getDebug(){
	    $aSession = Session::singleton();
	    if(!empty($this->params['debug'])){
	        $aSession->addVariable('debug',$this->params['debug']);
	        //$aSession->variable('debug') = $this->params['debug'];
	    }
	    return $aSession->variable('debug');
	}
	
	private function getPackageFolder(){
	    return Extension::flyweight('development-toolkit')->publicFolder();
	}
	
	private function getPackagedFSO($name,$version){
	    return $this->getPackageFolder()->findFile($name.'-'.$version.'.ocp.zip',FileSystem::FIND_AUTO_CREATE_OBJECT);
	}
	
	private function hasPackaged($name,$version){
	    return $this->getPackagedFSO($name,$version)->exists();
	}
	
	private function createLink($type,$name,$version=''){
	    switch($type){
	    case 'package':
    		return '/?c=org.opencomb.development.toolkit.extension.ExtensionPackages&act=package&name='.$name;
    		break;
		case 'download':
		    return $this->getPackagedFSO($name,$version)->path();
		    break;
		}
	}
	
	private $arrPackageList = null;
}
