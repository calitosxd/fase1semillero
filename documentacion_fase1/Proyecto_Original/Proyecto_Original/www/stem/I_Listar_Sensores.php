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

<?php
include('LOGICA/L_Funciones.php');
error_reporting(E_ERROR | E_PARSE);

// Si el usuario ha enviado un rango de fechas, se usa ese rango; de lo contrario, se usa la fecha actual
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Lógica para exportar los datos a CSV
if (isset($_GET['exportar']) && $_GET['exportar'] == '1') {
    // Obtener los datos filtrados
    $datos_exportar = Funcion_Listar_Sensados_Rango_CSV($fecha_inicio, $fecha_fin);

    // Crear el archivo CSV
    $filename = "datos_sensores_" . $fecha_inicio . "_a_" . $fecha_fin . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Escribir los encabezados una sola vez
    fputcsv($output, array('Id_Sensado', 'Fecha', 'Lux Int', 'Lux Ext', 'Tem Int', 'Hum Int', 'Tem Ext', 'Hum Ext', 'Hum Sue 100', 'Hum Sue 75', 'Hum Sue 50'));

    // Escribir los datos_exportar filtrados
    if (!empty($datos_exportar)) {
        foreach ($datos_exportar as $dato_exportar) {
            fputcsv($output, $dato_exportar);
        }
    }

    fclose($output);
    exit(); // Terminar el script inmediatamente después de descargar el archivo
}

// Si no se está exportando, seguir con el resto del código de la página.
require('ASSETS/PLANTILLAS/Plantilla-Head-Heater-Aside-Admin.php');

// Obtener los datos para mostrar en la tabla
$datos = Funcion_Listar_Sensados_Rango_HTML($fecha_inicio, $fecha_fin);
?>

<!-- Aquí comienza el HTML y el resto del código PHP -->
<section>
    <div class="Section-css">
        <center>
        <h1>Datos entregados por los sensores - Desde: <?php echo $fecha_inicio; ?> Hasta: <?php echo $fecha_fin; ?></h1>
        </br>

        <!-- Formulario para seleccionar el rango de fechas y exportar datos -->
        <form method="GET" action="">
            <!-- Se selecciona el rango de fechas al que se desea ver los datos registrados  -->
            <label for="fecha_inicio">Fecha Inicio:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
            <label for="fecha_fin">Fecha Fin:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
            <!-- Boton que genera la tabla de datos a partir del rango de fechas seleccionado  -->
            <button type="submit" name="filtrar" class="btn">Filtrar</button>
            <!-- Boton que exporta y descarga la tabla generada en un archivo con formato *.csv  -->
            <button type="submit" name="exportar" value="1" class="btn">Exportar a CSV</button>
        </form>

        </br>

        <table class="table">
            <thead>
                <tr>
                    <th>Id_Sensado</th>
                    <th>Fecha</th>
                    <th>Lux Int</th>
                    <th>Lux Ext</th>
                    <th>Tem Int</th>
                    <th>Hum Int</th>
                    <th>Tem Ext</th>
                    <th>Hum Ext</th>
                    <th>Hum Sue 100</th>
                    <th>Hum Sue 75</th>
                    <th>Hum Sue 50</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($datos)) {
                    foreach ($datos as $dato) {
                        // Formatear la fecha para mostrarla completa
                        $fecha_completa = date('Y-m-d h:i A', strtotime($dato[1]));
                        echo "<tr>";
                        echo "<td>{$dato[0]}</td>";
                        echo "<td>{$fecha_completa}</td>";
                        echo "<td>{$dato[2]}</td>";
                        echo "<td>{$dato[3]}</td>";
                        echo "<td>{$dato[4]}</td>";
                        echo "<td>{$dato[5]}</td>";
                        echo "<td>{$dato[6]}</td>";
                        echo "<td>{$dato[7]}</td>";
                        echo "<td>{$dato[8]}</td>";
                        echo "<td>{$dato[9]}</td>";
                        echo "<td>{$dato[10]}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='11'>No hay datos para este rango de fechas</td></tr>";
                }
                ?>
            </tbody>
        </table>
        </br>
        </center>
    </div>
</section>

<!-- Concatenador con la parte Footer. -->
<?php
require('ASSETS/PLANTILLAS/Plantilla-Footer.html');
error_reporting(E_ERROR | E_PARSE);
?>
