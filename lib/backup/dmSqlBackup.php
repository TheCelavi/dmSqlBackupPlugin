<?php

class dmSqlBackup extends dmConfigurable {

    protected
    $filesystem,
    $logCallable,
    $adapters;

    public function __construct(dmFilesystem $filesystem, array $adapters, array $options) {
        $this->filesystem = $filesystem;
        $this->adapters = $adapters;
        $this->initialize($options);
    }

    protected function initialize(array $options) {
        $options['backup_dir'] = str_replace('SF_ROOT_DIR', sfConfig::get('sf_root_dir'), $options['backup_dir']);
        $this->configure($options);
    }

    public function execute(Doctrine_Connection $connection) {
        $eventLog = dmContext::getInstance()->getServiceContainer()->getService('event_log');
        $adapter = $this->getAdapter($connection);
        $this->log(sprintf('About to backup database into %s', $this->getOption('backup_dir')));
        $this->createDir();
        try {
            $fileName = $adapter->execute($this->getOption('backup_dir'));
        } catch (Exception $e) {
            $fileName = false;
        }
        if ($fileName) {
            $this->log(sprintf('New database backup file: %s', $fileName));
            $this->log('Database backup - done.');
            $eventLog->log(array(
                'server' => $_SERVER,
                'action' => 'database',
                'type' => 'Database',
                'subject' => 'Backup created'
            ));
        } else {
            $this->log('Error: backup of database is not executed properly.');
            $eventLog->log(array(
                'server' => $_SERVER,
                'action' => 'error',
                'type' => 'Database',
                'subject' => 'Database error'
            ));
        }
        return $fileName;
    }

    protected function getAdapter(Doctrine_Connection $connection) {
        $adapterName = strtolower($connection->getDriverName());
        if (!isset($this->adapters[$adapterName])) {
            throw new dmException(sprintf('%s is not supported. Available adapters are %s', $adapterName, implode(', ', array_keys($this->adapters))));
        }
        return new $this->adapters[$adapterName]($this->filesystem, $connection);
    }
    
    public function getBackupFiles() {
        return sfFinder::type('file')->ignore_version_control()->maxdepth(0)->sort_by_name()->name('*.sql')->in($this->getBackupDirectory());
    }
    
    public function getBackupDirectory() {
        return $this->getOption('backup_dir');
    }
    
    protected function createDir() {
        if (!$this->filesystem->mkdir($this->getOption('backup_dir'))) {
            throw new dmException(sprintf('Can NOT create dir %s', $this->getOption('backup_dir')));
        }
    }
    
    public function setLogCallable($callable) {
        $this->logCallable = $callable;
        return $this;
    }

    protected function log($msg) {
        if (is_callable($this->logCallable)) {
            call_user_func($this->logCallable, $msg);
        }
    }
    
    public function delete($file) {
        if (trim((string)$file) == '') throw new dmException('File name not provided.');
        return $this->filesystem->unlink(dmOs::join($this->getOption('backup_dir'), $file));
    }
    
    public function download($file) {
        if (trim((string)$file) == '') throw new dmException('File name not provided.');
        return readfile(dmOs::join($this->getOption('backup_dir'), $file));
    }
    
    public function isBackUpExist($file) {
        if (trim((string)$file) == '') throw new dmException('File name not provided.');
        return file_exists(dmOs::join($this->getOption('backup_dir'), $file));
    }
    

}