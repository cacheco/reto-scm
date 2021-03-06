<?php
ini_set('display_errors','1');
class connect_db{

    var $conect;

    var $host;
    var $user;
    var $password;
    var $name_db;

    function __construct( $host, $bd_user, $bd_password, $bd_name ){
        if ( session_status() == PHP_SESSION_NONE ) {
            session_start();
        }
        $this->host = $host;
        $this->user = $bd_user;
        $this->password = $bd_password;
        $this->name_db = $bd_name;
    }

    function Conectarse(){
        try{
            $conn = new PDO( "sqlsrv:server=$this->host ; Database=$this->name_db", "$this->user", "$this->password");
            $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

            $this->conect = $conn;
        }catch( Exception $e ){
            die( print_r( $e->getMessage() ) );
        }

        return true;
    }

    function Desconectarse(){
        $this->conect = null;
    }

}

?>