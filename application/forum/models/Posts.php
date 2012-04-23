<?php
require_once ('forum/models/vo/Post.php');
require_once ('forum/models/Topics.php');
require_once ('forum/models/Forums.php');


class Posts
{
	public function getCount($topic_id)
	{
		return count($this->getPosts($topic_id));
	}
	
	public function getPosts($topic_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS, 'rowClass'=>'Post'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);			
			
		$select = $select->from(array('a' => PrecurioTableConstants::FORUM_POSTS))
			->join(array('b' => PrecurioTableConstants::USERS), 'a.user_id = b.user_id', array('first_name', 'last_name'))	
			->where("a.topic_id = {$topic_id} AND a.active = 1")
			->order("a.id ASC");
		
	
		return $table->fetchAll($select);
	}
		
	public function addPost($data)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS,'rowClass'=>'Post'));
		$msg = "";
		try
		{
			$row = $table->createRow($data);
			$id = $row->save();
			if($id)
			{
				$forum_id = Topics::getTopic($table->find($id)->current()->topic_id)->forum_id; // Get the forum id for the forum the post belongs to, this is needed to update the forum's last action date
				//$msg = $forum_id;
				$forum = new Forums();
				$forum->updateForum( $forum_id, array("last_action"=>time()) );
				
				$url = '/forum/post/index/tid/'.$row->topic_id;
				Precurio_Activity::newActivity($row->user_id,Precurio_Activity::NEW_POST,$row->topic_id,$url);
				
				$msg = $tr->translate(PrecurioStrings::ADDSUCESS);
			}
			else $msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);			
		}
			
		return ($msg);			
	}
	
	public function updatePost($post_id, $data)
	{
		unset($data['id']);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS,'rowClass'=>'Post'));
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$post = $table->find($post_id)->current();
			$post->setFromArray($data);
			$post->save();
			$msg = $tr->translate(PrecurioStrings::EDITSUCESS);			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
		return $msg;
	}
	
	public function deletePost($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS));
			
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$data['active'] = 0;
			$this->updatePost($id, $data);
				
			$msg = $tr->translate(PrecurioStrings::DELETESUCCESS);
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
			
		return ($msg);
	}
	
	
	public static function getPost($post_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_POSTS, 'rowClass'=>'Post'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::FORUM_POSTS))
			->join(array('b' => PrecurioTableConstants::USERS), 'a.user_id = b.user_id', array('first_name', 'last_name'))	
			->where("a.id = {$post_id} AND a.active = 1");				
				
		//return $select->__toString();		
		return $table->fetchRow($select);	
	}
	
	
		
}
?>