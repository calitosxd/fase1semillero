<?php
// Del BackEnd (L_Validar_Ingreso.php), se recibe los datos "rol" y "permisos" del usuario logueado correctamente.
if (isset($_POST['rol']) && isset($_POST['permiso'])) {
    $rol = $_POST['rol'];
    $permisos = $_POST['permiso'];
} else {
	// Si por algun motivo, no llegan datos, las variables se declaran vacias.
    $rol = null;
    $permisos = null;
}
// Impresion de prueba de que el dato "rol" y "permisos" llegaron correctamente.
//echo "Rol: " . $rol . "<br>";
//echo "Permisos: " . $permisos;
?>

<!--Concatenador con las partes Head-Heater-Aside. -->
<?php
	require('ASSETS/PLANTILLAS/Plantilla-Head-Heater-Aside-Admin.php');
	error_reporting(E_ERROR |  E_PARSE);    
?>
<!--Página Agregar Personas (Parte Secttion)-->
	<section>
	    <div class="Section-css"> 
            <center>
            <div class="form">
                <br/>
                <br/>
                <h3>REGISTRO DE USUARIOS</h3><br/>
                <form action="LOGICA/L_Usuarios_Registrar.php" method="post">
                    <!-- Variables ocultas para enviar junto con el formulario -->
                    <input type="hidden" name="roles" value="<?php echo $rol; ?>">
                    <input type="hidden" name="permiso" value="<?php echo $permisos; ?>">

                    <table>
                        <th>Identificacion</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Edad</th>                                                
                        <tr>
                            <td>   
                                <input style="text-align:center;" name="id" type="text" maxlength="10" required="Ingresar Numero de Identificacion.">
                            </td>
                            <td>
                                <input style="text-align:center;" name="nombre" type="text" maxlength="15" required="Ingresar Nombre.">
                            </td>
                            <td>
                                <input style="text-align:center;" name="apellido" type="text" maxlength="20" required="Ingresar Aprellido.">
                            </td>
                            <td>
                                <input style="text-align:center;" name="edad" type="text" maxlength="2" required="Ingresar Edad.">
                            </td>
                        </tr>
                        <th>Genero</th>
                        <th>Telefono</th>
                        <th>Dirección</th>
                        <th>Ciudad</th>
                        <tr>
                            <td>
                                <select name="genero">
                                    <option value="Ninguno" disabled="" selected="">Elija una opción</option>
                                    <option value="Masculino" required="Seleccione una Opción." >Masculino</option>
                                    <option value="Femenino" required="Seleccione una Opción.">Femenino</option>
                                    <option value="Otro" required="Seleccione una Opción.">Otro</option>                  
                                </select>
                            </td>
                            <td>
                                <input style="text-align:center;" name="telefono" type="text" maxlength="10" required="Ingresar Número Telefonico."> 
                            </td>
                            <td>
                                <input style="text-align:center;" name="direccion" type="text" maxlength="25" required="Ingresar Dirección.">
                            </td>
                            <td>
                                <input style="text-align:center;" name="ciudad" type="text" maxlength="25" required="Ingresar Ciudad.">
                            </td>
                        </tr>
                    </table>
                    <br/>
                    <table>
                        <th>Universidad</th>
                        <th>Programa</th>
                        <th>E-Mail</th>
                        <th>Fecha Registro</th>
                        
                        <tr>                            
                            <td>
                            <input style="text-align:center;" name="universidad" type="text" maxlength="25" required="Ingresar el nombre del centro educativo al que pertenece el usuario.">
                            </td>
                            <td>
                                <input style="text-align:center;" name="programa" type="text" maxlength="25" required="Ingresar programa al que pertenece el usuario.">
                            </td>
                            <td>
                                <input style="text-align:center;" name="email" type="text" maxlength="45" required="Ingresar Email.">
                            </td>
                            <td>
                                <?php
                                    date_default_timezone_set('America/Bogota');
                                    $fecha_actual=date("Y-m-d");
                                ?>
                                <input style="text-align:center;" name="fecha_registro" type="DateTime" class="fecha"  value="<?=$fecha_actual?>" readonly="">
                            </td>
                            
                        </tr>
                            <th>Contraseña</th>
                            <th>Rol</th>
                            <th>Permisos sobre Usuarios</th>
                            <th>Estado</th>                         
                        <tr>
                
                            <td>
                                <input style="text-align:center;" name="contrasena" type="text" required="Ingresar Contraseña del Usuario.">
                            </td>                
                            <td>
                                <select name="rol" style="text-align:center;" required="Ingresar el rol de la persona.">
                                        <option value="Ninguno" disabled="" selected="">Elija una opción</option>
                                        <option value="Administrativo" required="Seleccione una Opción.">Administrativo</option>
                                        <option value="Docente" required="Seleccione una Opción.">Docente</option>
                                        <option value="Estudiante" required="Seleccione una Opción.">Estudiante</option>
                                        <option value="Invitado" required="Seleccione una Opción.">Invitado</option>
                                </select> 
                            </td>
                            <td>
                                <input type="checkbox" name="permiso_1[]" value="1">Registrar
                                <br/>
                                <input type="checkbox" name="permiso_1[]" value="3">Ver
                                <br/>
                                <input type="checkbox" name="permiso_1[]" value="5">Editar
                                <br/>
                                <input type="checkbox" name="permiso_1[]" value="10">Eliminar      
                            </td>
                            <td>
                            <select name="estado" style="text-align:center;" required="Ingresar el estado de la persona.">
                                    <option value="Ninguno" disabled="" selected="">Elija una opción</option>
                                    <option value="Activo" required="Seleccione una Opción.">Activo</option>
                                    <option value="Inactivo" required="Seleccione una Opción.">Inactivo</option>
                            </select> 
                            </td>
                        </tr>
                    </table>
                    <br/>                
                    <table>
                        <tr>            
                            <td>                    
                                <input name="submit" type="submit"  class="btn" value="Registrar">
                            </td>
                            <td>
                                <a href='javascript:history.back(-1);' title='Ir la página anterior' class="btn" value="Regresar">Regresar</a>
                            </td>          
                        </tr>
                    </table>
                    <br/>
                    <br/>
                    <br/> 
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