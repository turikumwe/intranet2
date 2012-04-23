<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initPlugin() {
        if (!Precurio_Session::sessionExists())
            $this->bootstrap('session');;
        $this->bootstrap('config');
        $this->bootstrap('request');
        $this->bootstrap('controller');
        $this->bootstrap('routes');
        $this->bootstrap('db');
        $this->bootstrap('log');
        $this->bootstrap('view');
        $frontController = Zend_Controller_Front::getInstance();
        // Change to 'production' parameter under production environemtn
        $frontController->registerPlugin(new Initializer('development'));

        // Dispatch the request using the front controller. 
        //$frontController->dispatch();
    }

    /**
     * Initialize view 
     * 
     * @return void
     */
    protected function _initView() {
        $this->bootstrap('config');
        $this->bootstrap('controller');
        $this->bootstrap('log');
        $this->bootstrap('languages');

        $frontController = Zend_Controller_Front::getInstance();
        $baseUrl = $frontController->getBaseUrl();

        $root = Zend_Registry::get('root');

        $themes = new Precurio_Themes();

        $theme = $themes->getUserTheme();
        $style = $themes->getUserStyle($theme);

        // Bootstrap layouts

        $layout = Zend_Layout::startMvc(array(
                    'layoutPath' => $root . '/application/default/layouts/' . $theme,
                    'layout' => 'main_c'
                ));

        $config = Zend_Registry::get('config');

        $liveServer = $config->live;
        try {
            $user = UserUtil::getUser(Precurio_Session::getCurrentUserId());
            $pwd = Precurio_Session::getCurrentUserPwd();
        }
        //if this exception is not caught, it propogates to the ErrorController, in this 
        //situation, we dont want that.
        catch (Exception $e) {//i.e there was a problem retrieving the session user
            $user = null;
        }
        $username = $user == null ? '' : $user->getUsername();
        $password = $user == null ? '' : $pwd;

        $view = new Zend_View();
        $view->assign('theme', $theme);
        $view->assign('style', $style);
        $view->assign('themestyle', $theme . '/' . $style);
        $view->assign('company_name', $config->company_name);

        $view->headScript()->appendFile($baseUrl . '/library/js/others/easySlider1.5.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jquery.curvycorners.source.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jquery_ui_drag_n_drop.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jquery.listmenu-1.1.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jcarousellite.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jquery-easing-compatibility.1.2.pack.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jquery-easing.1.2.pack.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/coda-slider.1.1.1.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/always_at_bottom.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jquery.autofill.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jquery.autocomplete.js');
        ;
        $view->headScript()->appendFile($baseUrl . '/library/js/others/admin.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/facebox.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/ajaxfileupload.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/styleswitch.js')
                ->appendScript("var baseUrl='" . $baseUrl . "';
        								var currentStyle = '" . $style . "';");
        $view->headScript()->appendFile($baseUrl . '/library/js/others/comment.js');
        if ($config->module->mod_chat == 1)
            $view->headScript()->appendFile($baseUrl . '/library/js/others/chat.js')
                    ->appendScript("var service='" . $liveServer->service . "';
                                            var domain='" . $liveServer->domain . "';
                                            var username='" . $this->encrypt($username) . "';
                                            var password='" . $this->encrypt($password) . "';");
        $view->headScript()->appendFile($baseUrl . '/library/js/strophe/strophe.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/strophe/sha1.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/strophe/md5.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/strophe/b64.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/task.js');
        $view->headScript()->appendFile($baseUrl . '/library/js/others/jqprint.js');

        $view->headLink()->setStylesheet($baseUrl . "/library/css/$theme/theme.css");
        $styles = $themes->getThemeStyles($theme);
        foreach ($styles as $aStyle) {
            $view->headLink(array('title' => $aStyle, 'rel' => ($style == $aStyle ? 'stylesheet' : 'alternate stylesheet'), 'href' => $baseUrl . "/library/css/$theme/$aStyle/style.css", 'type' => 'text/css'));
        }

        $view->headLink()->appendStylesheet($baseUrl . "/library/css/tipsy/tipsy.css");
        $view->headLink()->appendStylesheet($baseUrl . "/library/css/facebox/facebox.css");
        $view->headLink()->appendStylesheet($baseUrl . "/library/css/autocomplete/autocomplete.css");
        $view->addHelperPath($root . '/application/default/views/helpers', 'Default_View_Helper');
        $view->addHelperPath($root . '/application/workflow/views/helpers', 'Workflow_View_Helper');
        $view->addHelperPath($root . '/application/news/views/helpers', 'News_View_Helper');
        $view->addHelperPath($root . '/application/task/views/helpers', 'Task_View_Helper');
        $view->addHelperPath($root . '/application/cms/views/helpers', 'Cms_View_Helper');
        $view->addHelperPath($root . '/application/chat/views/helpers', 'Chat_View_Helper');
        $view->addHelperPath($root . '/application/user/views/helpers', 'User_View_Helper');
        $view->addHelperPath($root . '/application/employee/views/helpers', 'Employee_View_Helper');


        Zend_View_Helper_PaginationControl::setDefaultViewPartial('page_control.phtml');

        $config = new Zend_Config_Xml($root . '/application/configs/navigation.xml');

        $navigation = new Zend_Navigation($config);
        $view->navigation($navigation);

        $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
        $viewRenderer->setView($view);
        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
    }

    /**
     * Initialize Controller paths 
     * 
     * @return void
     */
    protected function _initController() {
        $front = Zend_Controller_Front::getInstance();
        $root = Zend_Registry::get('root');

        $front->addControllerDirectory($root . '/application/default/controllers');
        $front->addControllerDirectory($root . '/application/user/controllers', 'user');
        $front->addControllerDirectory($root . '/application/install/controllers', 'install');
        $front->addControllerDirectory($root . '/application/search/controllers', 'search');
        $front->addControllerDirectory($root . '/application/widget/controllers', 'widget');

        //TODO remove the line below once document management has been fully implemented
        $front->addControllerDirectory($root . '/application/document/controllers', 'document');

        $modules = Bootstrap::getModules(false);
        foreach ($modules as $module)
            $front->addControllerDirectory($root . '/application/' . $module->getName() . '/controllers', $module->getName());
        return $front;
    }

    protected function _initConfig() {

        $root = Zend_Registry::get('root');


        try {
            $frontendOptions = array('master_file' => $root . '/application/configs/precurio.ini', 'automatic_serialization' => true, 'lifetime' => 7200); //cache is valid for 2 hrs
            $backendOptions = array('cache_dir' => $root . '/application/tmp/');

            $cache = Zend_Cache::factory('File', 'File', $frontendOptions, $backendOptions);
            if (!$config = $cache->load('config')) {
                $config = new Zend_Config_Ini($root . '/application/configs/precurio.ini', null, array('allowModifications' => true));
                $cache->save($config, 'config');
            }
        } catch (Exception $e) {
            if (file_exists($root . '/application/configs/precurio_tmp.ini')) {
                copy($root . '/application/configs/precurio_tmp.ini', $root . '/application/configs/precurio.ini');
            }
            $config = new Zend_Config_Ini($root . '/application/configs/precurio.ini', null, array('allowModifications' => true));
        }
        Zend_Registry::set('config', $config);
        return $config;
    }

    /**
     * Initialize data bases
     * 
     * @return void
     */
    protected function _initDb() {
        $this->bootstrap('config');
        $this->bootstrap('controller');

        $root = Zend_Registry::get('root');
        $config = Zend_Registry::get('config');

        $active_db = $config->active_database;
        $db_config = new Zend_Config_Ini($root . '/application/configs/precurio.ini', $active_db);

        $db_config = $db_config->toArray();
        $db_config = $db_config['database'];

        $adapter = $db_config['adapter'];
        $params = array();
        foreach ($db_config as $key => $value) {
            if ($key == 'adapter')
                continue;
            $params[$key] = $value;
        }

        $db = Zend_Db::factory($adapter, $params);
        if ($config->database_auto_connect) {
            try {
                $db->getConnection();
            } catch (Zend_Db_Exception $e) {
                throw new Precurio_Exception($e->getMessage(), Precurio_Exception::EXCEPTION_DATABASE_CONNECTION);
            } catch (Zend_Exception $e) {
                throw new Precurio_Exception($e->getMessage(), Precurio_Exception::EXCEPTION_DATABASE_CONNECTION);
            }
            $db->closeConnection();
        }

        Zend_Registry::set('db', $db);
        Zend_Db_Table::setDefaultAdapter($db);

        $frontendOptions = array('automatic_serialization' => true); //cache is valid for ever
        $backendOptions = array('cache_dir' => $root . '/application/tmp/');

        $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        return $db;
    }

    /**
     * Initialize action helpers
     * 
     * @return void
     */
    protected function _initHelpers() {
        $this->bootstrap('controller');
        // register the default action helpers
        $root = Zend_Registry::get('root');
        Zend_Controller_Action_HelperBroker::addPath($root . '/application/default/helpers', 'Precurio_Helper');
    }

    protected function _initLanguages() {
        $this->bootstrap('config');
        $this->bootstrap('controller');

        $root = Zend_Registry::get('root');

        $frontendOptions = array('automatic_serialization' => true, 'lifetime' => 21600); //cache is valid for 6 hrs
        $backendOptions = array('cache_dir' => $root . '/application/tmp/');

        $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

        Zend_Translate::setCache($cache);

        //get locale settings for registry
        $registry = Zend_Registry::getInstance();
        $config = $registry->get('config');


        try {
            $user = UserUtil::getUser(Precurio_Session::getCurrentUserId());
            if ($user == null) {
                Precurio_Session::logOut();
                throw new Exception('User has been deactivated');
            }

            $userSettings = $user->getSettings();
            $loc = $userSettings->getLocale();
            $locale = new Zend_Locale($loc);
            if (!$locale->isLocale($locale))
                throw new Zend_Locale_Exception("User locale is invalid");
        } catch (Exception $e) {//i.e there was a problem retrieving the session user
            $loc = $config->default_locale;
            $locale = new Zend_Locale($loc);
            $log = Zend_Registry::get('log');
            $log->err($e);
        }


        $lang = $locale->getLanguage();
        $region = $locale->getRegion();
        //$t_file => translation file
        $t_file = $root . "/application/languages/$lang/LC_MESSAGES/" . $lang . '_' . "$region.mo";
        if (!file_exists($t_file))
            $t_file = $root . '/application/languages/' . $lang . '/LC_MESSAGES/precurio.mo';

        $translate = new Zend_Translate('gettext', $t_file, $loc);
        $translate->setLocale($locale);

        $registry->set('Zend_Translate', $translate);
        $registry->set('Zend_Locale', $locale);
    }

    /**
     * Initialize routes
     * 
     * @return void
     */
    protected function _initRoutes() {
        $this->bootstrap('controller');

        $router = Zend_Controller_Front::getInstance()->getRouter();

        $router->addRoute('workflow', new Zend_Controller_Router_Route('workflow/new/:id',
                        array('module' => 'workflow',
                            'controller' => 'process',
                            'action' => 'new',
                            'id' => 1)));

        $router->addRoute('workflow2', new Zend_Controller_Router_Route('workflow/view/:id',
                        array('module' => 'workflow',
                            'controller' => 'process',
                            'action' => 'view',
                            'id' => 1)));

        $router->addRoute('profile', new Zend_Controller_Router_Route('user/profile/view/:id/:page/:param1/:param2/:cpage',
                        array('module' => 'user',
                            'controller' => 'profile',
                            'action' => 'view',
                            'id' => 0,
                            'page' => 'recent',
                            'param1' => '0',
                            'param2' => '0',
                            'cpage' => '0')));
    }

    /**
     * Initialize Session
     * 
     * @return void
     */
    protected function _initSession() {
        $root = Zend_Registry::get('root');
        Zend_Session::start(array('save_path' => $root . '/application/tmp/',
            'name' => 'PRECURIOSESSID',
            'remember_me_seconds' => 7200
        ));
    }

    /**
     * Initialize logs
     * 
     * @return void
     */
    protected function _initLog() {
        $log = new Precurio_Log();
        Zend_Registry::set('log', $log);
        return $log;
    }

    protected function _initRequest() {
        $front = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();
        if (null === $request) {
            $request = new Zend_Controller_Request_Http();
            $front->setRequest($request);
        }
        return $request;
    }

    private function encrypt($str) {
        $key = rand(1, 6);
        $arr = str_split($str);
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = chr(ord($arr[$i]) + $key);
        }
        $arr[] = chr($key + 65);
        return implode('', $arr);
    }

    /**
     * Returns an array of Precurio_Module objects of enabled modules.
     * @param boolean $onlyActive - Determine if you want to get only active modules or you also want to include inactive ones. Default = true i.e. get only active modules
     * @return array
     */
    public static function getModules($onlyActive = true) {
        $config = Zend_Registry::get('config');
        $arr = $config->module->toArray(); //$arr = Array([mod_employee] => 1,[mod_event] => 1,[mod_contact] => 1 ...
        $modules = array();
        foreach ($arr as $key => $value) {
            $key = substr($key, 4); //change 'mod_employee' to 'employee'
            $value = (int) $value;
            try {
                if ($value >= (int) $onlyActive) {
                    $module = new Precurio_Module($key, $value);
                    $modules[] = $module;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        return $modules;
    }

    /**
     * Returns an array of Precurio_Widget objects of enabled modules.
     * @param boolean $onlyActive - Determine if you want to get only active widgets or you also want to include inactive ones. Default = true i.e. get only active modules
     * @return array
     */
    public static function getWidgets($onlyActive = true) {
        $config = Zend_Registry::get('config');
        $arr = $config->widget->toArray(); //$arr = Array([wgt_poll] => 1,[wgt_link] => 1,....
        $widgets = array();
        foreach ($arr as $key => $value) {
            $key = substr($key, 4); //change 'wgt_poll' to 'poll'
            $value = (int) $value;
            try {
                if ($value >= (int) $onlyActive) {
                    $widget = new Precurio_Widget($key, $value);
                    $widgets[] = $widget;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        return $widgets;
    }

    /**
     * Returns all availabe language translation in Precurio
     * @return array of Precurio_Language objects
     */
    public static function getLanguages() {
        $config = Zend_Registry::get('config');
        $arr = $config->language->language->toArray();
        $languages = array();
        foreach ($arr as $key => $value) {
            $languages[] = new Precurio_Language($key, $config->language->language->{$key}->toArray());
        }
        //perform binary sort. to sort languages alphabetically A....Z.
        for ($j = 0; $j <= count($languages) - 1; $j++) {
            for ($i = 0; $i <= count($languages) - 1; $i++) {
                if ($languages[$i]->getLabel() > $languages[$j]->getLabel()) {
                    $temp = $languages[$j];
                    $languages[$j] = $languages[$i];
                    $languages[$i] = $temp;
                }
            }
        }
        return $languages;
    }

    /*     * s
     * Determines if Precurio uses the database for user authentication & management
     * @return boolean
     */

    public static function usesDatabase() {
        $config = Zend_Registry::get('config');
        return $config->auth_mech == 'DatabaseAuth';
    }

    public static function isHosted() {
        $config = Zend_Registry::get('config');
        return !($config->license->type == 'PRO' || $config->license->type == 'FREE');
    }

}

?>