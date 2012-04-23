<?php

require_once ('news/models/vo/News.php');
require_once ('news/models/vo/Rss.php');
require_once ('cms/models/vo/Content.php');
class MyNews{
	
	public static function getRecent($num)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS, 'rowClass'=>'News'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->distinct()->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::NEWS),'a.id = b.content_id',array('content_id'))
						->where('a.active=1')
						->order('a.last_updated DESC')
						->limit($num);
		$news = $table->fetchAll($select);
		return $news;
	}
	public static function getMostRead($num)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS, 'rowClass'=>'Content'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->where('a.active=1')
						->where('a.publish_as_news=1')
						->order('a.num_of_hits DESC')
						->limit($num);
		$news = $table->fetchAll($select);
		return $news;
	}
	public static function getMostCommented($num)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS, 'rowClass'=>'Content'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::COMMENTS),'a.id = b.content_id',array('content_id','num_of_comments'=>'count(*)'))
						->where('a.active = 1')
						->where('a.publish_as_news = 1')
						->group('b.content_id')
						->having('b.content_id <> 0')
						->order('num_of_comments DESC')
						->limit($num);

		$news = $table->fetchAll($select);
		return $news;
	}
	
	public static function getNews($content_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS, 'rowClass'=>'News'));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::NEWS),'a.id = b.content_id',array('content_id'))
						->where('a.id=?',$content_id)
						->order('id DESC');
		$news = $table->fetchRow($select);
		return $news;
	}
	/**
	 * Returns the most recent news
	 * @return News
	 */
	public static function getMostRecent()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS, 'rowClass'=>'News'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::NEWS),'a.id = b.content_id',array('content_id'))
						->where('a.active=1')
						->order('a.id DESC')
						->limit(1);
		$news = $table->fetchRow($select);
		return $news;
	}
	public static function getRss()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RSS, 'rowClass'=>'Rss'));
		return $table->fetchAll();
	}

}

?>