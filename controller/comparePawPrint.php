<?php
	include("../clases/gestionconfadmin.class.php");
	include("../clases/gestionconf.class.php");
	include("../clases/connect_db.class.php");
	
	$host = $_POST['dbHost'];
	$user = $_POST['dbUser'];
	$pass = $_POST['dbPass'];
	$dbname = $_POST['dbName'];
	$idRelease = $_POST['idRelease'];
	$configurationTable = $_POST['configurationTable'];
	
	$miGestionConfAdmin = new GestionConfAdmin();
	$miGestionConfAdmin->getHuellasRelease( $idRelease );
	
	$miConnectDb = new connect_db( $host, $user, $pass, $dbname );
	$miGestionConf = new GestionConf($miConnectDb);

	$miGestionConf->getTables();
	$tablas = $miGestionConf->result;

	$arrayHuellasNuevas = [];
	$arrayHuellasNuevasParametros = [];
	foreach($tablas as $tabla){
		$miGestionConf->getTableDefinition( $tabla->name );
		
		$arrayHuellasNuevas[$tabla->name] = hash('sha256', $miGestionConf->result['TableScript']);
	}
	
	$miGestionConf->getObjectsDefinition();
	
	foreach($miGestionConf->result as $object){
		$arrayHuellasNuevas[$object->RoutineName] = hash('sha256', $object->definition);
	}
	
	if( $configurationTable != "" ){
		$miGestionConf->getConfigurationTable( $configurationTable );
		
		foreach($miGestionConf->result as $object){
			$arrayHuellasNuevasParametros[$object->nombreParametro] = hash('sha256', $object->valorParametro);
		}
	}
	
	$html = '<table id="tablaHuellasComparacion" border="1" cellspacing="0"><tr><td>Release</td><td>Artefacto</td><td>Huella release (SHA256)</td><td>Huella actual (SHA256)</td><td>Estado</td></tr>';
	
	$result = ["status" => "success"];
	
	foreach( $miGestionConfAdmin->result as $huellasAnteriores ){
		
		if( $huellasAnteriores->tipoparametro != "1" ){
			continue;
		}
		
		if( isset( $arrayHuellasNuevas[$huellasAnteriores->nombreArtefacto] ) ){
			$huellaNueva = $arrayHuellasNuevas[$huellasAnteriores->nombreArtefacto];
			unset($arrayHuellasNuevas[$huellasAnteriores->nombreArtefacto]);
		}else{
			$huellaNueva = "";
		}
		
		$color = "white";
		$estado = "OK";
		if( $huellasAnteriores->huella != $huellaNueva ){
			$color = "red";
			$estado = "ERROR";
		}
		
		$html .= "<tr class=\"artefacto\">";
			$html .= "<td>".$huellasAnteriores->nombre."</td>";
			$html .= "<td>".$huellasAnteriores->nombreArtefacto."</td>";
			$html .= "<td>".$huellasAnteriores->huella."</td>";
			$html .= "<td>".$huellaNueva."</td>";
			$html .= '<td style="background-color: '.$color.';">'.$estado.'</td>';
		$html .= "</tr>";
	}
	
	foreach( $arrayHuellasNuevas as $nombreArtefacto => $huellaNuevaSobrante ){
		$html .= "<tr class=\"artefacto\">";
			$html .= "<td></td>";
			$html .= "<td>".$nombreArtefacto."</td>";
			$html .= "<td></td>";
			$html .= "<td>".$huellaNuevaSobrante."</td>";
			$html .= '<td style="background-color: red;">ERROR</td>';
		$html .= "</tr>";
	}
	
	$html .= "</table>";
	
	$html .= '<br /><table id="tablaHuellasComparacion" border="1" cellspacing="0"><tr><td colspan="5">Comparacion parametros</td></tr><tr><td>Release</td><td>Artefacto</td><td>Huella release (SHA256)</td><td>Huella actual (SHA256)</td><td>Estado</td></tr>';
	
	reset($miGestionConfAdmin->result);
	
	foreach( $miGestionConfAdmin->result as $huellasAnteriores ){
		
		if( $huellasAnteriores->tipoparametro != "2" ){
			continue;
		}
		
		if( isset( $arrayHuellasNuevasParametros[$huellasAnteriores->nombreArtefacto] ) ){
			$huellaNueva = $arrayHuellasNuevasParametros[$huellasAnteriores->nombreArtefacto];
			unset($arrayHuellasNuevasParametros[$huellasAnteriores->nombreArtefacto]);
		}else{
			$huellaNueva = "";
		}
		
		$color = "white";
		$estado = "OK";
		if( $huellasAnteriores->huella != $huellaNueva ){
			$color = "red";
			$estado = "ERROR";
		}
		
		$html .= "<tr class=\"artefacto\">";
			$html .= "<td>".$huellasAnteriores->nombre."</td>";
			$html .= "<td>".$huellasAnteriores->nombreArtefacto."</td>";
			$html .= "<td>".$huellasAnteriores->huella."</td>";
			$html .= "<td>".$huellaNueva."</td>";
			$html .= '<td style="background-color: '.$color.';">'.$estado.'</td>';
		$html .= "</tr>";
	}
	
	foreach( $arrayHuellasNuevasParametros as $nombreArtefacto => $huellaNuevaSobrante ){
		$html .= "<tr class=\"artefacto\">";
			$html .= "<td></td>";
			$html .= "<td>".$nombreArtefacto."</td>";
			$html .= "<td></td>";
			$html .= "<td>".$huellaNuevaSobrante."</td>";
			$html .= '<td style="background-color: red;">ERROR</td>';
		$html .= "</tr>";
	}
	
	$html .= "</table>";
	
	$result['html'] = $html;
	
	echo json_encode( $result );
?>