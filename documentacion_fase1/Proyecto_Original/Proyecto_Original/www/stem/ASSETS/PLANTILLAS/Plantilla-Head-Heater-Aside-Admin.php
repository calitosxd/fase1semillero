<!DOCTYPE html>
<!--Esta página tenemos diseñada la plantilla donde se encuentra el Body-Head y Header.-->
<html>
	<head>
		<meta charset="UTF-8">
		<title>IWS</title>
		<link rel="shortcut icon" type="text/css" href="ASSETS/IMG/estudiantes.png">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="ASSETS/CSS/css.css">
		<script src="ASSETS/JS/script.js" language="Javascript"></script> 
	</head>
	<!--Sección Body-->
	<body>
	<div id="Body-css">
		<!--Sección Header-->
		<HEADER>
			<div class="Header-css">
				<br><br>
				<center><h2>Sistema Monitoreo de Variables Miro-Ambientales</h2></center>
				<br><br>
			</div>
			<!--En esta sección tenemos los botones de las opciones para redirigir a las diferentes páginas.-->
			<div class="Aside-css">
				<center>
					<!--Botones Principales -->
					<br/>
					<ul>
					<?php
						// El rol "Super Admin", puede acceder a infomacion de los usuarios y datos entregados por los sensores.
						if ($rol == 0) {
						?>
							<li>
								<!-- Botón que envía el formulario -->
								<button class="btn" onclick="document.getElementById('redirectForm').submit();">Control Usuarios</button>

								<!-- Formulario para enviar los datos -->
								<form id="redirectForm" method="POST" action="I_Usuarios_Control.php">
									<input type="hidden" name="rol" value="<?php echo $rol; ?>">
									<input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
								</form>
							</li>
							<li>
								<button class="btn" onclick="location.href='I_Listar_Sensores.php'">Monitoreo Sensores</button>
							</li>
						<?php		
								}
							// El rol "Administrativos", puede acceder a infomacion de los usuarios pero no a datos entregados por los sensores.
							elseif ($rol == 1)
								{
						?>
							<li>
								<!-- Botón que envía el formulario -->
								<button class="btn" onclick="document.getElementById('redirectForm').submit();">Control Usuarios</button>

								<!-- Formulario para enviar los datos -->
								<form id="redirectForm" method="POST" action="I_Usuarios_Control.php">
									<input type="hidden" name="rol" value="<?php echo $rol; ?>">
									<input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
								</form>
							</li>
						<?php
								}
							// El rol "Docente", puede acceder a infomacion de los usuarios y datos entregados por los sensores.
							elseif ($rol == 2)
								{
						?>
							<li>
								<!-- Botón que envía el formulario -->
								<button class="btn" onclick="document.getElementById('redirectForm').submit();">Control Usuarios</button>

								<!-- Formulario para enviar los datos -->
								<form id="redirectForm" method="POST" action="I_Usuarios_Control.php">
									<input type="hidden" name="rol" value="<?php echo $rol; ?>">
									<input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
								</form>
							</li>
								<li>
									<button class="btn" onclick="location.href='I_Listar_Sensores.php'">Monitoreo Sensores</button>
								</li>
						<?php
								}
							// El rol "Estudiantes" o "Invitados". solo puede ver los datos provenientes de los sensores.
							elseif ($rol == 3 || $rol == 4)
								{
						?>
								<li>
									<!-- Botón que envía el formulario -->
									<button class="btn" onclick="document.getElementById('redirectForm').submit();">Monitoreo Sensores</button>

									<!-- Formulario para enviar los datos -->
									<form id="redirectForm" method="POST" action="I_Listar_Sensores.php">
										<input type="hidden" name="rol" value="<?php echo $rol; ?>">
										<input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
									</form>								
								</li>
						<?php
								}
						?>
						
						<!--Botón para cerrar la sesión.-->
						<li>
							<button class="btn" onclick="location.href='index.php'">Cerrar Sesión</button>
						</li>	
					</ul>
					<br/>
				</center>
			</div>
	</HEADER>