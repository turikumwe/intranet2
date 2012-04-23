<?php
require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('user/models/vo/UserActivity.php');
class Comment extends Zend_Db_Table_Row_Abstract{
	
	public function getProfilePicture()
	{
		return $this->image_path;
	}
	public function getFullName()
	{
		return $this->first_name . ' ' . $this->last_name;
	}
	public function getMessage()
	{
		return $this->message;
	}
	public function getSinceWhen()
	{
		$date = new Precurio_Date($this->date_created);
		return $date->getHowLongAgo();
		
	}
	/**
	 * Creates a new comment, and inserts into database
	 * @param $user_id
	 * @param $comment
	 * @param $activity_id
	 * @return int comment_id if successful
	 */
	public function createNew($user_id,$comment,$activity_id,$content_id)
	{
		//insert into comments table
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS));
		$date_created = Precurio_Date::now()->getTimestamp(); 
		$data = array('user_id'=>$user_id,'message'=>$comment,'activity_id'=>$activity_id,'content_id'=>$content_id,'date_created'=>$date_created);
		$comment_id = $table->insert($data);
		
		//get the activity we are commenting on. This is where we will get the url from.
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY,'rowClass'=>'UserActivity'));
		$row = $table->fetchRow($table->select()->where('id = ?',$activity_id));
		if($activity_id == 0)
		{
			$row = $table->fetchRow($table->select()->where('activity_id = ?',$content_id));
		}
		
		//inserts into activity table
		if($row != null)Precurio_Activity::newActivity($user_id,Precurio_Activity::ADD_COMMENT,$comment_id,$row->getMessageUrl(),$row->id);

		return $comment_id;
	}
	/**
	 * Gets a comment
	 * @param $id
	 * @return Comment
	 */
	public static function getComment($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS, 'rowClass'=>'Comment'));
		$select  = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->from(array('a' => PrecurioTableConstants::COMMENTS))
						->join(array('b' => PrecurioTableConstants::USERS),'a.user_id = b.user_id',array('first_name','last_name','profile_picture_id','gender'))
						->where('a.id = ?',$id);
		return $table->fetchRow($select);
	}

}

?>