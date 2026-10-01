<!--Concatenador con las partes Head-Heater-Aside. -->
<?php
require('ASSETS/PLANTILLAS/Plantilla-Head-Heater-Aside-index.php');
error_reporting(E_ERROR |  E_PARSE);
?>
<!--Página de Bienvenida (Parte Secttion)-->
<section>
	<div class="Section-css"> 
		<center>
			<div class="form">
				<form action="LOGICA/L_Validar_Ingreso.php" method="post">
					<br>  
					<h1>Ingresar al Sistema </h1> <br>
					<label for="usuario" >Usuario:</label> <br/>
					<input name="user" type="text" required><br/>    
					<label for="password" >Password:</label> <br/>
					<input name="pass" type="password" required><br/> 
					<br>
					<input type="submit" class="btn" value="Ingresar">
					<br><br> 
				</form>
			</div>
		</center>
	</div>
</section>
<!--Concatenador con la parte Footer. -->
<?php
require('ASSETS/PLANTILLAS/Plantilla-Footer.html');
error_reporting(E_ERROR |  E_PARSE);
?>
