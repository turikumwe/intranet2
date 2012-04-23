<?php
require_once ('forum/models/vo/Forum.php');
require_once ('forum/models/vo/Post.php');
require_once ('forum/models/vo/Topic.php');


class Forums
{
	public function getCount()
	{
		return count($this->getForums());
	}
	
	public function getForums()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUMS, 'rowClass'=>'Forum'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::FORUMS))
			->join(array('b' => PrecurioTableConstants::USERS), 'a.creator = b.user_id', array('first_name', 'last_name'))	
			->where("a.active = 1")
			->order("a.last_action DESC");
				
		$this->forums = $table->fetchAll($select);
		return $this->forums;
	}
	
	public function addForum($data)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUMS,'rowClass'=>'Forum'));
		$msg = "";
		try
		{
			$row = $table->createRow($data);
			$id = $row->save();
			if($id)
			{
				$url = '/forum/topic/list/fid/'.$id;
				Precurio_Activity::newActivity($data['creator'],Precurio_Activity::FORUM_ADDED,$id,$url)	;
				$msg = $tr->translate(PrecurioStrings::ADDSUCESS);
			}
			else $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);			
		}
			
		return ($msg);			
	}
	
	public function updateForum($forum_id, $data)
	{
		unset($data['id']);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUMS,'rowClass'=>'Forum'));
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$forum = $table->find($forum_id)->current();
			$forum->setFromArray($data);
			$forum->save();
			$msg = $tr->translate(PrecurioStrings::EDITSUCESS);			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
		return $msg;
	}
	
	
	
	/**
	 * @param $forum_id
	 * @return Forum
	 */
	public static function getForum($forum_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUMS, 'rowClass'=>'Forum'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::FORUMS))
			->join(array('b' => PrecurioTableConstants::USERS), 'a.creator = b.user_id', array('first_name', 'last_name'))	
			->where("a.id = {$forum_id} AND a.active = 1");				
				
		//return $select->__toString();		
		return $table->fetchRow($select);
	}
	
	
		
}
?>