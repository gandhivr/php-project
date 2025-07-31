<?php
// config/database.php
//for database conection
class Database {
    private $host = "localhost";
    private $database_name = "inventory_system";
    private $username = "root";
    private $password = "";
    public $conn;
//this function is to connect to the database
    public function getConnection() {
        $this->conn = null;//it sets $this->conn = null at first(empty)

        //it try to connect using php object PDO.it need localhost and database name
        //Username and Password
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database_name,
                $this->username,
                $this->password
            );//if there is a problem it will give error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }//if somthing goes wrong it will shaw an error message
        //andexplain what happended
        return $this->conn;//it returns the connection link so the code can 
        //use it to run database commands
    }
}
?>
