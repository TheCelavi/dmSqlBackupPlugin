<?php

class dmSqlBackupAdminActions extends dmAdminBaseActions {

    public function executeIndex(sfWebRequest $request) {
        if ($request->isMethod('post')) $this->executeForm($request);

        $sqlBackup = $this->getService('sql_backup');
        $this->backupDirectory = $sqlBackup->getBackupDirectory();
        $files = $sqlBackup->getBackupFiles();
        
        $this->files = $this->parseFiles($files);
        
        $widgets = array();
        $validators = array();
        
        foreach ($this->files as $file) {
            $widgets[dmString::slugify($file['path'])] = new sfWidgetFormInputCheckbox();
            $validators[dmString::slugify($file['path'])] = new sfValidatorBoolean(array('required' => false));
        }
        
        $this->form = new dmForm();
        $this->form->setWidgets($widgets);
        $this->form->setValidators($validators);
    }

    public function executeDownload(sfWebRequest $request) {
        $path = $this->getService('sql_backup')->getBackupDirectory() . DIRECTORY_SEPARATOR . $request->getParameter('file');
        $this->forward404Unless(file_exists($path)); // TODO ERROR MESSAGE???
        $this->setLayout(false);
        sfConfig::set('sf_web_debug', false);
        $this->getResponse()->clearHttpHeaders();
        $this->getResponse()->setHttpHeader('Pragma: public', true);
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(readfile($path));
        return sfView::NONE;
    }

    protected function executeForm(sfWebRequest $request) {
        $sqlBackup = $this->getService('sql_backup');
        $this->form = new dmForm();
        $files = $sqlBackup->getBackupFiles();
        $widgets = array();
        $validators = array();
        $check_params = array();
        foreach ($files as $file) {   
            $fullpath = $file;
            $file = str_replace($this->getService('sql_backup')->getBackupDirectory() . DIRECTORY_SEPARATOR, '', $file);
            $check_params[dmString::slugify($file)] = $fullpath;
            $widgets[dmString::slugify($file)] = new sfWidgetFormInputCheckbox();
            $validators[dmString::slugify($file)] = new sfValidatorBoolean(array('required' => false));
        }
        $this->form->setWidgets($widgets);
        $this->form->setValidators($validators);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getParameter($this->form->getName()));
            if ($this->form->isValid()) {
                if ($request->hasParameter('_backup') || $request->hasParameter('_backup_and_download')) {
                    try {
                        $filename = $sqlBackup->execute(Doctrine_Manager::connection());
                        $this->getUser()->setFlash('notice', 'Backup created successfully!');
                        if ($request->hasParameter('_backup_and_download')) {
                            $this->downloadFileName = $filename;
                        }
                    } catch (Exception $e) {
                        $this->getUser()->setFlash('error', 'Something went wrong, backup is not created properly.', false);
                    }
                }
                if ($request->hasParameter('_batch_delete')) {
                    $formFields = $request->getParameter($this->form->getName());
                    $noErrors = true;                    
                    foreach ($check_params as $key=>$value) {                        
                        if (isset($formFields[$key])) {
                            $noErrors = $noErrors && unlink($value);
                        }
                    }
                    if ($noErrors) {
                        $this->getUser()->setFlash('notice', 'Backup files are successfuly deleted!');
                    } else {
                        $this->getUser()->setFlash('error', 'Something went wrong, not all backups are delted.', false);
                    }
                }
            }
        }
    }

    protected function parseFiles($files) {
        $result = array();
        foreach ($files as $file) {
            if (strpos($file, '.sql'))
                $result[] = $this->parseFile($file);
        }
        return $result;
    }

    protected function parseFile($file) {
        $result = array();        
        $file = str_replace($this->getService('sql_backup')->getBackupDirectory() . DIRECTORY_SEPARATOR, '', $file);
        $result['path'] = $file;
        $file = explode('-', $file);
        $tmp = explode(':', $file[0]);
        $result['dbms'] = $tmp[0];
        $tmp = explode(';', $tmp[1]);
        $result['host'] = str_replace('host=', '', $tmp[0]);
        $result['database'] = str_replace('dbname=', '', $tmp[1]);
        $y = (int) $file[1];
        $m = (int) $file[2];
        $d = (int) $file[3];
        $h = (int) $file[4];
        $m = (int) $file[5];
        $s = (int) $file[6];
        $date = new DateTime();
        $date->setDate($y, $m, $d);
        $date->setTime($h, $m, $s);
        $result['date'] = $date;
        $result['filesize'] = filesize($this->getService('sql_backup')->getBackupDirectory() . DIRECTORY_SEPARATOR . $result['path']);
        return $result;
    }

}