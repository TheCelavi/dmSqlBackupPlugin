<?php

class dmSqlBackupTask extends dmContextTask
{

  /**
   * @see sfTask
   */
  protected function configure()
  {
    parent::configure();

    $this->addOptions(array(
    ));

    $this->namespace = 'dm';
    $this->name = 'sql-backup';
    $this->briefDescription = 'Creates a sql backup';

    $this->detailedDescription = $this->briefDescription;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->withDatabase();

    $connection = dmDb::table('DmPage')->getConnection();

    $this->get('sql_backup')
    ->setLogCallable(array($this, 'customLog'))
    ->execute($connection);
  }

  public function customLog($msg)
  {
    return $this->logSection('diem-sql-backup', $msg);
  }
}