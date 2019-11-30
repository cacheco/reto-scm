<?php
	include("../clases/gestionconf.class.php");
	include("../clases/connect_db.class.php");
	
	$host = $_POST['dbHost'];
	$user = $_POST['dbUser'];
	$pass = $_POST['dbPass'];
	$dbname = $_POST['dbName'];
	$configurationTable = $_POST['configurationTable'];
	
	$miConnectDb = new connect_db( $host, $user, $pass, $dbname );

	$miGestionConf = new GestionConf($miConnectDb);
	
	$miGestionConf->validateSpHelpTableExist();
	if( !$miGestionConf->hasValue ){
		$miGestionConf->crearSpHelpTable();
	} 

	$miGestionConf->getTables();
	$tablas = $miGestionConf->result;

	$html = '<table id="tablaHuellas" border="1" cellspacing="0"><tr><td>Artefacto</td><td>Huella (SHA256)</td></tr>';
	
	$result = ["status" => "success"];

	foreach($tablas as $tabla){
		$miGestionConf->getTableDefinition( $tabla->name );
		
		$html .= "<tr class=\"artefacto\">";
			$html .= "<td class=\"nombreArtefacto\">".$tabla->name."</td>";
			$html .= "<td class=\"huellaArtefacto\">".hash('sha256', $miGestionConf->result['TableScript'])."</td>";
		$html .= "</tr>";
	}
	
	$miGestionConf->getObjectsDefinition();
	
	foreach($miGestionConf->result as $object){
		$html .= "<tr class=\"artefacto\">";
			$html .= "<td class=\"nombreArtefacto\">".$object->RoutineName."</td>";
			$html .= "<td class=\"huellaArtefacto\">".hash('sha256', $object->definition)."</td>";
		$html .= "</tr>";
	}
	
	$html .= "</table>";
	
	if( $configurationTable != "" ){
		$miGestionConf->getConfigurationTable( $configurationTable );
		
		if( $miGestionConf->hasValue ){
			$html .= '<br /><table id="tablaHuellasParametros" border="1" cellspacing="0"><tr><td colspan="2">Tabla de parametros</td></tr><tr><td>Artefacto</td><td>Huella (SHA256)</td></tr>';
			
			foreach($miGestionConf->result as $object){
				$html .= "<tr class=\"artefacto\">";
					$html .= "<td class=\"nombreArtefacto\">".$object->nombreParametro."</td>";
					$html .= "<td class=\"huellaArtefacto\">".hash('sha256', $object->valorParametro)."</td>";
				$html .= "</tr>";
			}
			
			$html .= "</table>";
		}
	}
	
	
	$result['html'] = $html;
	
	echo json_encode( $result );
?>