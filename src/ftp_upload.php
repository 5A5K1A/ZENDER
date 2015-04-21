<?php
// http://code.tutsplus.com/tutorials/how-to-work-with-php-and-ftp--net-20012

class FTPClient
{
    private $connectionId;
    private $loginOk = false;
    private $messageArray = array();

    public function __construct() { }

    private function logMessage($message)
    {
        $this->messageArray[] = $message;
    }

    public function getMessages()
    {
        return $this->messageArray;
    }

    public function connect ($server, $ftpUser, $ftpPassword, $isPassive = false)
    {

        // *** Set up basic connection
        $this->connectionId = ftp_connect($server);

        // *** Login with username and password
        $loginResult = ftp_login($this->connectionId, $ftpUser, $ftpPassword);

        // *** Sets passive mode on/off (default off)
        ftp_pasv($this->connectionId, $isPassive);

        // *** Check connection
        if ((!$this->connectionId) || (!$loginResult)) {
            $this->logMessage('FTP connection has failed!');
            $this->logMessage('Attempted to connect to ' . $server . ' for user ' . $ftpUser, true);
            return false;
        } else {
            $this->logMessage('Connected to ' . $server . ', for user ' . $ftpUser);
            $this->loginOk = true;
            return true;
        }
    }


}