<?php
ini_set('display_errors','1');
include("connect_admin_db.class.php");

class GestionConfAdmin{

    var $connectDb;
    var $hasValue;
	var $result;
	var $consulta;

    function __construct(){
        $this->connectDb = new connect_admin_db();
        $this->hasValue = false;
		$this->result = [];
    }
	
	function getReleases() {
        $this->result = array();
        try {
            if ($this->connectDb->Conectarse() == true) {
                $params = array();

                $query = "SELECT * FROM release";
				
                $this->consulta = $this->connectDb->conect->prepare($query);
                $this->consulta->execute($params);
                $this->consulta->setFetchMode(PDO::FETCH_OBJ);

                while ($row = $this->consulta->fetch()) {
                    $this->hasValue = true;
                    $this->result[] = $row;
                }

                $this->connectDb->Desconectarse();
            }
        }
        catch (PDOException $e) {
            print_r($e);
            $this->set_error($e);
            return false;
        }

        return true;
    }
	
	function crearRelease( $nombreRelease ) {
        $this->insert_id = 0;
        
        try {
            if ($this->connectDb->Conectarse() == true) {
                $params = array(
                          "nombre" => $nombreRelease,
                    );

                $query = "INSERT INTO release (
                                      nombre
                                   )
                            VALUES (                
                                      :nombre
                                   );";

                $this->consulta = $this->connectDb->conect->prepare($query);
                $this->consulta->execute($params);
                $this->insert_id = $this->connectDb->conect->lastInsertId();
                $this->connectDb->Desconectarse();
            }
        }
        catch (PDOException $e) {
            print_r($e);
            $this->set_error($e);
            return false;
        }
        return true;
    }
	
	function insertarHuellaRelease( $idRelease, $nombreArtefacto, $huella, $tipo = "1" ) {
        $this->insert_id = 0;
        
        try {
            if ($this->connectDb->Conectarse() == true) {
                $params = array(
                          "idRelease" => $idRelease,
						  "nombreArtefacto" => $nombreArtefacto,
						  "huella" => $huella,
						  "tipoparametro" => $tipo,
                    );

                $query = "INSERT INTO huellas (
                                      idRelease,
									  nombreArtefacto,
									  huella,
									  tipoparametro
                                   )
                            VALUES (                
                                      :idRelease,
									  :nombreArtefacto,
									  :huella,
									  :tipoparametro
                                   );";

                $this->consulta = $this->connectDb->conect->prepare($query);
                $this->consulta->execute($params);
                $this->insert_id = $this->connectDb->conect->lastInsertId();
                $this->connectDb->Desconectarse();
            }
        }
        catch (PDOException $e) {
            print_r($e);
            $this->set_error($e);
            return false;
        }
        return true;
    }
	
	function getHuellasRelease( $idRelease ) {
        $this->result = array();
        try {
            if ($this->connectDb->Conectarse() == true) {
                $params = array(
					"idRelease" => $idRelease
				);

                $query = "SELECT * FROM huellas LEFT JOIN release ON idRelease = id WHERE idRelease = :idRelease";
				
                $this->consulta = $this->connectDb->conect->prepare($query);
                $this->consulta->execute($params);
                $this->consulta->setFetchMode(PDO::FETCH_OBJ);

                while ($row = $this->consulta->fetch()) {
                    $this->hasValue = true;
                    $this->result[] = $row;
                }

                $this->connectDb->Desconectarse();
            }
        }
        catch (PDOException $e) {
            print_r($e);
            $this->set_error($e);
            return false;
        }

        return true;
    }

    function set_error( $e ){
        $this->error = $e;
    }

    function get_error(){
        return $this->error;
    }

    function get_error_message(){
        return $this->error->getMessage();
    }
}
?>