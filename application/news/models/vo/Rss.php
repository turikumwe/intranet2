<?php
require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('news/models/vo/RssNews.php');
class Rss extends Zend_Db_Table_Row_Abstract{
	
	public function getid()
	{
		return $this->id;
	}
	public function getName()
	{
		return $this->title;
	}
	public function getLastUpdated()
	{
		return $this->last_updated;
	}
	public function getNews($num)
	{
		$date = new Precurio_Date();
		$date->subDay(1);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RSS_NEWS, 'rowClass'=>'RssNews'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::RSS_NEWS),'a.id = b.content_id',array('content_id'))
						->where('a.active=1')
						->where('b.rss_id=?',$this->id)
						->order('a.id DESC')
						->limit($num);
		$news = $table->fetchAll($select);
		return $news;
	}
	public function getUrl()
	{
		return $this->url;	
	}
	public function getLogo()
	{
		return $this->logo;
	}

}

?>