<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('others/class.httpdownload.php');
require_once ('cms/models/vo/Content.php');
class Document extends Zend_Db_Table_Row_Abstract {
	
	
	public function download()
	{
		$root = Zend_Registry::get('root');
		$filename = $root.'/public/'.Content::PATH_TMP.$this->getFullFilename();
		
		file_put_contents($filename,$this->getContent());

		$url = Zend_Controller_Front::getInstance()->getBaseUrl().Content::PATH_TMP.$this->getFullFilename();
		$object = new httpdownload;
	    $object->set_byurl($url);
		$object->use_resume = false;
		$object->download();
//		
//		unlink($filename);//delete the file
//		unset($object);
		return;
	}
	public function getContent()
	{
		return $this->file_content;
	}
	public function getFullFilename()
	{
		return $this->file_name.'.'.$this->file_type;
	}
	public function isWord()
	{
		return $this->file_type == 'doc' || $this->file_type == 'docx';
	}
	
	public function isPdf()
	{
		return $this->file_type == 'pdf' ;
	}

}

?>