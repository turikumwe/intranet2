<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('contact/models/Contacts.php'); 
require_once('contact/models/vo/Contact.php');
class Contact_IndexController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$params = $this->getRequest()->getParams();
		if(isset($params['id']))
			$this->view->id = $params['id'];
	}
	public function viewAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->contact_id = $this->getRequest()->getParam('id');
		$this->view->type = $this->getRequest()->getParam('type');
	}
	public function addAction()
	{
		$this->_helper->layout->disableLayout();
		//$this->view->contact_id = $this->getRequest()->getParam('id');
		$this->view->form = $this->getForm();
	}
	public function editAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		$contact_id = $this->getRequest()->getParam('id');
		$this->view->contact_id = $contact_id;
		$contact = Contacts::getContact($contact_id);
		$this->view->form = $this->getForm($contact);
		$this->render('add');
	}
	public function deleteAction()
	{
		$id = $this->getRequest()->getParam('id');
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$contacts = new Contacts();
		$msg = $contacts->deleteContact($id);
		$dict = new Precurio_Search();
		$dict->unIndexContact($id);
		echo $msg;
	}
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$params = $this->getRequest()->getParams();
		
		if (!$this->getRequest()->isPost()) {
			$this->_redirect('/contact/index/index/');
			return;
		}
		$form = $this->getForm();
		if (!$form->isValid($_POST))
		{
			echo $this->view->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			return;
		}
		$values = $form->getValues();
		$contacts = new Contacts();
		if(Precurio_Utils::isNull($values['id']))
		{
			
			$result = $contacts->addContact($values);
		}
		else
		{
			$result = $contacts->updateContact($values['id'],$values); 
		}
		
		echo $result;
		
	}
	public function vcardAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$contact_id = $this->getRequest()->getParam('id');
		$contact = Contacts::getContact($contact_id);
		
		$v = new Precurio_vCard();
		$v->setPhoneNumber($contact->mobile_phone, "CELL");
		$v->setName($contact->full_name, "", "", "","");
		$v->setAddress("", "", $contact->address, "", "", "", "","HOME");
		$v->setEmail($contact->email);
		$v->setURL($contact->website, "WORK");
		
		$output = $v->getVCard();
		$filename = $v->getFileName();
		$this->getResponse()->setHeader('Content-Description','File Transfer');
		$this->getResponse()->setHeader('Content-Disposition',"attachment; filename=precurio_contact.vcf");
		$this->getResponse()->setHeader('Content-Type','text/vCard');
		$this->getResponse()->setHeader('Content-Transfer-Encoding','binary');
		echo $output;
	}
	/**
	 * @param $contact Contact
	 * @return Zend_Form
	 */
	private function getForm($contact = null)
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/contact/index/submit')
			->setMethod('post')
			->setAttrib('enctype', 'multipart/form-data');

		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact['id'],
				));
		$form->addElement('text', 'full_name', array(
		'validators' => array(
				),
				'required' => true,		
				'value'=>$contact['full_name'],
				));
		$form->addElement('text', 'company', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact['company'],
				));
		$form->addElement('text', 'job_title', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact['job_title'],
				));
		$form->addElement('textarea', 'address', array(
				'validators' => array(
				),
				'rows'=>4,
				'required' => false,
				'value'=>$contact['address'],
				));
				
		$form->addElement('text', 'work_phone', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact['work_phone'],
				));

		
		$form->addElement('text', 'mobile_phone', array(
			'validators' => array(
				),
				'required' => false,
				'value'=>$contact['mobile_phone'],
				));		
		
		
		$form->addElement('text', 'fax', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact['fax'],
				));	
		$form->addElement('text', 'email', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact['email'],
				));	
		$form->addElement('text', 'website', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact['website'],
				));	
		$form->addElement('checkbox', 'shared', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact['shared'],
				));	
				
		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$contact == null ? Precurio_Date::now()->getTimestamp() : $contact['date_created'],
				));
		
		
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		return $form;
		
	}
}
?>