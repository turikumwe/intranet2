<?php

require_once ('Zend/Db/Table/Row/Abstract.php');

class Fact extends Zend_Db_Table_Row_Abstract 
{
	public function hasUrl()
	{
		return !Precurio_Utils::isNull($this->url);
	}
	public function getTitle()
	{
		return $this->title;
	}
	public function getUrl()
	{
		return $this->url;
	}
	
}

?>