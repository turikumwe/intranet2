<?php
require_once ('forum/models/vo/Topic.php');
require_once ('forum/models/Posts.php');


class Topics
{
	public function getCount($forum_id)
	{
		return count($this->getTopics($forum_id));
	}
	
	public function getTopics($forum_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_TOPICS, 'rowClass'=>'Topic'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);			
			
		$select = $select->from(array('a' => PrecurioTableConstants::FORUM_TOPICS))
			->join(array('b' => PrecurioTableConstants::USERS), 'a.creator = b.user_id', array('first_name', 'last_name'))	
			->where("a.forum_id = {$forum_id} AND a.active = 1");
		
	
		return $table->fetchAll($select);
	}
		
	public function addTopic($data)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_TOPICS,'rowClass'=>'Topic'));
		$msg = "";
		$tr = Zend_Registry::get('Zend_Translate');
		try
		{
			$t_post = $data['post'];
			unset($data['post']);
			
			$row = $table->createRow($data);
			$id = $row->save();
			if($id)
			{
				$url = '/forum/post/index/tid/'.$id;
				Precurio_Activity::newActivity($data['creator'],Precurio_Activity::TOPIC_ADDED,$id,$url);
				$msg = $tr->translate(PrecurioStrings::ADDSUCESS);
				
				$posts = new Posts();
				
				$post['content'] = $t_post;
				$post['topic_id'] = $id;
				$post['user_id'] = Precurio_Session::getCurrentUserId();
				$post['date_posted'] = time();
				
				$posts->addPost($post);
			}
			else $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);			
		}
			
		return ($msg);			
	}
	
	public function updateTopic($topic_id, $data)
	{
		unset($data['id']);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_TOPICS,'rowClass'=>'Topic'));
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$topic = $table->find($topic_id)->current();
			$topic->setFromArray($data);
			$topic->save();
			$msg = $tr->translate(PrecurioStrings::EDITSUCESS);			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
		return $msg;
	}
	
	
	/**
	 * @param $topic_id
	 * @return Topic
	 */
	public static function getTopic($topic_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_TOPICS, 'rowClass'=>'Topic'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::FORUM_TOPICS))
			->join(array('b' => PrecurioTableConstants::USERS), 'a.creator = b.user_id', array('first_name', 'last_name'))	
			->where("a.id = {$topic_id} AND a.active = 1");				
				
		//return $select->__toString();		
		return $table->fetchRow($select);	
	}
	
	public function deleteTopic($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_TOPICS));
		$tr = Zend_Registry::get('Zend_Translate');	
			
		try
		{
			$data['active'] = 0;
			$this->updateTopic($id, $data);
				
			$msg = $tr->translate(PrecurioStrings::DELETESUCCESS);
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
			
		return ($msg);
	}
	
	
		
}
?>