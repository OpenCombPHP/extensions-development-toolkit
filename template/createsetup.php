<?php
namespace {=$namespace}\setup;

use org\jecat\framework\message\MessageQueue;
use org\jecat\framework\message\Message;
use org\opencomb\platform\ext\ExtensionMetainfo ;
use org\opencomb\development\toolkit\extension\createsetup\AbstractSetup ;

class Setup extends AbstractSetup{
	public function install(MessageQueue $aMessageQueue,ExtensionMetainfo $aMetainfo){
		// 0 . save metainfo
		$this->setMetainfo($aMetainfo);
		
		// 1 . create data table
		<foreach for="$arrTableInfoList" item="tableinfo">
		$this->executeSQL(
"{=$tableinfo['Create Table']}"
		);
		$aMessageQueue->create(Message::success,'create table %s succeed',"{=$tableinfo['Table']}");
		
		</foreach>
		
		// 2. insert table data
		<foreach for="$arrTableInfoList" item="tableinfo">
			<if "!empty($tableinfo['keys'])">
		$this->insertTableData(
{=var_export($tableinfo['data'],true)}
		,'{=$tableinfo['Table']}'
		);
		$aMessageQueue->create(Message::success,'insert data into table %s succeed',"{=$tableinfo['Table']}");
			</if>
		</foreach>
		
		// 3. settings
		<if '!empty($setting)' >
		$aSetting = $this->setting();
			<foreach for="$setting" key='path' item="items">
				<foreach for="$items" key='item' item='value'>
		$aSetting->setItem('{=$path}','{=$item}',{=var_export($value,true)});
				</foreach>
		$aMessageQueue->create(Message::success,'save settings in path %s succeed',"{=$path}");
			</foreach>
		$aMessageQueue->create(Message::success,'save settings succeed');
		</if>
		
		// 4. files
		<if '!empty($dataFolder)' >
		$sDataFolder = '{=$dataFolder}';
		$sToFolder = $this->getToFolder();
		$this->copyFolder($sDataFolder,$sToFolder);
		$aMessageQueue->create(Message::success,'copy folder from `%s` to `%s` succeed',array($sDataFolder,$sToFolder));
		</if>
	}
}
