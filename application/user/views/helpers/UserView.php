<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/View/Interface.php';
require_once 'user/models/vo/User.php';
require_once 'user/models/UserUtil.php';
/**
 * UserView helper
 *
 * @uses viewHelper Zend_View_Helper
 */
class User_View_Helper_UserView {
	
	/**
	 * @var UserUtil
	 */
	public $userUtil;
	/**
	 * @var User
	 */
	private $_currentUser;
	/**
	 * @var Zend_View_Interface 
	 */
	public $view;
	
	/**
	 *  
	 */
	public function userView() {
		$this->userUtil = new UserUtil();
		return $this;
	}
		
	/**
	 * @return current User
	 */
	public function currentUser()
	{
		if(Precurio_Utils::isNull($this->_currentUser))
			$this->_currentUser = UserUtil::getUser(Precurio_Session::getCurrentUserId());;
		
		return $this->_currentUser;
	}
	public function getPhotoDetails($pos)
	{
		$photos = $this->view->user->getPhotos();//user has been set in the ProfileController
		$total = count($photos);
		if($pos > $total)$pos = $total;//get the last photo;
		if($pos < 1)$pos=1;//get the first photo
		$tr = Zend_Registry::get('Zend_Translate');
		if(!isset($photos[$pos-1]))//photo does not exist
			throw new Precurio_Exception($tr->translate(PrecurioStrings::MISSINGCONTENT),Precurio_Exception::EXCEPTION_MISSING_CONTENT);
		
		$obj = new stdClass();
		$obj->position = $pos;
		$obj->total = count($photos);
		$obj->photo = $photos[$pos-1];
		return $obj;
	}
//	public function getPhotoDetails($photo_id)
//	{
//		$photos = $this->view->user->getPhotos();//user has been set in the ProfileController
//		for($i=0;$i<count($photos);$i++  )
//		{
//			$photo = $photos[$i];
//			if($photo->id == $photo_id)
//				break;
//		}
//		$obj = new stdClass();
//		$obj->position = $i+1;
//		$obj->total = count($photos);
//		$obj->photo = $photo;
//		return $obj;
//	}
	public function getLocations($selected_id=0,$selectDefault=true)
	{
		$locations = $this->userUtil->getLocations();
		$content = "";
		$id = $selected_id == 0 ? UserUtil::getUser(Precurio_Session::getCurrentUserId())->getLocationId() : $selected_id;
		if(!$selectDefault) $id = $selected_id;
		foreach($locations as $location)
		{
			$content.="<option value = '".$location->getId().($location->getId() == $id ? "' selected='selected' " : "' " ).' >'.$location->getTitle().'</option>';
		}
		return $content;
	}
	public function getDepartments($selected_id=0,$selectDefault=true)
	{
		$departments = $this->userUtil->getDepartments();
		$content = "";
		$id = $selected_id == 0 ? UserUtil::getUser(Precurio_Session::getCurrentUserId())->getDepartmentId() : $selected_id;
		if(!$selectDefault) $id = $selected_id;
		foreach($departments as $department)
		{
			$content.="<option value = '".$department->getId().($department->getId() == $id ? "' selected='selected' " : "' " ).' >'.$department->getTitle().'</option>';
		}

		return $content;
	}
	public function getMonths($selectedMonth=0)
	{
		$content = "";
		for($i=1;$i<=12;$i++)
		{
			$content.="<option value = '".$i.($i == $selectedMonth ? "' selected='selected' " : "' " ).' >'.str_pad($i,2,'0',STR_PAD_LEFT).'</option>';
		}
		return $content;
	}
	public function getDates($selectedDate=0)
	{
		$content = "";
		for($i=1;$i<=31;$i++)
		{
			$content.="<option value = '".$i.($i == $selectedDate ? "' selected='selected' " : "' " ).' >'.str_pad($i,2,'0',STR_PAD_LEFT).'</option>';
		}
		return $content;
	}
	public function getUserThemeStyle()
	{
		$themes = new Precurio_Themes();
        
        $theme = $themes->getUserTheme();
        $style = $themes->getUserStyle($theme);
		return $theme.'/'.$style;
	}
	/**
	 * Sets the view field 
	 * @param $view Zend_View_Interface
	 */
	public function setView(Zend_View_Interface $view) {
		$this->view = $view;
	}
}
