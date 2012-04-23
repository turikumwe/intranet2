<?php

/**
 * ListController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('contact/models/Contacts.php'); 
require_once('contact/models/file_db.php'); 
require_once('contact/models/vo/Contact.php');
require_once('cms/models/vo/Content.php');
require_once('employee/models/Employees.php');
class Contact_ListController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
	}
	public function allAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->type = $this->getRequest()->getParam('type');
		$this->view->page = $this->getRequest()->getParam('page');
	}
	public function exportAction()
	{
		$this->_helper->layout->disableLayout();
	}
	public function doexportAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$type = $this->getRequest()->getParam('contactType','my');
		
		
		if($type == 'my')
		{
			$contacts = new Contacts();
			$myContacts = $contacts->getMyContact();
		}
		else
		{
			$employees = new Employees();
			$myContacts = $employees->getAll(true);
		}
		
		$data = '"'.$this->view->translate('Full Name').'","'.$this->view->translate('Company').'","'.$this->view->translate('E-mail Address').'","'.
		$this->view->translate('Job Title').'","'.$this->view->translate('Business Address').'","'.$this->view->translate('Mobile Phone').'","'.
		$this->view->translate('Business Phone').'","'.$this->view->translate('Business Fax').'","'.$this->view->translate('Web Page').'"'."\n";
		foreach($myContacts as $contact)
		{
			$data = $data.$contact->toCSVString()."\n";	
		}
		
		$this->getResponse()->clearBody();
//		$this->getResponse()->setHeader('Cache-Control','no-cache, must-revalidate'); // HTTP/1.1 
//		$this->getResponse()->setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$this->getResponse()->setHeader('Content-Description','File Transfer');
		$this->getResponse()->setHeader('Content-Disposition','attachment; filename=precurio_contact.csv');
		$this->getResponse()->setHeader('Content-Type','text/csv');
		$this->getResponse()->setHeader('Content-Transfer-Encoding','binary');
		echo trim($data);
	}
	public function uploadAction()
	{
		$this->_helper->layout->disableLayout();
	}
	public function importAction()
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
		if(strtolower(substr($_FILES['file']['name'],-3))!== 'csv')
			$error =  $this->view->translate('Unsupported file format');
		if($error != "" )
		{
			return;
		}
		$root = Zend_Registry::get('root');
		$upload = new Precurio_Upload;
		$upload->uploadFile(Content::PATH_TMP);
		$filePath = $root.'/public/'.Content::PATH_TMP.$upload->_files['file'];
		
		$user_id = Precurio_Session::getCurrentUserId();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS));
		
		//Create new recordset
		$rec = new file_db;
		$rec->filename = $filePath;
		
		//Open File and load first recordset
		$rec->open_db();
		while (!$rec->EOF)
		{
			$data['full_name'] = $rec->record["Title"]." ".$rec->record["First Name"]." ".$rec->record["Middle Name"]." ".$rec->record["Last Name"];
			$data['company'] = $rec->record["Company"];
			$data['job_title'] = $rec->record["Job Title"];
			$data['email'] = $rec->record["E-mail Address"];
			$data['website'] = $rec->record["web Page"];
			$data['address'] = $rec->record["Business Street"]." ".$rec->record["Business Street 2"]." ".$rec->record["Business Street 3"]
			." ".$rec->record["Business City"]." ".$rec->record["Business State"]." ".$rec->record["Business Postal Code"]
			." ".$rec->record["Business Country/Region"];
			$data['work_phone'] = $rec->record["Business Phone"].",".$rec->record["Business Phone 2"];
			$data['mobile_phone'] = $rec->record["Mobile Phone"];
			$data['fax ']= $rec->record["Business Fax"];
			if(strlen($data['full_name']) > 6)
			{
				$data['user_id'] = $user_id;
				$row = $table->createRow($data);
				$row->save();
			}
			$rec->move_next();
		}
		
		$obj = new stdClass();
    	$obj->error = $error;
    	$obj->filePath = $filePath;
    	$obj = Zend_Json::encode($obj);
    	echo $obj;
	}
	public function preDispatch()
	{
	}

}
?>