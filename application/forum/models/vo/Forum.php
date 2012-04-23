<?php

require_once ('forum/models/vo/Topic.php');
require_once ('forum/models/vo/Post.php');
		
class Forum extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Gets the id of the forum
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Gets the forum title
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}
	
	/**
	 * Gets the forum description
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}
	
	/**
	 * Gets the number of topics that registered in the forum
	 * @return int
	 */
	public function getNumberOfTopics()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_TOPICS, 'rowClass'=>'Topic'));
		
		return $table->fetchAll($table->select()->where('active = 1')->where('forum_id = ?',$this->getId()))->count();
	}
	
	/**
	 * Gets the number of replies that has been added the forum
	 * @return int
	 */
	public function getNumberOfReplies()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS, 'rowClass'=>'Post'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);			
		
		
		$select = $select->from(array('a' => PrecurioTableConstants::FORUM_POSTS))
			->join(array('b' => PrecurioTableConstants::FORUM_TOPICS), 'b.id = a.topic_id','forum_id')
			->where("a.active = 1 AND b.forum_id = {$this->getId()}")
			->order("a.id DESC");
		
	
		return $table->fetchAll($select)->count();
	}
	
	/**
	 * Gets the last post registered in the forum
	 * @return Post
	 */
	public function getLastPost()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS, 'rowClass'=>'Post'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);			
			
		$select = $select->from(array('a' => PrecurioTableConstants::FORUM_POSTS))
			->join(array('b' => PrecurioTableConstants::USERS), 'a.user_id = b.user_id', array('first_name', 'last_name'))	
			->join(array('d' => PrecurioTableConstants::FORUM_TOPICS), 'd.id = a.topic_id', array())
			->join(array('c' => PrecurioTableConstants::FORUMS), 'c.id = d.forum_id', array())
						
			->where("a.active = 1 AND c.id = {$this->getId()}")
			->order("a.id DESC");
		
	
		return $table->fetchRow($select);
	}	
	
	/**
	 * Gets the name of the user that created the forum
	 * @return string
	 */
	public function getCreator()
	{
		return UserUtil::getUser($this->creator)->getFullName();
	}
	
	/**
	 * Gets the date the forum was created
	 * @return string
	 */
	public function getDateCreated()
	{
		$date = new Precurio_Date($this->date_created);
		if($date->isYesterday()) return 'Yesterday';
		if($date->isToday()) return 'Today';
		
		return $date->get(Precurio_Date::DATE_SHORT);
	}
	
}
?>