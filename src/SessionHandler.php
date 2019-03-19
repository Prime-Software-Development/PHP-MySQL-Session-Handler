<?php

namespace Programster\SessionHandler;


final class SessionHandler implements \SessionHandlerInterface
{
    private $dbConnection; # the mysqli connection
    private $dbTable; # name of the db table to store sessions in
    private $m_maxAge;
    
    
    /**
     * Create the session handler.
     * @param \mysqli $mysqli - the database connection to store sessions in.
     * @param string $tableName - the table within the database to store session data in.
     * @param int $maxAge - the maximum age in seconds of a session variable.
     */
    public function __construct(\mysqli $mysqli, string $tableName, int $maxAge=86400)
    {
        $this->dbConnection = $mysqli;
        $this->dbTable = $tableName;
        $this->m_maxAge = $maxAge;
        
        
        $createSessionsTableQuery = 
            "CREATE TABLE IF NOT EXISTS `" . $this->dbTable . "` (
                `id` varchar(32) NOT NULL,
                `modified_timestamp` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `data` mediumtext,
                PRIMARY KEY (`id`),
                KEY `modified_timestamp` (`modified_timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        $this->dbConnection->query($createSessionsTableQuery);
    }
    
    
    public function open($savePath, $sessionName)
    {
        $sql =
            "DELETE FROM `" . $this->dbTable . "` " . 
            "WHERE `modified_timestamp` < (NOW() - INTERVAL " . $this->m_maxAge . " SECOND)";
        
        return $this->dbConnection->query($sql);
    }
    
    
    public function close()
    {
        return $this->dbConnection->close();
    }
    
    
    public function read($id)
    {
        $sql = 
            "SELECT `data` " . 
            "FROM `" . $this->dbTable . "` " . 
            "WHERE `id` = '" . $id . "'";
        
        $result = $this->dbConnection->query($sql);
        
        if ($result === FALSE)
        {
            $result = "";
        }
        else
        {
            $row = $result->fetch_assoc();
            $result = $row['data'];
            $result = $result === null ? "" : $result;
        }
        
        return $result;
    }
    
    
    public function write($id, $data)
    {
        $sql = "REPLACE INTO `" . $this->dbTable . "` (id, data)" . 
               " VALUES('" . $id . "', '" . $data . "')";
        
        return $this->dbConnection->query($sql);
    }
    
    
    public function destroy($id)
    {
        $sql = "DELETE FROM `" . $this->dbTable . " WHERE `id` = '" . $id . "'";
        return $this->dbConnection->query($sql);
    }
    
    
    public function gc($maxlifetime)
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE `timestamp` < '%s'", 
            $this->dbTable, 
            time() - intval($maxlifetime)
        );
        
        return $this->dbConnection->query($sql);
    }
}
