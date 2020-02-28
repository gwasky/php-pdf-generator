<?php

/**
 * Description of PDOConnection
 *
 * @author gibson
 */

set_time_limit(7800);

class PDOConnection {
    //put your code here

    private $dbh;

    function __construct() {
        try {

            $db_username    = "PROMOTIONS";
            $db_password    = "PROMUSR123";
            $tns = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = 172.27.98.149)(PORT = 1524))(ADDRESS = (PROTOCOL = TCP)(HOST = 172.27.98.150)(PORT = 1524))(LOAD_BALANCE = yes)(CONNECT_DATA = (SERVER = DEDICATED)(SERVICE_NAME = KIKADB)(FAILOVER_MODE =(TYPE = SELECT)(METHOD = BASIC)(RETRIES = 180)(DELAY = 5))))";

            $this->dbh = new PDO("oci:dbname=" . $tns . ";charset=utf8", $db_username, $db_password, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC));

        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function select($sql) {
        $sql_stmt = $this->dbh->prepare($sql);
        $sql_stmt->execute();
        $result = $sql_stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function insert($sql) {
        $sql_stmt = $this->dbh->prepare($sql);
        try {
            $result = $sql_stmt->execute();
        } catch (PDOException $e) {
            trigger_error('Error occured while trying to insert into the DB:' . $e->getMessage(), E_USER_ERROR);
        }
        if ($result) {
            return $sql_stmt->rowCount();
        }
    }

    function __destruct() {
        $this->dbh = NULL;
    }
}
