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
    // Se da inicio a la creación de variables globales. "Punto de inicio."
    require('ASSETS/PLANTILLAS/Plantilla-Head-Heater-Aside-Admin.php');
    error_reporting(E_ERROR |  E_PARSE);
?>
<!--Página de Bienvenida (Parte Secttion)-->
<section>
    <center>
    <div class="Section-css">
        <br/><br/><br/><br/>
        <table>
            <th colspan="4">Control de Usuarios</th>
            <tr>    
                <!--Se diferencia los permisos. ROL para el Admin (modo dios) "id_pk_rol_fk = 0" y ROL Docente "id_pk_rol_fk = 2" -->
                <?php
                if($rol == 0) {
                ?>
                    <td>                    
                        <!-- Botón que envía el formulario -->
                        <button class="btn" onclick="document.getElementById('formRegistrar').submit();">Registrar Usuarios</button>

                        <!-- Formulario para enviar los datos -->
                        <form id="formRegistrar" method="POST" action="I_Usuarios_Registrar.php">
                            <input type="hidden" name="rol" value="<?php echo $rol; ?>">
                            <input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
                        </form>              		
                    </td>                
                    <td>
                        <!-- Botón que envía el formulario -->
                        <button class="btn" onclick="document.getElementById('formBuscar').submit();">Buscar Usuarios</button>

                        <!-- Formulario para enviar los datos -->
                        <form id="formBuscar" method="POST" action="I_Usuarios_Buscar.php">
                            <input type="hidden" name="rol" value="<?php echo $rol; ?>">
                            <input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
                        </form> 
                    </td>  		
                <?php
                } elseif($rol == 1) {
                ?>               
                    <td>
                        <!-- Botón que envía el formulario -->
                        <button class="btn" onclick="document.getElementById('formBuscar').submit();">Buscar Usuarios</button>

                        <!-- Formulario para enviar los datos -->
                        <form id="formBuscar" method="POST" action="I_Usuarios_Buscar.php">
                            <input type="hidden" name="rol" value="<?php echo $rol; ?>">
                            <input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
                        </form> 
                    </td>
                <?php
                } elseif($rol == 2) {
                ?>
                    <td>                    
                        <!-- Botón que envía el formulario -->
                        <button class="btn" onclick="document.getElementById('formRegistrar').submit();">Registrar Usuarios</button>

                        <!-- Formulario para enviar los datos -->
                        <form id="formRegistrar" method="POST" action="I_Usuarios_Registrar.php">
                            <input type="hidden" name="rol" value="<?php echo $rol; ?>">
                            <input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
                        </form>              		
                    </td>                
                    <td>
                        <!-- Botón que envía el formulario -->
                        <button class="btn" onclick="document.getElementById('formBuscar').submit();">Buscar Usuarios</button>

                        <!-- Formulario para enviar los datos -->
                        <form id="formBuscar" method="POST" action="I_Usuarios_Buscar.php">
                            <input type="hidden" name="rol" value="<?php echo $rol; ?>">
                            <input type="hidden" name="permiso" value="<?php echo $permiso; ?>">
                        </form> 
                    </td> 
                <?php
                }	
                ?>              
            </tr>
        </table>
        <br/><br/><br/><br/>
    </center>
</section>

<!--Concatenador con la parte Footer. -->
<?php
    require('ASSETS/PLANTILLAS/Plantilla-Footer.html');
    error_reporting(E_ERROR |  E_PARSE);
?>
