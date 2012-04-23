<?php

/**
 * ContentController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('event/models/Events.php');
class Event_ContentController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
	}
    public function addAction()
    {
    	$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
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
			$id = $this->getRequest()->getParam('id');
			if(stristr($_FILES['file']['type'],'image')===false)
	    	{
	    		$filePath = $this->addResource($id);
	    	}
	    	else
	    	{
	    		$filePath = $this->addPhoto($id);
	    	}
		}
    	
    	$obj = new stdClass();
    	$obj->error = $error;
    	$obj->filePath = $filePath;
    	$obj = Zend_Json::encode($obj);
    	echo $obj;
    }
    private function addPhoto($event_id)
    {
    	$filePath = Content::addPhoto();
    	
    	$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT));
    	$date_created = Precurio_Date::now()->getTimestamp();
    	$data = array(
    	'image_path'=>$filePath,
    	'is_photo'=>1,
    	'date_created'=>$date_created,
    	'active'=>1,
    	'user_id'=>Precurio_Session::getCurrentUserId()
    	);
    	$row = $table->createRow($data);
    	$content_id = $row->save();
    	
    	$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::EVENT_CONTENTS));
    	$data = array(
    	'event_id'=>$event_id,
    	'date_created'=>$date_created,
    	'content_id'=>$content_id
    	);
    	$row = $table->createRow($data);
    	$row->save();
    	
    	$url = '/event/content/viewphoto/e_id/'.$event_id;
		Precurio_Activity::newActivity(Precurio_Session::getCurrentUserId(),Precurio_Activity::ADD_EVENT_PHOTO,$event_id,$url);
    	
		return $filePath;
    }
    private function addResource($event_id)
    {
    	
    }
    
    public function photosAction()
    {
    	$id = $this->getRequest()->getParam('id');
    	$this->view->event = Events::getEvent($id);
    	$this->view->cpage = $this->getRequest()->getParam('cpage');
    }
    public function viewphotoAction()
    {
    	$id = $this->getRequest()->getParam('e_id');
    	$pos = $this->getRequest()->getParam('pos');
    	$event = Events::getEvent($id);
    	$photos = $event->getPhotos();//user has been set in the ProfileController
		$total = count($photos);
		if($pos > $total)$pos = $total;//get the last photo;
		if($pos < 1)$pos=1;//get the first photo
		
		if(!isset($photos[$pos-1]))//photo does not exist
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::MISSINGCONTENT),Precurio_Exception::EXCEPTION_MISSING_CONTENT);
		$photo = $photos[$pos-1];
		$obj = new stdClass();
		$obj->position = $pos;
		$obj->total = count($photos);
		$obj->photo = $photo;//Content
		$this->view->photoDetails = $obj;
		$this->view->event = $event;
		
		$ns = new Zend_Session_Namespace('temp');
		$ns->id = $photo->getId();
		$ns->selectedUsers = $photo->getSharedUsers();//array
    }
	public function preDispatch()
	{
		$ns = new Zend_Session_Namespace('temp');
		$this->view->page = $ns->page;
		$ns->callback = $this->getRequest()->getBaseUrl()."/cms/user/share";//needed for the user select dialog
		$ns->selectedUsers = null;//clear the user select dialog.
	}
}
?>