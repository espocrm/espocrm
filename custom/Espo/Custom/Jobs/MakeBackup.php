<?php

namespace Espo\Custom\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\Utils\Config;

class MakeBackup implements JobDataLess
{
    public function __construct(private Config $config)
    {
    }

    public function run(): void 
    {
        $this->makeBackup();
        $this->sendBackup();
    }
    
    private function makeBackup()
    {
        $script = $this->getScriptPath('make-backup.sh');

        $DB_NAME = $this->config->get('database.dbname');
        $DB_USER = $this->config->get('database.user');
        $DB_PASS = $this->config->get('database.password');
        $BACKUP_FOLDER = $this->getBackupPath();

        $scriptWithParams = $script . ' ' . $DB_NAME . ' ' . $DB_USER . ' ' . $DB_PASS . ' ' . $BACKUP_FOLDER;
        shell_exec('sh ' . $scriptWithParams);

        return $scriptWithParams;
    }

    private function sendBackup()
    {
        $script = $this->getScriptPath('backup-to-telegram.sh');

        $token = $this->config->get('telegram.token');
        $chatId = $this->config->get('telegram.chats.FreedomCRM');
        $backupFolder = $this->getBackupPath();

        $scriptWithParams = $script . ' ' . $token . ' ' . $chatId . ' ' . $backupFolder;
        shell_exec('sh ' . $scriptWithParams);
        
        return $scriptWithParams;
    }

    private function getScriptPath($scriptName) 
    {   
        return exec('pwd') . '/custom/Espo/Custom/Jobs/scripts/' . $scriptName;
    }

    private function getBackupPath()
    {
        return exec('pwd') . '/' . $this->config->get('backup');
    }
}
