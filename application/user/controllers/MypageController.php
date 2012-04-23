<?php

/**
 * MypageController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'user/models/UserUtil.php';
require_once 'user/models/vo/OutOfOffice.php';

class User_MypageController extends Zend_Controller_Action {

	public function indexAction() {
		Precurio_Session::getLicense()->validate();
		
	}
	public function uploadphotoAction()
	{
		$this->_helper->layout->disableLayout();
	}
	public function deletephotoAction()
	{
		$content_id = $this->getRequest()->getParam('c_id');
		$content = MyContents::getContent($content_id);
		$content->active = 0;
		$content->save();
		unset($content);
		$this->_redirect('/user/profile/view/'.Precurio_Session::getCurrentUserId().'/photos');
	}
	public function addphotoAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$title = $this->getRequest()->getParam('title');
    	$error = "";
    	$filePath = "";
    	if(!isset($_FILES['file']))
    	{
    		$error = $this->view->translate('No file was uploaded');
    	}
    	

    	switch($_FILES['file']['error'])
		{

			case '1':
				$error = $this->view->translate('The uploaded file exceeds the upload_max_filesize directive in php.ini');
				break;
			case '2':
				$error = $this->view->translate('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
				break;
			case '3':
				$error = $this->view->translate('The uploaded file was only partially uploaded');
				break;
			case '4':
				$error = $this->view->translate('No file was uploaded');
				break;

			case '6':
				$error = $this->view->translate('Missing a temporary folder');
				break;
			case '7':
				$error = $this->view->translate('Failed to write file to disk');
				break;
			case '8':
				$error = $this->view->translate('File upload stopped by extension');
				break;
			case '999':
				$error = $this->view->translate('No error code avaiable');
		}
		if($error=="")
		{
			if(stristr($_FILES['file']['type'],'image')===false)
	    	{
	    		$error = $this->view->translate('Invalid image file.');
	    	}
	    	else
	    	{
	    		$filePath = $this->addPhoto($title);
	    	}
		}
    	
    	$obj = new stdClass();
    	$obj->error = $error;
    	$obj->filePath = $filePath;
    	$obj = Zend_Json::encode($obj);
    	echo $obj;
	}
 	private function addPhoto($title)
    {
    	$filePath = Content::addPhoto();
    	$user_id = Precurio_Session::getCurrentUserId();
    	$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT));
    	$date_created = Precurio_Date::now()->getTimestamp();
    	$data = array(
    	'image_path'=>$filePath,
    	'title'=>$title,
    	'is_photo'=>1,
    	'date_created'=>$date_created,
    	'active'=>1,
    	'user_id'=>$user_id
    	);
    	$row = $table->createRow($data);
    	$content_id = $row->save();
    	
    	//implement photo dir util here
    	$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::PROFILE_PICS));
    	$data = array(
    	'user_id'=>$user_id,
    	'image_path'=>$filePath,
    	'content_id'=>$content_id,
    	'date_created'=>$date_created
    	);
    	$row = $table->createRow($data);
    	$row->save();
    	
    	$url = '/user/profile/view/'.$user_id.'/photos';
		Precurio_Activity::newActivity($user_id,Precurio_Activity::ADD_PROFILE_PICTURE,$content_id,$url);
    	
		return $filePath;
    }
    public function newstatusAction()
    {
    	$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$msg = $this->getRequest()->getParam('message');

		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::STATUS_MESSAGES,'rowClass'=>'UserStatus'));
    	$data = array(
    	'user_id'=>Precurio_Session::getCurrentUserId(),
    	'message'=>$msg,
    	'group_id'=>0,
    	'content_id'=>0,
    	'date_created'=>Precurio_Date::now()->getTimestamp()
    	);
    	$row = $table->createRow($data);
    	$id = $row->save();
    	$this->view->partial()->setObjectKey('model');
		$content = $this->view->partial('partial_status.phtml','user',$row);
		Precurio_Activity::newActivity(Precurio_Session::getCurrentUserId(),Precurio_Activity::CHANGE_STATUS,$id);
		echo $content;
    }
    public function changepasswordAction()
    {
    	$params = $this->getRequest()->getParams();
   		$user = UserUtil::getUser(Precurio_Session::getCurrentUserId());
    	if(md5($params['cp']) !== $user->password)
    	{
    		$this->_helper->FlashMessenger($this->view->translate('Current password is not correct'));
    		$this->_redirect('/user/mypage/settings');
    	}
    	if($params['np'] !== $params['np2'])
    	{
    		$this->_helper->FlashMessenger($this->view->translate('Your new passwords do not match'));
    		$this->_redirect('/user/mypage/settings');
    	}
    	if( strlen($params['np']) < 6)
    	{
    		$this->_helper->FlashMessenger($this->view->translate('Your new password is invalid, enter atleast 6 characters'));
    		$this->_redirect('/user/mypage/settings');
    	}
    	$user->updatePassword($params['np']);	
    	$this->_helper->FlashMessenger($this->view->translate('You have successfuly changed your password'));
    	$this->_redirect('/user/mypage/settings');
    }
    public function settingsAction()
    {
    	$config = Zend_Registry::get('config');
    	$this->view->errorMessages = implode('<br/>',$this->_helper->flashMessenger->getMessages());
    	$util = new UserUtil();
    	
    	$user = $util->getUser(Precurio_Session::getCurrentUserId());
    	
    	$blockedUsers = $user->getSettings()->getBlockedUsers();//returns array of user id
    	//but what we need is array of user ids and full_names
    	foreach($blockedUsers as $key=>$user_id)
    	{
    		$temp  = $util->getUser($user_id);
    		$u = array('user_id'=>$temp->getId(),'full_name'=>$temp->getFullName());
    		$blockedUsers[$key] = $u;
    	}
    	
    	$this->view->blockedUsers = $blockedUsers;
    	
    	$ns = new Zend_Session_Namespace('temp');
		$ns->id = $user->getId();
		$ns->selectedUsers = $blockedUsers;//array
		$ns->selectLabel = $this->view->translate("Blocked list");
    }
    public function updatesettingsAction()
    {
    	$params = $this->getRequest()->getParams();
    	if($params['fn'] == 'locale')
    	{
    		$locale = $this->getRequest()->getParam('locale');
    		$s = new UserSetting(Precurio_Session::getCurrentUserId());
    		$s->setLocale($locale);
    	}
    	
    	if($params['fn'] == 'widgets')
    	{
    		$widgets = $this->getRequest()->getParam('widgets',array());
    		$widgets = array_flip($widgets);
    		foreach($widgets as $key=>$value)
    		{
    			$widgets[$key] = 1;
    		}
    		$s = new UserSetting(Precurio_Session::getCurrentUserId());
    		$s->setWidgets($widgets);
    	}
    	
    	if($params['fn'] == 'users')
    	{
    		$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			$blockedUsers = $params['users'];
			$blockedUsers = explode(",",$blockedUsers);
			$id = array_shift($blockedUsers);
			Precurio_Utils::debug($params['users']);
			
			$s = new UserSetting(Precurio_Session::getCurrentUserId());
    		$s->setBlockedUsers($blockedUsers);
    		
    		$util = new UserUtil();
	    	foreach($blockedUsers as $key=>$user_id)
	    	{
	    		$temp  = $util->getUser($user_id);
	    		$u = array('user_id'=>$temp->getId(),'full_name'=>$temp->getFullName());
	    		$blockedUsers[$key] = $u;
	    	}
	  
	    	$ns = new Zend_Session_Namespace('temp');
			$ns->selectedUsers = $blockedUsers;//array
			$str = "location.reload()";
			echo $str;
    		return ;
    	}
    	
    	$this->_redirect('/user/mypage/settings');
    	return;
    }
    public function setoutAction()
    {
		$redirectPage = $this->getRequest()->getParam('user_id') == Precurio_Session::getCurrentUserId() 
		? '/user/mypage/settings'   : '/admin/user/view/id/'.$this->getRequest()->getParam('user_id'); 	
    	if (!$this->getRequest()->isPost()) {
			$this->_redirect($redirectPage);
    		return;
		}
		$form = $this->getOutOfOfficeForm(0,null);
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->form = $form;
			$this->_redirect($redirectPage);
    		return;
		}
		
		$timestamp = Precurio_Date::now()->getTimestamp();
		
		$values = $form->getValues();
		$values['date_created'] = $timestamp;
		$start_date = new Precurio_Date();
		$start_date->setMonth($values['start_month']);
		$start_date->setDay($values['start_day']);
		$start_date->setYear($values['start_year']);
		$values['leave_date'] = $start_date->getTimestamp();
		unset($values['start_month']);
		unset($values['start_day']);
		unset($values['start_year']);
		
		
		$end_date = new Precurio_Date();
		$end_date->setMonth($values['end_month']);
		$end_date->setDay($values['end_day']);
		$end_date->setYear($values['end_year']);
		$values['return_date'] = $end_date->getTimestamp();
		unset($values['end_month']);
		unset($values['end_day']);
		unset($values['end_year']);
		
		if($values['return_date'] <= $values['leave_date'])
		{
			$this->_redirect($redirectPage);
    		return;
		}
		
		$values['proxy_id'] = $values['proxy_id'][0];
		$values['active'] = 1;

		if(Precurio_Utils::isNull($values['id']))
		{
			unset($values['id']);	
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::OUT_OF_OFFICE));
	    	$row = $table->createRow($values);
	    	$id = $row->save();
	    	
	    	$currentUser = UserUtil::getUser($values['user_id']);
	    	$currentUser->out_of_office = 1;
	    	$currentUser->save();
		}
		else
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::OUT_OF_OFFICE));
	    	$row = $table->find($values['id'])->current();
	    	$row->setFromArray($values);
	    	$row->save();
		}
		$this->_redirect($redirectPage);
    	return;
    }
    public function deleteoutAction()
    {
    	$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
    	$out_id = $this->getRequest()->getParam('id',0);
    	$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::OUT_OF_OFFICE));
    	$row = $table->find($out_id)->current();
    	if($row == null)
    	{
    		$this->_redirect('/user/mypage/settings');
    		return;
    	}
    	$row->active = 0;
    	$row->save();
    	
    	$currentUser = UserUtil::getUser(Precurio_Session::getCurrentUserId());
    	$currentUser->out_of_office = 0;
    	$currentUser->save();
    	
    	echo $this->view->translate('Welcome back')." {$currentUser->getFirstName()}!";
    	
    	return ;
    }
    public function outAction()
    {
    	$this->_helper->layout->disableLayout();
    	$out_id = $this->getRequest()->getParam('id',0);
    	$user_id = $this->getRequest()->getParam('u_id',Precurio_Session::getCurrentUserId());
    	
    	$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::OUT_OF_OFFICE,'rowClass'=>'OutOfOffice'));
    	$row = $table->fetchRow("id = $out_id");
    	
    	$this->view->form = $this->getOutOfOfficeForm($user_id,$row);
    }
    private function getOutOfOfficeForm($user_id,$outObj = null)
    {
    	$startDate = $outObj == null ? Precurio_Date::now() : new Precurio_Date($outObj->getLeaveDate(true));
		$endDate = $outObj == null ? Precurio_Date::now() : new Precurio_Date($outObj->getReturnDate(true) + 7200);
    	
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/user/mypage/setout')
			->setMethod('post')
			->setAttrib('id','addForm')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
			
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$outObj['id'],
				));
		$form->addElement('hidden', 'user_id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$user_id,
				));	//$user_id here is user initiating the out of office
		
		$form->addElement('select', 'start_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$startDate->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'start_day', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$startDate->get(Precurio_Date::DAY)
				));
		$form->addElement('select', 'start_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$startDate->get(Precurio_Date::YEAR)		
				));
				
		$form->addElement('select', 'end_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$endDate->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'end_day', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::DAY)
				));
		$form->addElement('select', 'end_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::YEAR)		
				));

		$form->addElement('textarea', 'summary', array(
				'validators' => array(
				),
				'filters'=>array(
				'htmlEntities'
				),
				'rows'=>5,
				'required' => false,
				'value'=>$outObj['summary']
				));
				
		$selectUser = new Zend_Form_Element_Multiselect('proxy_id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select()->where('active=1')->order('first_name asc');
		$users = $table->fetchAll($select);
		
		$proxy_id = $outObj == null ? $user_id : $outObj['proxy_id'];
		foreach($users as $user)
		{
			$selectUser->addMultiOption($user->getId(),$user->getFullName());
		}
		$selectUser->setAttrib('size',10);
		$selectUser->setValue($proxy_id);
		$selectUser->multiple = '';
		$form->addElement($selectUser);	
		
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		return $form;
    }
	public function preDispatch()
	{
		$ns = new Zend_Session_Namespace('temp');
		$ns->callback = $this->getRequest()->getBaseUrl()."/user/mypage/updatesettings";//needed for the user select dialog
		$ns->selectedUsers = null;//clear the user select dialog.
	}

}
?>