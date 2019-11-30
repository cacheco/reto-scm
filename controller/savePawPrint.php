<?php
	include("../clases/gestionconfadmin.class.php");
	
	$miGestionConfAdmin = new GestionConfAdmin();
	
	$nombreRelease = $_POST['nombreRelease'];
	$artefactos = json_decode( urldecode($_POST['arrayArtefactosSerialized']), true );
	$artefactosParametros = json_decode( urldecode($_POST['arrayArtefactosParametrosSerialized']), true );
	
	$miGestionConfAdmin->crearRelease($nombreRelease);
	
	if( $miGestionConfAdmin->insert_id > 0 ){
		$idRelease =  $miGestionConfAdmin->insert_id;
		
		foreach($artefactos as $artefacto){
			$miGestionConfAdmin->insertarHuellaRelease( $idRelease, $artefacto['nombreArtefacto'], $artefacto['huellaArtefacto']);
		}
		
		foreach($artefactosParametros as $artefacto){
			$miGestionConfAdmin->insertarHuellaRelease( $idRelease, $artefacto['nombreArtefacto'], $artefacto['huellaArtefacto'], "2");
		}
	}
	
	echo json_encode( ['status' => "success"]);
?>