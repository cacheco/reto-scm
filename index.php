<?php
	include("clases/gestionconfadmin.class.php");
	
	$miGestionConfAdmin = new GestionConfAdmin();
?>
<html>
	<body>
		<table>
			<tr>
				<td>host</td><td><input type="text" id="dbHost" value="localhost" /></td>
			</tr>
			<tr>
				<td>user</td><td><input type="text" id="dbUser" value="username" /></td>
			</tr>
			<tr>
				<td>pass</td><td><input type="password" id="dbPass" value="password" /></td>
			</tr>
			<tr>
				<td>database</td><td><input type="text" id="dbName" value="schema" /></td>
			</tr>
			<tr>
				<td>tabla parametros</td><td><input type="text" id="configurationTable" value="tablaconfiguracion" /></td>
			</tr>
		</table>
		
		<div>
			<table>
				<tr>
					<td><button onclick="getPawPrint(); return false;" type="button">Calcular huellas</button></td><td></td>
				</tr>
				<tr>
					<td><button onclick="savePawPrint(); return false;" type="button">Guardar huellas en release</button></td><td>Nombre release:<input type="text" id="nombreRelease" /></td>
				</tr>
				<tr>
					<td><button onclick="comparePawPrint(); return false;" type="button">Comparar huellas</button></td><td>
					<select id="idRelease">
						<option value="">Seleccione...</option>
						<?php
							$miGestionConfAdmin->getReleases();
							
							foreach( $miGestionConfAdmin->result as $release ){
								echo '<option value="'.$release->id.'">'.$release->nombre.'</option>';
							}
						?>
					</select>
				</td>
				</tr>
			</table>
		</div>
		
		<div id="result">
			
		</div>
		
		<script src="plugins/jQuery/jQuery-2.1.4.min.js"></script>
		
		<script>
			function getPawPrint(){
				var dbHost = $("#dbHost").val();
				var dbUser = $("#dbUser").val();
				var dbPass = $("#dbPass").val();
				var dbName = $("#dbName").val();
				var configurationTable = $("#configurationTable").val();
				
				$.ajax({
					type : "POST",
					url : "controller/getPawPrint.php",
					data : { dbHost : dbHost, dbUser : dbUser, dbPass : dbPass, dbName : dbName, configurationTable : configurationTable },
					dataType : 'JSON',
					success : function(data) {
						$("#result").html(data.html);
					}
				});
			}
			
			function savePawPrint(){
				var nombreRelease = $("#nombreRelease").val();
				
				if( nombreRelease == "" ){
					 alert("Debe especificar el nombre del release");
					 return;
				}
				
				var arrayArtefactos = [];
				$("#tablaHuellas tr.artefacto").each(function(){
					var artefacto = {
						nombreArtefacto : $(this).find(".nombreArtefacto").html(),
						huellaArtefacto : $(this).find(".huellaArtefacto").html(),
					}
					
					arrayArtefactos.push(artefacto);
				});
				
				var arrayArtefactosSerialized = JSON.stringify( arrayArtefactos );
				
				var arrayArtefactosParametros = [];
				$("#tablaHuellasParametros tr.artefacto").each(function(){
					var artefacto = {
						nombreArtefacto : $(this).find(".nombreArtefacto").html(),
						huellaArtefacto : $(this).find(".huellaArtefacto").html(),
					}
					
					arrayArtefactosParametros.push(artefacto);
				});
				
				var arrayArtefactosParametrosSerialized = JSON.stringify( arrayArtefactosParametros );
				
				$.ajax({
					type : "POST",
					url : "controller/savePawPrint.php",
					data : { nombreRelease : nombreRelease, arrayArtefactosSerialized : arrayArtefactosSerialized, arrayArtefactosParametrosSerialized : arrayArtefactosParametrosSerialized },
					dataType : 'JSON',
					success : function(data) {
						if(data.status == "success"){
							alert("Se crearon las huellas exitosamente para el release");
							location.reload();
						}else{
							alert("Error");
						}
					},
					error : function(data){
						alert("Error");
					}
				});
			}
			
			function comparePawPrint(){
				var dbHost = $("#dbHost").val();
				var dbUser = $("#dbUser").val();
				var dbPass = $("#dbPass").val();
				var dbName = $("#dbName").val();
				var idRelease = $("#idRelease").val();
				var configurationTable = $("#configurationTable").val();
				
				if( idRelease == "" ){
					 alert("Debe seleccionar un release");
					 return;
				}
				
				$.ajax({
					type : "POST",
					url : "controller/comparePawPrint.php",
					data : { idRelease : idRelease, dbHost : dbHost, dbUser : dbUser, dbPass : dbPass, dbName : dbName, configurationTable : configurationTable },
					dataType : 'JSON',
					success : function(data) {
						$("#result").html(data.html);
					}
				});
			}
		</script>
	</body>
</html>
