<?php

/**
 * WorkflowController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'workflow/models/vo/Process.php';
require_once 'report/models/WorkflowReport.php';
require_once 'report/models/Report.php';
class Report_WorkflowController extends Zend_Controller_Action {
	public function translate($str)
	{
		return $this->view->translate($str);
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		
		
		$chart = Report::chart('chart1');
        $chart->setExport(array('ofc'));

        $chart->setChartType('bar_glass');
		
        $workflowReport = new WorkflowReport();
        
        $arr = array(
		array('label'=>$this->translate('Completed'),'total'=>$workflowReport->getNumOfCompleted()),
		array('label'=>$this->translate('Pending'),'total'=>$workflowReport->getNumOfPending()),
		array('label'=>$this->translate('OverDue'),'total'=>$workflowReport->getNumOfDue()),
		array('label'=>$this->translate('Approved'),'total'=>$workflowReport->getNumOfApproved()),
		array('label'=>$this->translate('Denied'),'total'=>$workflowReport->getNumOfDenied())
		);

		if(count($arr) < 5)
		{
			echo("<div id='noRecords' align='center'>".$this->view->translate(PrecurioStrings::NOTENOUGHREPORTDATA) ."</div>");
			return;//no top contents, then definitely no most search content
		}
		
        $chart->setSource(new Bvb_Grid_Source_Array($arr));
        
        $chart->addValues('total', array('set_key' => 'total', 'set_animate'=>'1'));
		$chart->setTitle($this->translate('Your Workflow Summary'));
		$chart->setXLabels('label');
		$chart->setChartDimensions(450,250);
		
		
		
		
		$chart2 = Report::chart('chart2');
        $chart2->setExport(array('ofc'));

        $chart2->setChartType('pie');
        
        $table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		
		$select = $select->distinct()
				->from(array('a'=>PrecurioTableConstants::WORKFLOW))
				->join(array('b' => PrecurioTableConstants::USER_PROCESS),'a.id = b.process_id',array('total'=>'count(form_id)'))
				->where('b.active=1')
				->where('b.user_id=?',Precurio_Session::getCurrentUserId())
				->group('b.process_id')
				->order('total desc')
				->limit(5);
		
		if($table->fetchAll($select)->count() <= 0)
		{
			echo("<div id='noRecords' align='center'>".$this->view->translate(PrecurioStrings::NOTENOUGHREPORTDATA) ."</div>");
			return;//no active workflow, then definitely no workflows
		}
		
        $chart2->setSource(new Bvb_Grid_Source_Zend_Select($select));
        
        $chart2->addValues('total', array('set_colour' => '#00FF00', 'set_key' => $this->translate('Total'), 'set_animate'=>'1'));
		$chart2->setTitle($this->translate('Most active workflows'));
        $chart2->setXLabels('code');
		$chart2->setChartDimensions(400,250);
		
		
		
		$grid = Report::grid('workflows');

		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW));
		$select = $table->select(false)->distinct()
				->from(array('a'=>PrecurioTableConstants::WORKFLOW),
					array('id','display_name','code','description','date_created'))
				->join(array('b' => PrecurioTableConstants::USER_PROCESS),'a.id = b.process_id',array())
				->where('b.user_id = ?',Precurio_Session::getCurrentUserId())
				->where('a.active=1');
		$grid->setSource(new Bvb_Grid_Source_Zend_Select($select));
		
		$grid->updateColumn('display_name',array('title'=>$this->translate('Name')));
		$grid->updateColumn('id',array('hidden'=>true));
		$grid->updateColumn('code',array('search'=>false));
		$grid->updateColumn('date_created',array('search'=>false));
		$grid->updateColumn('date_created',array('format'=>'date'));

		
		$baseUrl = $this->getRequest()->getBaseUrl();
		$grid->updateColumn('display_name',array('decorator'=>"<a href='$baseUrl/report/workflow/view/id/{{id}}'>{{display_name}}</a>"));
		
//		$col = new Bvb_Grid_Extra_Column();
//		$col->name('');
//		$col->decorator("<a href='$baseUrl/report/workflow/view/id/{{id}}'> View Report </a>");
//		$col->position('right');
//		$grid->addExtraColumns($col);
		
		
		$this ->view->grid = $grid->deploy();
		
		$this->view->chart =  $chart->deploy();
		
		$this->view->chart2 =  $chart2->deploy();
	}
	public function viewAction()
	{
		$process_id = $this->getRequest()->getParam('id',0);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$rowSet = $table->find($process_id);
		if(count($rowSet) == 0)//missing process, i.e process id does not exit
		{
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::MISSINGPROCESS),Precurio_Exception::EXCEPTION_MISSING_PROCESS);
		}
		$process = $rowSet->current();
		
		
		$grid = Report::grid('form_view');

		$table = new Zend_Db_Table(array('name'=>$process->getTableName()));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		
		$select = $select->distinct()
				->from(array('a'=>$process->getTableName()))
				->join(array('b' => PrecurioTableConstants::USER_PROCESS),'a.id = b.form_id',array('view_id'=>'id'))
				->where('b.user_id = ?',Precurio_Session::getCurrentUserId())
				->where('b.process_id = ?',$process_id)
				->where('b.active=1')
				->group('b.form_id');
				
		
		if($table->fetchAll($select)->count() < 1)
		{
			echo("<div id='noRecords' align='center'>".$this->view->translate(PrecurioStrings::NOTENOUGHREPORTDATA) ."</div>");
			return;//no top contents, then definitely no most search content
		}
				
		$grid->setSource(new Bvb_Grid_Source_Zend_Select($select));
		
		$grid->updateColumn('display_name',array('title'=>$this->translate('Name')));
		$grid->updateColumn('user_id',array('title'=>$this->translate('User')));
		$grid->updateColumn('id',array('hidden'=>true));
		$grid->updateColumn('view_id',array('hidden'=>true));
		$grid->updateColumn('signature',array('hidden'=>true));
		for($i=2;$i<10;$i++)//(we are assuming all forms wont have more than 10 approval levels)
			$grid->updateColumn('signature_'.$i,array('hidden'=>true)); //hide all signature fields. 
		$grid->updateColumn('last_updated',array('hidden'=>true));
		
		$grid->updateColumn('date_created',array('format'=>'date'));
		$grid->updateColumn('user_id',array('format'=>'User'));
		
			
		$fields = $process->getFields();
		
		foreach($fields as $field)
		{
			$grid->updateColumn($field['name'],array('title'=>$field['label']));
			if($field['type'] == 'currency')
				$grid->updateColumn($field['name'],array('format'=>'Currency'));
		}
		
		$col = new Bvb_Grid_Extra_Column();
		$baseUrl = $this->getRequest()->getBaseUrl();
		$col->name('');
		$col->decorator("<a href='$baseUrl/workflow/view/{{view_id}}'> ".$this->translate('View Form')." </a>");
		$col->position('left');
		$col->hide(true);
		$grid->addExtraColumns($col);
		
		
		
		
        $chart = Report::chart();
        $chart->setExport(array('ofc'));

        $chart->setChartType('bar');
        
        $table = new Zend_Db_Table(array('name'=>$process->getTableName()));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		
		$select = $select->distinct()
				->from(array('a'=>$process->getTableName()),array('user_id'))
				->join(array('b' => PrecurioTableConstants::USER_PROCESS),'a.id = b.form_id',array('total'=>'count(*)'))
				->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('fullname'=>'concat(c.first_name," ",c.last_name)'))
				->where('b.process_id = ?',$process_id)
				->where('b.active=1')
				->where('b.task_id = 0')
				->group('b.user_id')
				->order('total desc')
				->limit(20);
				
		
        $chart->setSource(new Bvb_Grid_Source_Zend_Select($select));
        
        $chart->addValues('total', array('set_colour' => '#00FF00', 'set_key' => 'total'));
		$chart->setTitle($this->translate('Total number of request per user (Top 20)'));
        $chart->setXLabels('fullname');
        
        $count = $table->fetchAll($select)->count();
        $x  =  $count * 100;
        if($x>900)$x=900; //900 is the maximum value
        if($x<300)$x=300; //300 is the minimum value
        
        $chart->setChartDimensions($x,350);
        $chart->setMultiple(false);
		$this->view->grid = $grid->deploy();
		
		if($count > 5)
			$this->view->myChart = $chart->deploy();
	}
	
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_REPORT);
	}
}
?>