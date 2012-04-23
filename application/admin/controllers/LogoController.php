<?php


require_once 'Zend/Controller/Action.php';

class Admin_LogoController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
	}
	public function submitAction()
	{
		$params = $this->getRequest()->getParams();
		
		$themes = new Precurio_Themes();
		$temp_dir = $themes->checkDir('/uploads/tmp/');

		if(!isset($_FILES['file']) )
		{
			$this->view->error = $this->translate('No file was selected');
			$this->render('index');
			return;
		}
		$file = $_FILES['file'];
		
		if (is_uploaded_file($file['tmp_name'])) 
		{
	    	$filename = $file['name'];
			$basefilename = preg_replace("/(.*)\.([^.]+)$/","\\1", $filename);
			$ext = preg_replace("/.*\.([^.]+)$/","\\1", $filename);
			if(strtolower($ext) !== 'jpg')
			{
				$this->view->error = $this->translate('Incorrect file format');
				$this->render('index');
				return;
			}
			if (!move_uploaded_file($file['tmp_name'], $temp_dir.'/'.$filename)) 
			{
			    $this->view->error = $this->translate('Cannot upload file');
			    $this->render('index');
			    return;
			}
		}
		else
		{
			$this->view->error = $this->translate('No file was selected');
			$this->render('index');
			return;
		}
		//ok, upload was successful, now lets rename the file, and copy it to selected style folder
		
		if(!rename($temp_dir.'/'.$filename,$temp_dir.'/logo.'.$ext))
		{
			$this->view->error = $this->translate('Could not complete upload');
			$this->render('index');
			return;
		}
		$tempFile = $temp_dir.'/logo.'.$ext;
		$currentTheme = $themes->getDefaultTheme();
		$styles = $themes->getThemeStyles($currentTheme);
		$selectedStyles = array_intersect_key($params,$styles);
		foreach($selectedStyles as $style)
		{
			$logoPath  = $themes->checkDir('/library/css/'.$currentTheme.'/'.$style.'/images/');
			
			copy($tempFile,$logoPath.'/logo.'.$ext);
		}
		unlink($tempFile);
		
		
		return $this->_redirect('/admin/logo/');
	}
	function getPageTitle() {
		return $this->translate('Company Logo');
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ADMIN);
		$this->view->pageTitle = $this->view->translate($this->getPageTitle());
	}
	public function translate($str)
	{
		return $this->view->translate($str);
	}
}
?>