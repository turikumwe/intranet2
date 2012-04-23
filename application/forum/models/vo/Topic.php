<?php

require_once ('forum/models/vo/Post.php');
		
class Topic extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Gets the id of the topic
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Gets the topic title
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}
	
	/**
	 * Gets the user that created the topic
	 * @return string
	 */	
	public function getCreator()
	{
		return UserUtil::getUser($this->creator)->getFullName();
	}
	/**
	 * Returns the user_id of the creator
	 * @return int
	 */
	
	public function getCreatorId()
	{
		return $this->creator;
	}
	/**
	 * Gets the date the topic was created
	 * @return string
	 */
	public function getDateCreated()
	{
		$date = new Precurio_Date($this->date_created);
		if($date->isYesterday()) return 'Yesterday';
		if($date->isToday()) return 'Today';
		
		return $date->get(Precurio_Date::DATE_SHORT);
	}
	
	/**
	 * Gets the number of replies on the topic
	 * @return int
	 */
	public function getNumberOfReplies()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS));
		return $table->fetchAll($table->select()->where('active = 1')->where('topic_id = ?',$this->getId()))->count();
	}
	
	/**
	 * Gets the last post registered in the topic
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
									
			->where("a.active = 1 AND d.id = {$this->getId()}")
			->order("a.id DESC");
		
	
		return $table->fetchRow($select);
	}	
	
	/**
	 * Checks if the topic has any post that has not been viewed
	 * @return Boolean
	 */
	public function hasNewPost()
	{
		$userId = Precurio_Session::getCurrentUserId();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS, 'rowClass'=>'Post'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);			
			
		$select = $select->from(array('a' => PrecurioTableConstants::FORUM_POSTS))
			->join(array('b' => PrecurioTableConstants::USERS), 'a.user_id = b.user_id', array('first_name', 'last_name'))	
			->join(array('d' => PrecurioTableConstants::FORUM_TOPICS), 'd.id = a.topic_id', array())
									
			->where("a.active = 1 AND d.id = {$this->getId()} AND a.user_id != {$userId} AND a.viewed = 0")
			->order("a.id DESC");
		
	
		return count($table->fetchRow($select));
	}
	
}
?>