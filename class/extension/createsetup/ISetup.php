<?php
namespace org\opencomb\development\toolkit\extension\createsetup ;

use org\jecat\framework\message\MessageQueue;
use org\opencomb\platform\ext\ExtensionMetainfo ;

interface ISetup{
	public function install(MessageQueue $aMessageQueue,ExtensionMetainfo $aMetainfo);
}
