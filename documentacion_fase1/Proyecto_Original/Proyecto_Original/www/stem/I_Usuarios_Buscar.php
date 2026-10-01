<?php
// Del BackEnd (L_Validar_Ingreso.php), se recibe los datos "rol" y "permisos" del usuario logueado correctamente.
if (isset($_POST['rol']) && isset($_POST['permiso'])) {
    $rol = $_POST['rol'];
    $permiso = $_POST['permiso'];
} else {
    // Si por algún motivo, no llegan datos, las variables se declaran vacías.
    $rol = null;
    $permiso = null;
}
// Impresion de prueba de que el dato "rol" y "permisos" llegaron correctamente.
//echo "Rol: " . $rol . "<br>";
//echo "Permisos: " . $permiso;
?>

<!--Concatenador con las partes Head-Heater-Aside. -->
<?php
	require('ASSETS/PLANTILLAS/Plantilla-Head-Heater-Aside-Admin.php');
	error_reporting(E_ERROR |  E_PARSE);
?>
<!--Página Agregar Estudiantes (Parte Secttion)-->
	<section>
	    <div class="Section-css"> 
            <center>
                <br/><br/>
                <div class="form">
                    <h3>BURCAR PERSONA</h3>
        	        <form action="I_Usuarios_Buscar_Resultado.php" method="post">
                        <!-- Variables ocultas para enviar junto con el formulario -->
                        <input type="hidden" name="roles" value="<?php echo $rol; ?>">
                        <input type="hidden" name="permisos" value="<?php echo $permiso; ?>">

                        <br><label for="id_usuario">Ingrese Identificacion:</label></br>
                        <br><input style="text-align:center;" name="id_usuario" type="text" maxlength="10" required="Ingresar Numero de Identificacion.">
                        </br></br>
                        <table>
                            <th ROWSPAN="1">                               
                                    <input name="submit_Buscar_Persona" type="submit"  class="btn" value="Buscar Usuario">
                            </th>                
                            <th ROWSPAN="1">
                                    <!-- <button onclick="javascript:history.back(-1);" title="Ir a la página anterior" class="btn">Regresar</button> -->
                                    <a href='javascript:history.back(-1);' title='Ir la página anterior' class="btn" value="Regresar">Regresar</a>        
                            </th>
                        </table>
        	        </form>
                </div>
                <br/><br/>
                </center>
        </div>
	</section>
<!--Concatenador con la parte Footer. -->
<?php
	require('ASSETS/PLANTILLAS/Plantilla-Footer.html');
	error_reporting(E_ERROR |  E_PARSE);
?>