<?php
require_once ('visitor/models/vo/Visitor.php');
require_once ('visitor/models/Visitors.php');
require_once ('contact/models/Contacts.php');
require_once ('visitor/models/VisitorUtil.php');
require_once ('admin/controllers/BaseController.php');
class Admin_VisitorController extends Admin_BaseController {
	
	function generateHeader() {
		return array('',$this->translate('Visitor Name'),$this->translate('Host'), $this->translate('Visitor Company'));
	}

	function generateList($searchText) 
	{
		$searchText = strtolower($searchText);
			
		$visitors = VisitorUtil::getAllVisitors();
		
		$arr = array();
		$i = 1;
		foreach($visitors as $visitor)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($visitor->full_name,$searchText)===FALSE)
				{
					if(stripos($visitor->last_name,$searchText)===FALSE)
					{
						if(stripos($visitor->first_name,$searchText)===FALSE)
						{
							if(strtolower($visitor->company) != $searchText)
							{
								continue;
							}
						}
					}
				}
				
			}
			
			$arr[] = array($i++,'full_name'=>$visitor->full_name,'for'=>$visitor->getHost(),'company'=>$visitor->company, 'id'=>$visitor->id);
		}
		return $arr;
	}
	
	function getForm($user = null, $viewMode = false) 
	{
		
	}
	
	function getPageTitle() {
		return $this->translate("Visitor Management");
	}
	
	function getTableName() 
	{
		return PrecurioTableConstants::VISITOR;
	}
	
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$visitors = new Visitors();
		
		$item = $visitors->getVisitor($id);	
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getViewForm($item);
		
		$this->view->visitor = $item;
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		
		
	}
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$visitors = new Visitors();
		
		$item = $visitors->getVisitor($id);	
		if($item == null)return $this->_forward('index');
		
		$this->view->edtop = 1;
		$this->view->visitor = $item;		
		
		$this->view->form = $this->getViewForm($item);
				
		$this->view->pageTitle = $this->view->translate($this->getPageTitle());
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		$this->render('view');
	}
	
	public function addAction()
	{
		$this->view->form = $this->getAddForm();
		$this->view->pageTitle = $this->getPageTitle()." : ".$this->translate("Add new");		
	}
	
	public function addreceptionistAction()
	{
		$this->view->pageTitle = $this->getPageTitle()." : ".$this->translate("Add Receptionist");
		
		$groups = array();
		$ids = VisitorUtil::getReceptionistGroups();
		
		foreach($ids as $id)
			$groups[] = UserUtil::getGroup($id);
		
		$this->view->groups = $groups;		
	}
	
	public function selectcontactAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$ns = new Zend_Session_Namespace('temp');
				
		$ns->id = 0;
		$ns->selectedUsers = array();//clear the user select dialog.
		$ns->callback = $this->getRequest()->getBaseUrl()."/visitor/ajax/selectcontact";//needed for the user select dialog
		$ns->selectLabel = 'Selected Contact (Only 1 contact allowed)'; // translate later
		//echo "loadSelectPopup()";
	}
	
	public function selectreceptionistAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$ns = new Zend_Session_Namespace('temp');
				
		$ns->id = $this->getRequest()->getParam('g_id');
		$ns->selectedUsers = array();//clear the user select dialog.
		$ns->callback = $this->getRequest()->getBaseUrl()."/visitor/ajax/addreceptionist";//needed for the user select dialog
		$ns->selectLabel = 'Selected receptionists'; // translate later
		//echo "loadSelectPopup()";
	}
	
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$values = $this->getRequest()->getParams();
		
		
		
		$visitors = new Visitors();
		$contacts = new Contacts();

		$contact['full_name'] = $values['full_name'];
		$contact['user_id'] = $values['user_id'];
		$contact['company'] = $values['company'];
		
		$date = new Precurio_Date();
		$date->setMonth($values['dob_month']);
		$date->setDay($values['dob_day']);
		$date->setYear($values['dob_year']);
		
		$values['DOB'] = $date->getTimestamp();
		$contacts->updateContact($values['contact_id'], $contact);
		
		$visitors->updateVisitor($values['id'], $values);
		return $this->_forward('index');
	}
	
	public function addgroupAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS));
		$date = Precurio_Date::now();
		
		$data['title'] = $this->getRequest()->getParam('g_name');
		$data['is_role'] = 1;
		$data['date_created'] = $date->getTimestamp();
		
		$row = $table->createRow($data);
		$id = $row->save();
		
		if( $id )
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
			
			$data['title'] = $this->getRequest()->getParam('g_name');
			$data['group_id'] = $id;
			$data['date_created'] = $date->getTimestamp();
			
			$row = $table->createRow($data);
			$id = $row->save();
		
			if( $id )
			{
				$privileges = array('null', 'add', 'view', 'delete', 'edit', 'details', 'share');
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RULES));
			
				foreach($privileges as $privilege)
				{					
					$data['role_id' ] = $id;
					$data['resource'] = 'visitor_telephone'; // this is used to represent the receptionist resource
					$data['privilege'] = $privilege;				
					$data['allow'] = ($privilege == 'null') ? 0 : 1;
					$data['created_by'] = Precurio_Session::getCurrentUserId();
					$data['date_created'] = $date->getTimestamp();
				
					$row = $table->createRow($data);
					$row->save();
				}
			}
			
		}		
		
	}	
	
	public function submitnewAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$data = $this->getRequest()->getParams();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR));
		$msg = "";
		try
		{
			$data['contact_id'] = $data['visitor'];
			if(Precurio_Utils::isNull($data['contact_id']))
			{				
				//$contact = explode(',', $data[contact_id);
				$vdata['full_name'] = $data['full_name'];
				$vdata['user_id'] = $data['contact'];
				$vdata['company'] = $data['company'];
					
				$data['contact_id'] = VisitorUtil::addUserContact($vdata);
			}
			
			$date = new Precurio_Date();
			$date->setMonth($data['dob_month']);
			$date->setDay($data['dob_day']);
			$date->setYear($data['dob_year']);
			
			$data['DOB'] = $date->getTimestamp();
			
			/* validate no visitor curently exists with as selected contact */
			$result = $table->fetchAll($table->select()
				->where('contact_id= ? ',$data['contact_id']));
			
			if(count($result) > 0) return $this->_redirect('/admin/visitor'); 
				
			/* Add visitor if it doesnt exist */
			$row = $table->createRow($data);
			$id = $row->save();
			if($id)
			{
				//Precurio_Activity::newActivity($data['target'],Precurio_Activity::TELEPHONE_MESSAGE_LOGGED,$id,'')	;
				$msg = $this->translate(PrecurioStrings::ADDSUCESS);
			}
			else $msg = $id;
						
		}
		catch (Exception $e)
		{
			$msg = 'Error Performing operation';		
		}
			
		
		return $this->_redirect('/admin/visitor');
	}
	
	
	
	function getAddForm()
	{		
		$form = new Zend_Form();
		$form->setMethod('post');
		
				
		$contact = Precurio_Session::getCurrentUserId();
		$contact_name = UserUtil::getUser($contact)->getFullName();
		
		$form->addElement('hidden', 'contact', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $contact,
				));
		
		$form->addElement('text', 'contact_name', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $contact_name,
				'disabled' => 'disabled',
				));
		
			$form->addElement('select', 'dob_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
						
				));
				
		$form->addElement('select', 'dob_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        
				));
		$form->addElement('select', 'dob_day', array(
				'required' => true,

				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				
				));
				
		$form->addElement('text', 'emergency_contact', array(
				'validators' => array(
				),				
				'required' => true,
				
				));	
				
		$form->addElement('text', 'car_reg_number', array(
				'validators' => array(
				),				
				'required' => true,
				
				));

	$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
	return $form;
	}
	
	function getViewForm($visitor)
	{
		$form = new Zend_Form();
		$form->setMethod('post');
			
		
		$dob = Precurio_Utils::isNull($visitor->DOB) ? Zend_Date::now() : new Zend_Date($visitor->DOB);
		
		$contact = Precurio_Session::getCurrentUserId();
		$contact_name = UserUtil::getUser($visitor->user_id)->getFullName();
		
		$form->addElement('hidden', 'contact', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $contact,
				));
		
		$form->addElement('text', 'contact_name', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $contact_name,
				'disabled' => 'disabled',
				));
				
		$form->addElement('select', 'dob_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$dob->get(Precurio_Date::YEAR)					
				));
				
		$form->addElement('select', 'dob_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$dob->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'dob_day', array(
				'required' => true,

				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$dob->get(Precurio_Date::DAY)
				));
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		
		return $form;
	}
	
	
}

?>