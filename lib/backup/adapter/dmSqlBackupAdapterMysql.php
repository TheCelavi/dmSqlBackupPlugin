<?php

class dmSqlBackupAdapterMysql extends dmSqlBackupAdapter
{

  public function execute($directoryDestination)
  {

      $fileName = $this->getFileName();
    $command = sprintf('mysqldump -h "%s" -u "%s" -p"%s" "%s" > "%s"',
      preg_replace('/mysql\:host=([-\.\w]+);.*/i', '$1', $this->connection->getOption('dsn')),
      $this->connection->getOption('username'),
      $this->connection->getOption('password'),
      preg_replace('/mysql\:host=[-\.\w]+;dbname=([-\.\w]+);.*/i', '$1', $this->connection->getOption('dsn')),
      dmOs::join($directoryDestination, $fileName)
    );
    
    $success = $this->filesystem->execute($command);
    return ($success) ? $fileName : false;
  }
}
