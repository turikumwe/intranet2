<?php
require_once('user/models/vo/User.php');
require_once 'workflow/models/ProcessManager.php';
class ProcessForm {
	
	/**
	 * Identifies the current state of the process
	 * @var ProcessState
	 */
	private $processState;

	/**
	 * Process Schema 
	 * @var Array 
	 */
	private $processSchema;
	
	/**
	 * @var UserProcess
	 */
	private $userProcess;
	
	public $form_id;
	public $viewMode;
	public $isMyForm;
	public $user_id;
	public $isRejected;
	public $process_id;
	/**
	 * @var Zend_Form
	 */
	protected  $_form;
	/**
	 * the header part of the form
	 * @var String 
	 */
	protected $_header;
	
	/**
	 * the footer part of the formthe footer part of the form
	 * @var String 
	 */
	protected $_footer;
	
	/**
	 * @param $processState ProcessState 
	 * @param $processSchema Simple_Xml_Element 
	 * @param $userProcess UserProcess  
	 * @param $isMyForm Boolean - flag to indicate of current form is for current user.
	 * @return Workflow_View_Helper_ProcessGenerator Object
	 */
	public function __construct($processState,$processSchema,$userProcess = null,$isMyForm = true,$viewMode = false) 
	{ 
		$this->processState = $processState;
		
		$converter = new Precurio_XmlToArray();
		$this->processSchema = $converter->GetXMLTree($processSchema->asXML());
		$this->processSchema = $this->processSchema['process'];
		
		$this->userProcess = $userProcess;
		
		$this->form_id = $userProcess == null ? 0 : $userProcess->getFormId();
		$this->isMyForm = $isMyForm;
		$this->viewMode = $viewMode;
		$this->user_id = $userProcess == null ? 0 : $userProcess->getUserId();
		$this->isRejected = $userProcess == null ? false : $userProcess->isRejected();
		$this->task_id = $userProcess == null ? 0 : $userProcess->getTaskId();
		$this->process_id = $userProcess == null ? 0 : $userProcess->getProcessId();
		
		$this->_header = $this->generateHeader();
		$this->_form = $this->generateForm($this->_form);
		$this->_footer = $this->generateFooter();
		
		return $this;
	}
	

	private function generateHeader()
	{
		$labelArr = $this->processSchema['header']['label'];
		$headerLabel = "<table class='workflow_table' cellspacing='0'>";
		if(!isset($labelArr[0]))
		{
			$labelArr = array($labelArr);
		}
		foreach($labelArr as $label)
		{
			$headerLabel .= "<tr><th scope='col' class='nobg'>";
			$headerLabel .= $label['value']. "<br/>";
			$headerLabel .= "</th></tr>";
		}
		$headerLabel .= "</table>";
		
		
		$owner =  $this->userProcess == null ? UserUtil::getUser($this->user_id) : $this->userProcess->getOwner();
		$date = $this->userProcess==null ? Precurio_Date::now() : new Precurio_Date($this->userProcess->date_created);
		
		$translate = Zend_Registry::get('Zend_Translate');
		
		$headerInfo =  "<table class='workflow_table' cellspacing='0'> 
		  <tr> 
		 
		 	   <td>".$translate->translate('Name')."</td> 
		    	<td class='alt'>{$owner->getFullName()}</td> 
		   		<td>&nbsp;</td> 
		        <td>".$translate->translate('Date of Request')."</td> 
		    	<td class='alt'>{$date}</td> 
		   		
		  </tr> 
		  
		  <tr> 
		 
		 	   <td>".$translate->translate('Job Title')."</td> 
		    	<td class='alt'>{$owner->getJobTitle()}</td> 
		   		<td>&nbsp;</td> 
		        <td>".$translate->translate('Department')."</td> 
		    	<td class='alt'>{$owner->getDepartment()}</td> 
		   		
		  </tr> 
  </table> ";
		return $headerLabel.$headerInfo;
	}
	private function generateFooter()
	{
		
	}
	private function generateForm()
	{
		$translate = Zend_Registry::get('Zend_Translate');
		$fields = $this->getFieldsSchema();
		
		$form = new Zend_Form(array('disableLoadDefaultDecorators' => true));
		
		$form->addElement(new Precurio_PlainText('headerSpace', array(
		  'decorators'=>array(
		    'ViewHelper',
		    array(array('col'=>'HtmlTag'), array('tag' => 'th','class'=>'nobg','colspan'=>'10')),
		    array(array('row'=>'HtmlTag'), array('tag' => 'tr')),
		  ),
		)));
		
		$form->addElement(new Precurio_PlainText('sectionHeader', array(
		  'decorators'=>array(
		    'ViewHelper',
		    array(array('col'=>'HtmlTag'), array('tag' => 'th','class'=>'nobg','colspan'=>'10')),
		    array(array('row'=>'HtmlTag'), array('tag' => 'tr')),
		  ),
		  'value'=>$this->processState->getDisplayName(). ' - '.$translate->translate('By').' '.(string)UserUtil::getUser($this->user_id),
		)));
		
		
		if($this->isMyForm)
		{
			$form->setAction(Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl().'/workflow/process/submit')
				->setMethod('post');
				
			$formIdField = new Zend_Form_Element_Hidden('form_id');
			$formIdField->removeDecorator('Label');
			$formIdField->setValue($this->form_id);
			$form->addElement($formIdField);
			
			$processIdField = new Zend_Form_Element_Hidden('process_id');
			$processIdField->removeDecorator('Label');
			$processIdField->setValue($this->processState->process_id);
			$form->addElement($processIdField);
			
			$stateIdField = new Zend_Form_Element_Hidden('state_id');
			$stateIdField->removeDecorator('Label');
			$stateIdField->setValue($this->processState->id);
			$form->addElement($stateIdField);
		}
		
		if($this->isRejected && $this->task_id )//note that the reject flag is only set in
		//two place. The person rejecting and the owner of the process.
		{
			$form = $this->addRejectForm($form);
			$fields = array();//clear fields array;	
		}
		
		
		$signatureFound = false;
		foreach($fields as $field)
		{
			$precurioElement = new Precurio_FormElement($field,$this);
			
			if($field['type']=='signature')
			{
				$signatureFound = true;
				if($this->viewMode)
				{
					//$form->addElement($precurioElement->processSchema());
				}
				else
				{
					if($this->isMyForm)
					{
						//$signatureFound = true;
						$form->addElement($precurioElement->processSchema());//adds an hidden field
							
					}
				}
				
			}
			else
			{
				$form->addElement($precurioElement->processSchema());
			}
		}
		
		if($signatureFound)
		{
			if($this->viewMode)
			{
				$tr = Zend_Registry::get('Zend_Translate');
				$label = new Precurio_PlainText('alabel');
				$label->setDecorators(array(
				'ViewHelper',
				array('HtmlTag',array('tag'=>'td','class'=>'alt')),
				'Label',
				array(array('label'=>'HtmlTag'),array('tag'=>'td','class'=>'alt')),
				array(array('row'=>'HtmlTag'),array('tag'=>'tr'))
				));
				$label->setLabel($translate->translate('Signature'));
				$label->setValue($tr->translate(PrecurioStrings::SIGNATURESTATEMENT));
				$form->addElement($label);
			}
			else 
			{
				$form->addElement($precurioElement->processApprovalButtons());
			}
		}
		
		
		if($this->isMyForm && (!$this->viewMode))
		{
			$nextState = $this->processState->getNextState();
			if($nextState && $nextState->userCanChangeApprover())
			{
				//note that the component name does not follow my conventions
				//this is so that a normal form component has almost no chance of having
				// the same name has this one
				$nextapproverid = new Zend_Form_Element_Select('nextapproverid');
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
				$select = $table->select()->where('active=1')->order('first_name asc');
				$users = $table->fetchAll($select);
					
				foreach($users as $user)
				{
					$nextapproverid->addMultiOption($user->getId(),$user->getFullName());
				}
				$nextapproverid->setLabel($translate->translate('Confirm next level approver:'));

				$nextapproverid->setDecorators(array(
					'ViewHelper',
					'Errors',
					array('HtmlTag', array('tag'=>'td')),
					array('Label',array('style'=>'font-weight:bold;','escape'=>false)),
					array(array('label'=>'HtmlTag'), array('tag'=>'td')),
					array(array('row'=>'HtmlTag'), array('tag'=>'tr'))
				));

				$processManager = new ProcessManager($this->form_id);
				$processManager->setCurrentState($this->processState);
				$nextapproverid->setValue($processManager->getGoodApproverId($nextState));
				
				if($signatureFound)$nextapproverid->setOrder(count($form->getElements())-1);
				$form->addElement($nextapproverid);
			}
		}
		
		
		if(!$this->viewMode)
		{
			if(!$signatureFound && $this->isMyForm)//signature element not found. means this is not an approval stage
				$form->addElement(Precurio_FormElement::generateSubmitCancelButtons());	
		}
		
		$form->setDecorators(array(
		    'FormElements',
		    array('HtmlTag', array('tag' => 'table','class'=>'workflow_table','cellspacing'=>0)),
		    'Form'
			));
		$form->setAttrib('enctype', 'multipart/form-data');	
		if($this->isMyForm)
			Precurio_Session::setCurrentForm($form);
	    return $form;
	}
	
	/**
	 * Returns array  of fields schema for this state sorted by position
	 * @return Array 
	 */
	private function getFieldsSchema()
	{
		$allFields =  $this->processSchema['fields']['field'];
		$stateFieldsNames = $this->processState->getFields();
		
		$stateFields = array();
		foreach($allFields as $field)
		{
			if(array_search($field['name'],$stateFieldsNames)=== FALSE)
				continue;
			$stateFields[] = $field;
		}
		usort($stateFields, 'self::sortFn');
		return $stateFields;
	}
	
	/**
	 * @param $form Zend_Form
	 * @return Zend_Form
	 */
	private function addRejectForm($form)
	{
		$translate = Zend_Registry::get('Zend_Translate');
		$label = new Precurio_PlainText('alabel');
		$label->setDecorators(array(
		'ViewHelper',
		array('HtmlTag',array('tag'=>'td','class'=>'alt')),
		'Label',
		array(array('label'=>'HtmlTag'),array('tag'=>'td','class'=>'alt')),
		array(array('row'=>'HtmlTag'),array('tag'=>'tr'))
		));
		$label->setLabel($translate->translate('REASON FOR DISAPPROVAL'));
		$label->setValue($this->userProcess->getRejectionComment());
		$form->addElement($label);
		return $form;
	}
	/**
	 * @return Zend_Form
	 */
	public function getForm() {
		return $this->_form;
	}
	/**
	 * @param $_footer the $_footer to set
	 */
	public function setFooter($_footer) {
		$this->_footer = $_footer;
	}

	/**
	 * @return the $_footer
	 */
	public function getFooter() {
		return $this->_footer;
	}

	/**
	 * @return the $_header
	 */
	public function getHeader() {
		return $this->_header;
	}
	
	private function sortFn($x, $y)
	{
		 if ( $x['position'] == $y['position'] )
		  return 0;
		 else if ( $x['position'] < $y['position'] )
		  return -1;
		 else
		  return 1;
	}
	public function getUserProcessId()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$row = $table->fetchRow($table->select()->where('state_id = ?',$this->processState->id)->where('form_id = ?',$this->form_id));
		return $row->id;
	}

}

?>