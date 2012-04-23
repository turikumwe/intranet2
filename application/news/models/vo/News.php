<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('cms/models/vo/Content.php');
class News extends Content {
	
/**
	 * get the news id
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	/**
	 * get the content id
	 * @return int
	 */
	public function getContentId()
	{
		return $this->content_id;
	}
	public function getHeadline()
	{
		return $this->getTitle();
	}
	
	public function getDatePublished()
	{
		return $this->getDateAdded();	
	}
	public function getAuthor()
	{
		return $this->getFullName();
	}
	public function deactivate()
	{
		$this->active = 0;
		//$this->save();
	}
}

?>