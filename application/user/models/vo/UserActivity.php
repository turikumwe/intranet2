<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('event/models/vo/Event.php');
require_once ('event/models/Events.php');
require_once ('task/models/vo/Task.php');
require_once ('task/models/Tasks.php');
require_once ('contact/models/Contacts.php');
require_once ('cms/models/MyContents.php');
require_once ('forum/models/Topics.php');
require_once ('forum/models/Forums.php');
class UserActivity extends Zend_Db_Table_Row_Abstract {
	
	public function getProfilePicture()
	{
		return UserUtil::getUser($this->user_id)->getProfilePicture();
	}
	
	public function getFullName()
	{
		return $this->first_name . ' ' . $this->last_name;
	}
	public function getObjectUrl()
	{
		$config = Zend_Registry::get('config');
		$url = $config->base_url.'/user/profile/view/'.$this->user_id;
		return $url;
	}
	/**
	 * Gets the activity message in first person format, typically for use on profile page
	 * @param $viewPoint int determines what gramitical person. 1= first person (default), 2= second person, 3= third person 
	 * @return String
	 */
	public function getMessage($viewPoint = 1)
	{
		$translate = Zend_Registry::get('Zend_Translate');
		$config = Zend_Registry::get('config');
		if($viewPoint == 1)$name = ' ';//first person
		if($viewPoint == 2)$name = $translate->translate('You');//second person
		if($viewPoint == 3)$name = $this->getFullName();//third person

		$format = Precurio_Activity::getMessageFormat($this->type);
		if($this->type == Precurio_Activity::CHANGE_STATUS)//special case 1 ->status messages
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::STATUS_MESSAGES, 'rowClass'=>'UserStatus'));
			$row = $table->find($this->activity_id)->current();
			//pass in the status message has the pronoun. The same idea is used in ADD_COMMENT, where the comment is passed as the pronoun
			return sprintf($format,$name,$row->getMessage(),$config->base_url.'/user/profile/view/'.$row->user_id,'',$row->user_id,$this->subject_id,$config->base_url);
		}
		if($this->type == Precurio_Activity::ADD_COMMENT)//special case 1 ->status messages
		{
			$comment = Comment::getComment($this->activity_id);
			//pass in the comment message has the pronoun. 
			return sprintf($format,$name,$comment->getMessage(),$this->getMessageUrl(),$this->getSubject(),$this->user_id,$this->subject_id,$config->base_url,$this->getLabel());
		}
		return sprintf($format,$name,$this->getGenderPronoun(),$this->getMessageUrl(),$this->getSubject(),$this->user_id,$this->subject_id,$config->base_url,$this->getLabel());//$this->_activity->message is specific to status messages;
	}
	
	public function getSinceWhen()
	{
		$date = new Precurio_Date($this->date_created);
		return $date->getHowLongAgo();
		
	}
	public function getComments()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS, 'rowClass'=>'Comment'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::COMMENTS))
						->join(array('b' => PrecurioTableConstants::ACTIVITY),'a.activity_id = b.id',array('actual_activity_id'=>'b.activity_id','type'))
						->join(array('c' => PrecurioTableConstants::USERS),'c.user_id = a.user_id',array('first_name','last_name','profile_picture_id'))
						->join(array('d' => PrecurioTableConstants::PROFILE_PICS),'c.profile_picture_id = d.id',array('image_path'))
						->where('a.activity_id= ? ',$this->id)
						->order('id ASC');
		
		$all = $table->fetchAll($select);
		return $all;
	}
	
	/**
	 * Determines where $user_id should be able to see this activity
	 * @param $user_id
	 * @return Boolean
	 */
	public function canSee($user_id)
	{
		if(!Precurio_Activity::isPrivate($this->type))
			return true;//the activity is not private.
		//ok, it is a private activity, lets see if $user_id has access.
		switch ($this->type)
		{
			case Precurio_Activity::ADD_EVENT:
			case Precurio_Activity::ADD_EVENT_PHOTO:
			case Precurio_Activity::EDIT_EVENT:	
				$event = Events::getEvent($this->activity_id);
				if($event == null)
					return false;
				else
					return $event->userIsInvited($user_id);
			case Precurio_Activity::ADD_COMMENT:
				//the subject_id of a comment is always the activity on which the comment is made on
				$activity = self::getUserActivity($this->subject_id);
				return $activity->canSee($user_id);
			case Precurio_Activity::NEW_TASK:
			case Precurio_Activity::EDIT_TASK:
			case Precurio_Activity::COMPLETE_TASK:
			case Precurio_Activity::FAILED_TASK:
				$task = Tasks::getTask($this->activity_id);
				if($task == null)
					return false;
				else
					return $task->canAccess($user_id);
			case Precurio_Activity::NEW_CONTENT:
			case Precurio_Activity::EDIT_CONTENT:
				$content = MyContents::getContent($this->activity_id);
				if($content == null)
					return false;
				else
					return $content->canAccess($user_id);
			case Precurio_Activity::APPOINTMENT_ADDED:
				return ($user_id == $this->user_id || $user_id == $this->subject_id);
			case Precurio_Activity::RESET_PASSWORD:
				return $user_id == $this->user_id;
				case Precurio_Activity::NEW_USER:
				return $user_id == $this->user_id;
		}	
		return false;//default is no access.
	}
	
	private function getGenderPronoun($thirdPerson = false)
	{
		$translate = Zend_Registry::get('Zend_Translate');
		if(strtolower($this->gender) == 'male' )
		{
			if($thirdPerson)
				return $translate->translate('him');
			else
				return $translate->translate('his');
		}
		return $translate->translate('her');
	}
	//%1\$s == name of actor
	//%2\$s == pronoun i.e his or her
	//%3\$s == url
	//%4\$s == name of subject.
	public function getMessageUrl()
	{
		$config = Zend_Registry::get('config');
		$url = $config->base_url;
		
		switch ($this->type)
		{
			case Precurio_Activity::ADD_PROFILE_PICTURE:
			case Precurio_Activity::CHANGE_PROFILE_PICTURE:
			case Precurio_Activity::CHANGE_PHONE_NUMBER:
			case Precurio_Activity::CHANGE_LOCATION:
			case Precurio_Activity::CHANGE_JOB_TITLE:
			case Precurio_Activity::CHANGE_DEPARTMENT:
			case Precurio_Activity::CHANGE_STATUS:
				$url .= '/user/profile/view/'.$this->user_id;
				break;
			case Precurio_Activity::ADD_COMMENT:
				$url = $this->url;
				//we dont need to prefix with base url here because a comment always 
				//links to an existing activity , which would have already been 
				//prefixed when gotten). 
				break;
			case Precurio_Activity::ADD_EVENT:
			case Precurio_Activity::EDIT_EVENT:
			case Precurio_Activity::ADD_EVENT_PHOTO:
				$url .= '/event/'.(Events::getEvent($this->activity_id)->isPast() ? 'past' : 'upcoming'). '/details/e_id/'.$this->activity_id;
				break;
			case Precurio_Activity::SHARED_CONTACT:
				$url .= '/contact/index/index/id/'.$this->activity_id;
				break;
			case Precurio_Activity::NEW_TASK:
			case Precurio_Activity::EDIT_TASK:
			case Precurio_Activity::COMPLETE_TASK:
			case Precurio_Activity::FAILED_TASK:
				$url .= '/task/index/index/id/'.$this->activity_id;
				break;
			case Precurio_Activity::RESET_PASSWORD:
				$url = $this->url;
				break;
			case Precurio_Activity::NEW_USER:
				$url = $this->url;
				break;
			default:
				$url .= $this->url;
		}
		return $url;
	}
	/**
	 * Returns a label for the activity, eg for an event, it returns the name of the event, for a content, it returns the name of the content
	 * @return string
	 */
	public function getLabel()
	{
		switch ($this->type)
		{
			case Precurio_Activity::ADD_EVENT:
			case Precurio_Activity::EDIT_EVENT:
			case Precurio_Activity::ADD_EVENT_PHOTO:
			case Precurio_Activity::EVENT_INVITED:
				$event = Events::getEvent($this->activity_id);
				if($event == null)return "";
				return $event->getTitle();
			case Precurio_Activity::NEW_TASK:
			case Precurio_Activity::EDIT_TASK:
			case Precurio_Activity::COMPLETE_TASK:
			case Precurio_Activity::FAILED_TASK:
			case Precurio_Activity::TRANSFER_TASK:
				$task = Tasks::getTask($this->activity_id);
				if($task ==null)return "";
				return $task->getTitle();
			case Precurio_Activity::SHARED_CONTACT:
				$contact = Contacts::getContact($this->activity_id);
				if($contact == null)return "";
				return $contact->getFullName();
			case Precurio_Activity::NEW_CONTENT:
			case Precurio_Activity::EDIT_CONTENT:
				$content = MyContents::getContent($this->activity_id);
				if($content == null)
					return false;
				return $content->getTitle();
			case Precurio_Activity::FORUM_ADDED:
				$forum = Forums::getForum($this->activity_id);
				return $forum->getTitle();
			case Precurio_Activity::TOPIC_ADDED:
				$topic = Topics::getTopic($this->activity_id);
				return $topic->getTitle();
			case Precurio_Activity::NEW_POST:
				$topic = Topics::getTopic($this->activity_id);
				return $topic->getTitle();
			case Precurio_Activity::ADD_COMMENT:
				//the subject_id of a comment is always the activity on which the comment is made on
				$activity = self::getUserActivity($this->subject_id);
				return $activity->getLabel();
				
				
		}
		$tr = Zend_Registry::get('Zend_Translate');
		return $this->type == Precurio_Activity::ADD_COMMENT ? $tr->translate("one of your activities") : "";
		
	}
	/**
	 * Returns the full name of the user
	 * @return string
	 */
	private function getSubject()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS));
		$user = $table->fetchRow($table->select()->where('user_id= ? ',$this->subject_id));
		return $user == null ? '' : $user->first_name.' '.$user->last_name;
	}
	/**
	 * Gets a user activity
	 * @param int $id primary key of the activity
	 * @return UserActivity
	 */
	public static function getUserActivity($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY, 'rowClass'=>'UserActivity'));
		$select  = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->from(array('a' => PrecurioTableConstants::ACTIVITY))
						->join(array('b' => PrecurioTableConstants::USERS),'a.user_id = b.user_id',array('first_name','last_name','profile_picture_id','gender'))
						->where('a.id = ?',$id);
		$activity = $table->fetchRow($select);
		return $activity;
	}
}

?>