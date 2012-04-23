<?php

require_once ('forum/models/Posts.php');
		
class Post extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Gets the id of the post
	 * @return id
	 */
	public function getId()
	{
		return $this->id;
	}
	
		
	/**
	 * Gets the content of the post
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}
	
	/**
	 * Gets the name of the person that added the post
	 * @return string
	 */	
	public function getCreator()
	{
		return UserUtil::getUser($this->user_id)->getFullName();
	}
/**
	 * Returns the user_id of the person who submited this post
	 * @return int
	 */
	
	public function getCreatorId()
	{
		return $this->user_id;
	}
	/**
	 * Gets the image thumbnail path for the user that added the post
	 * @return string
	 */
	public function getCreatorImagePath()
	{
		return Precurio_Image::getPath(UserUtil::getUser($this->user_id)->getProfilePicture(),Precurio_Image::IMAGE_ICON);
	}
	
	/**
	 * Gets the date the post was added
	 * @return string
	 */
	public function getDatePosted()
	{
		$date = new Precurio_Date($this->date_posted);
				
		return $date->get(Precurio_Date::DATE_SHORT).' '.$date->get(Precurio_Date::TIME_SHORT);
	}
	
	/**
	 * Checks if user has right to alter the post
	 * @return boolean
	 */
	public function canAlter()
	{
		return $this->user_id == Precurio_Session::getCurrentUserId();
	}
	
	/**
	 * Checks if post has been viewed or not
	 * @return boolean
	 */
	public function hasBeenViewed()
	{
		return $this->viewed;
	}
	
	/**
	 * Marks a post as viewed
	 * @return null;
	 */
	public function markAsViewed()
	{
		$posts = new Posts();
		
		$posts->updatePost($this->getId(), array('viewed'=>1) );
	}
	
}
?>