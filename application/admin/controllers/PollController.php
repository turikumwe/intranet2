<?php
require_once ('poll/models/Polls.php');
require_once ('admin/controllers/BaseController.php');
class Admin_PollController extends Admin_BaseController {
	
	function generateHeader() {
		return array(" ",$this->translate("Title"),$this->translate("Multiple Answers"),$this->translate("Expiring"));
	}
	
	function generateList($searchText) 
	{
		$polls = Polls::getPolls();
		
		$arr = array();
		$i = 1;
		foreach($polls as $poll)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($poll->title,$searchText)===FALSE)
				{
					continue;
				}
				
			}
			
			$arr[] = array($i++,'title'=>$poll->title,'multiple'=>$poll->multiple_answers == 0 ? $this->translate(PrecurioStrings::NO) : $this->translate(PrecurioStrings::YES),'expire'=>$poll->getExpiringTime(),'id'=>$poll->id);
		}
		return $arr;
	}
	
	public function preSubmit(&$values)
	{
		$date = new Precurio_Date();
		$date->setMonth($values['end_month']);
		$date->setDay($values['end_day']);
		$date->setYear($values['end_year']);
		$date->setHour($values['end_hour']);
		$date->setMinute($values['end_minute']);
		unset($values['end_month']);
		unset($values['end_day']);
		unset($values['end_year']);
		unset($values['end_hour']);
		unset($values['end_minute']);
		$values['end_date'] = $date->getTimestamp();
	}
	
	function getForm($poll = null,$viewMode = false) 
	{
		$endDate = $poll == null ? Zend_Date::now() : new Zend_Date($poll->end_date);
		
		$form = new Zend_Form();
		
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/poll/submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm');
			
		
			
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper'
				),
				'required' => false,
				'value'=>$poll['id'],
				));
		$form->addElement('hidden', 'user_id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper'
				),
				'required' => false,
				'value'=>$poll == null ? Precurio_Session::getCurrentUserId() : $poll['user_id'],
				));
		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper'
				),
				'required' => false,
				'value'=>$poll == null ? Precurio_Date::now()->getTimestamp() : $poll['date_created'],
				));
		$form->addElement('hidden', 'active', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper'
				),
				'required' => false,
				'value'=>1,
				));
		$form->addElement('text', 'title', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Title'),
				'value'=>$poll['title']
				));
		$form->addElement('checkbox', 'multiple_answers', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Multiple Answers'),
				'value'=>$poll == null ? 0 : $poll['multiple_answers'],
				));
				
		$form->addElement('checkbox', 'randomise_options', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Randomise Options'),
				'value'=>$poll == null ? 0 : $poll['randomise_options'],
				));
				
		$endDate = $poll == null ? Zend_Date::now() : new Zend_Date($poll->end_date);
		
		$form->addElement('select', 'end_month', array(
				'required' => true,
		'decorators'=>array('ViewHelper','FormElements','Errors','Label',array('HtmlTag',array('tag'=>'p','openOnly'=>true))),
				'class' => 'oneThirdSelect',
				'label'=>$this->translate('Expiring Date'),
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$endDate->get(Precurio_Date::MONTH_SHORT)
				));
		
		$form->addElement('select', 'end_day', array(
				'required' => true,
		'decorators'=>array('ViewHelper','FormElements','Errors'),
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::DAY)
				));
		
		$form->addElement('select', 'end_year', array(
				'required' => true,
		'decorators'=>array('ViewHelper','FormElements','Errors'),
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label',array('HtmlTag',array('tag'=>'p','closeOnly'=>true))),
				'value'=>$endDate->get(Precurio_Date::YEAR)		
				));
				
		$form->addElement('select', 'end_hour', array(
				'required' => true,
				'decorators'=>array('ViewHelper','FormElements','Errors','label',array('HtmlTag',array('tag'=>'p','openOnly'=>true))),
				'class' => 'oneThirdSelect',
				'label'=>$this->translate('Expiring Time'),
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllHours(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::HOUR)
				));	
//		$x = $form->getElement('end_hour')->getDecorator('label');
//		$x->setOption('escape',false);		
				
		$form->addElement('select', 'end_minute', array(
		'decorators'=>array('ViewHelper','FormElements','Errors'),
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMinutes(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::MINUTE)
				));
				
				
		if($viewMode)
		{
			$form->addElement('text', 'user', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Added By'),
				'readOnly'=>'readOnly',
				'value'=>UserUtil::getUser($poll['user_id'])
				));
				
			$form->addElement('text', 'date', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Date Added'),
				'readOnly'=>'readOnly',
				'value'=>new Precurio_Date($poll['date_created'])
				));
		}
		
		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		$form->addDecorator('HtmlTag', array('tag' => 'div','id'=>'form'));	
		return $form;
	}
	
	function getPageTitle() {
		return $this->translate("Polls");
	}
	
	function getTableName() {
		return PrecurioTableConstants::POLL;
	}
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$item = $table->find($id)->current();
		
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getForm($item,true);
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->list =  $this->generateOptionsList($id);
		$this->view->header = array('',$this->translate('Option'),$this->translate('Number of Votes'));
	}
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$item = $table->find($id)->current();
		
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getForm($item);
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->list =  $this->generateOptionsList($id);
		$this->view->header = array('',$this->translate('Option'),$this->translate('Number of Votes'));
		$this->render('view');
	}
	
	function generateOptionsList($poll_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL_OPTIONS));
		$options = $table->fetchAll($table->select()->where('poll_id = ?',$poll_id)
													->where('active = 1'));
		
		
		$arr = array();
		$i = 1;
		foreach($options as $option)
		{
			$arr[] = array($i++,'option'=>$option->label,'num_votes'=>$option->num_votes,'id'=>$option->id);
		}
		return $arr;
	}
	
	function addoptionAction()
	{
		$this->_helper->layout->disableLayout();
		$poll_id = $this->getRequest()->getParam('id',0);
		
		$this->view->form = $this->getOptionForm(null,$poll_id);
	}
	function editoptionAction()
	{
		$this->_helper->layout->disableLayout();
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL_OPTIONS));
		$option = $table->fetchRow($table->select()->where('id = ?',$id)
													->where('active = 1'));
		$this->view->id = $id;											
		$this->view->form = $this->getOptionForm($option,0);
		$this->render('addoption');
	}
	function deleteoptionAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL_OPTIONS));
		$option = $table->fetchRow($table->select()->where('id = ?',$id));
		$option->active = 0;
		$option->save();
		return $this->_forward('view','poll','admin',array('id'=>$option->poll_id));
	}
	function submitoptionAction()
	{
		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
		$form = $this->getOptionForm(null,$params['poll_id']);
		if (!$form->isValid($_POST))
		{
			//do nothing
		}
		
		$values = $form->getValues();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL_OPTIONS));
		if(Precurio_Utils::isNull($values['id']))
		{
			//check if the new addition already exists
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL_OPTIONS));
			$option = $table->fetchRow($table->select()->where('label = ?',$values['label'])->where('poll_id = ?',$params['poll_id']));
			//if it doesnt, add it, else reactivate the already existing one.
			if($option == null)
			{
				unset($values['id']);
				$data = $table->createRow($values);
				$data->save();
			}
			else
			{
				$option->active = 1;
				$option->save();
			}
		}
		else
		{
			$option = $table->find($values['id'])->current();
			$option->setFromArray($values);
			$option->save();
		}
		return $this->_redirect('/admin/poll/view/id/'.$values['poll_id']);
	}
	function getOptionForm($option = null,$poll_id) 
	{
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/option/submitoption')
			->setMethod('post')
			->setAttrib('id','optionform')
			->setAttrib('name','optionForm')
			->setAttrib('enctype', 'multipart/form-data');
			
		
			
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$option['id'],
				));
				
		$form->addElement('hidden', 'poll_id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$option == null ? $poll_id : $option['poll_id'],
				));
				
		$form->addElement('hidden', 'active', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>1,
				));
				
		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$option == null ? Precurio_Date::now()->getTimestamp() : $option['date_created'],
				));
				
		$form->addElement('text', 'label', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => true,
				'value'=>$option['label']
				));

		return $form;
	}
	
}

?>