<?php

abstract class dmSqlBackupAdapter {

    protected
    $filesystem,
    $connection;

    public function __construct(dmFilesystem $filesystem, Doctrine_Connection $connection) {
        $this->filesystem = $filesystem;
        $this->connection = $connection;
    }

    protected function getFileName() {
        return date('Y-m-d-H-i-s-u') . '.sql';
    }

    abstract public function execute($directoryDestination);
}