# Especificación de Requerimientos — Fase 2
## Proyecto de Grado: Sistema de Monitoreo Ambiental para Invernadero — COTECNOVA
### Semillero de Investigación COTECTRONIX

---

## Contexto y Alcance

La **Fase 1** del proyecto resolvió el diseño e implementación del hardware IoT (microcontrolador ESP32, sensores DHT20, BH1750 y LM393), la transmisión inalámbrica de datos, y su almacenamiento en una base de datos transaccional (OLTP) plana con un aplicativo web de consulta en tiempo real.

La **Fase 2** parte de los datos históricos recolectados por la Fase 1 (3.553 capturas horarias registradas entre noviembre de 2024 y abril de 2026) y se enfoca en:

1. **Proceso ETL** (Extracción, Transformación y Carga) del dataset crudo hacia un modelo analítico.
2. **Modelado dimensional OLAP** bajo la metodología de Ralph Kimball (Esquema en Estrella).
3. **Cálculo automatizado de indicadores agronómicos** (anomalías y déficit de riego).
4. **Habilitación de analítica multidimensional** para la toma de decisiones basada en datos.

> [!IMPORTANT]
> Los requerimientos de esta fase **no reemplazan** los de la Fase 1. Los complementan y extienden, tomando como entrada directa los datos generados por el sistema transaccional e IoT ya implementado.

---

## 1. Requerimientos Funcionales (RF)

### RF-01 · Extracción Automatizada del Dataset Histórico

| Campo | Detalle |
|---|---|
| **Descripción** | El sistema debe extraer automáticamente el archivo CSV generado por el servidor de la Fase 1 (`datos_sensores_2024-04-16_a_2026-04-16.-v2csv.csv`), que contiene las capturas históricas de los 9 sensores del invernadero, utilizando punto y coma (`;`) como separador y omitiendo las 2 primeras filas de encabezado del equipo. |
| **Entrada** | Archivo CSV plano exportado del servidor transaccional de Fase 1 (ubicado en `documentacion_fase1/backup_servidor/`). |
| **Salida** | DataFrame en memoria con columnas tipadas y listas para transformación. |
| **Prioridad** | Alta |
| **Criterio de aceptación** | El proceso lee correctamente las 3.553 filas y las 11 columnas requeridas (`Id_Sensado`, `Fecha`, `Lux Int`, `Lux Ext`, `Tem Int`, `Tem Ext`, `Hum Int`, `Hum Ext`, `Hum Sue 100`, `Hum Sue 75`, `Hum Sue 50`) sin pérdida de registros. |

---

### RF-02 · Validación y Limpieza de Datos de Entrada

| Campo | Detalle |
|---|---|
| **Descripción** | El sistema debe validar la calidad de los datos extraídos antes de cualquier transformación, verificando: (a) que no existan valores nulos ni vacíos en ninguna columna requerida, (b) que todas las fechas sean parseables al formato `DD/MM/YYYY HH:MM`, (c) que los valores de los 9 sensores sean numéricos finitos (sin `NaN` ni `Inf`), y (d) que el campo `Id_Sensado` sea único y no contenga duplicados. |
| **Entrada** | DataFrame crudo extraído en RF-01. |
| **Salida** | DataFrame validado o excepción explícita con mensaje descriptivo indicando la columna y naturaleza del error. |
| **Prioridad** | Alta |
| **Criterio de aceptación** | Si alguna validación falla, el proceso se detiene inmediatamente con un mensaje de error claro (ejemplo: `"La columna 'Hum Sue 75' contiene mediciones vacías o no finitas"`). No se generan datos parciales ni corruptos. |

---

### RF-03 · Desagregación Atómica de Mediciones (Pivoteo Vertical)

| Campo | Detalle |
|---|---|
| **Descripción** | El sistema debe transformar cada fila horizontal del dataset (1 captura con 9 valores de sensor en columnas) en exactamente 9 registros atómicos verticales, donde cada registro contiene un único `valor_medido` asociado a su sensor (`id_sensor`) y a su surco o zona (`id_surco`) correspondiente. |
| **Entrada** | DataFrame validado (3.553 filas × 9 sensores). |
| **Salida** | Exactamente 31.977 registros de hechos (3.553 × 9). |
| **Mapeo de sensores** | `Lux Int` → sensor 5 / surco 4, `Lux Ext` → sensor 6 / surco 4, `Tem Int` → sensor 1 / surco 4, `Tem Ext` → sensor 3 / surco 4, `Hum Int` → sensor 2 / surco 4, `Hum Ext` → sensor 4 / surco 4, `Hum Sue 100` → sensor 7 / surco 1, `Hum Sue 75` → sensor 8 / surco 2, `Hum Sue 50` → sensor 9 / surco 3. |
| **Prioridad** | Alta |
| **Criterio de aceptación** | El total de hechos generados es estrictamente igual a `capturas × 9`. Si no coincide, el proceso aborta con error de integridad. |

---

### RF-04 · Construcción de la Dimensión Temporal (dim_tiempo)

| Campo | Detalle |
|---|---|
| **Descripción** | El sistema debe generar automáticamente una dimensión de tiempo que desglose cada hora distinta del dataset en los siguientes atributos jerárquicos: `fecha_hora`, `fecha`, `año`, `mes`, `nombre_mes` (en español), `día`, `hora`, `franja_horaria` (Madrugada 00–05, Mañana 06–11, Tarde 12–17, Noche 18–23), `día_semana` (en español), `trimestre`, `semana_año` y `es_fin_de_semana` (sí/no). |
| **Entrada** | Columna `Fecha` del dataset validado. |
| **Salida** | Tabla `dim_tiempo` con una fila por cada hora distinta registrada (3.553 filas). |
| **Prioridad** | Alta |
| **Criterio de aceptación** | Cada combinación `YYYY-MM-DD HH` aparece una sola vez. Los nombres de días y meses están correctamente traducidos al español (Lunes–Domingo, Enero–Diciembre). La clave primaria `id_tiempo` sigue el formato `YYYYMMDDHH`. |

---

### RF-05 · Construcción de la Dimensión de Sensores (dim_sensor)

| Campo | Detalle |
|---|---|
| **Descripción** | El sistema debe generar un catálogo dimensional que registre los 9 sensores físicos del invernadero con su código de variable único, nombre del sensor, magnitud física medida, ubicación (Interior/Exterior/Surco), unidad de medida, rango óptimo agronómico (mínimo y máximo) y límites de alerta (superior e inferior). |
| **Sensores catalogados** | DHT20 Temp Interior (°C), DHT20 Hum Interior (%), DHT20 Temp Exterior (°C), DHT20 Hum Exterior (%), BH1750 Lux Interior (Lux), BH1750 Lux Exterior (Lux), LM393 Suelo Surco 1 (%), LM393 Suelo Surco 2 (%), LM393 Suelo Surco 3 (%). |
| **Prioridad** | Alta |
| **Criterio de aceptación** | La tabla contiene exactamente 9 registros con umbrales diferenciados por tipo de variable y ubicación. El campo `codigo_variable` es único (restricción `UNIQUE`). |

---

### RF-06 · Construcción de la Dimensión de Surcos y Tratamientos (dim_surco_suelo)

| Campo | Detalle |
|---|---|
| **Descripción** | El sistema debe modelar los tratamientos agronómicos del diseño experimental, registrando cada surco con su nombre, porcentaje de humedad objetivo, número de bolsas de almácigo, cantidad de cuadrículas y descripción del tratamiento. Debe incluir un registro especial "Ambiente General / N/A" para los sensores de aire y luz que no pertenecen a un surco. |
| **Tratamientos** | Surco 1: 100% humedad, 27 bolsas, 3 cuadrículas · Surco 2: 75% humedad, 27 bolsas, 3 cuadrículas · Surco 3: 50% humedad, 27 bolsas, 3 cuadrículas · Ambiente General: 0%, 0, 0. |
| **Prioridad** | Alta |
| **Criterio de aceptación** | La tabla contiene exactamente 4 registros. Cada surco refleja fielmente el diseño experimental documentado en la Fase 1. |

---

### RF-07 · Detección Automática de Anomalías Ambientales

| Campo | Detalle |
|---|---|
| **Descripción** | Para cada medición atómica, el sistema debe evaluar si el `valor_medido` está fuera del rango de alerta definido para ese sensor (límite inferior y superior de `dim_sensor`) y asignar una bandera binaria: `flag_anomalia = 1` si el valor es anómalo, `flag_anomalia = 0` si está dentro del rango aceptable. |
| **Umbrales aplicados** | DHT20 Temp Int: 10°C–38°C · DHT20 Hum Int: 40%–95% · DHT20 Temp Ext: 8°C–42°C · DHT20 Hum Ext: 30%–100% · BH1750 Int: 1.000–50.000 Lux · BH1750 Ext: 2.000–65.000 Lux · LM393 Surco 1: 40%–100% · LM393 Surco 2: 30%–90% · LM393 Surco 3: 15%–75%. |
| **Prioridad** | Alta |
| **Criterio de aceptación** | Se puede obtener el total de anomalías por sensor/período mediante una simple suma: `SUM(flag_anomalia)`. |

---

### RF-08 · Cálculo Automático de Necesidad de Riego

| Campo | Detalle |
|---|---|
| **Descripción** | Para cada medición de humedad de suelo (sensores LM393 en surcos 1, 2 y 3), el sistema debe comparar el `valor_medido` contra la `humedad_objetivo_pct` del surco al que pertenece. Si la humedad medida es inferior al objetivo, se asigna `flag_riego = 1` (déficit hídrico, requiere riego); caso contrario, `flag_riego = 0`. Para sensores ambientales (temperatura, humedad relativa, luminosidad), el flag siempre es `0`. |
| **Objetivos por surco** | Surco 1: 100% · Surco 2: 75% · Surco 3: 50%. |
| **Prioridad** | Alta |
| **Criterio de aceptación** | `SUM(flag_riego)` por surco y período arroja la cantidad de horas con déficit hídrico, utilizable para análisis de estrés hídrico. Los sensores ambientales nunca tienen `flag_riego = 1`. |

---

### RF-09 · Ensamblaje de la Tabla Central de Hechos (fact_mediciones_invernadero)

| Campo | Detalle |
|---|---|
| **Descripción** | El sistema debe ensamblar una tabla de hechos que conecte cada medición atómica con sus tres dimensiones mediante claves foráneas: `id_tiempo` → `dim_tiempo`, `id_sensor` → `dim_sensor`, `id_surco` → `dim_surco_suelo`. Cada registro debe contener además: el `id_sensado_origen` (trazabilidad a Fase 1), la `fecha_hora` de la captura, el `valor_medido`, el `flag_anomalia` y el `flag_riego`. |
| **Prioridad** | Alta |
| **Criterio de aceptación** | La tabla contiene exactamente 31.977 registros. Cada registro tiene sus 3 claves foráneas apuntando a registros válidos en las dimensiones. No existen huérfanos. La clave primaria `id_medicion` es autoincremental. |

---

### RF-10 · Soporte para Consultas Analíticas Multidimensionales (OLAP)

| Campo | Detalle |
|---|---|
| **Descripción** | El modelo de datos debe permitir ejecutar consultas SQL de agregación (AVG, SUM, COUNT, MIN, MAX) combinando libremente filtros y agrupaciones por las tres dimensiones: por tiempo (franja horaria, día de la semana, mes, trimestre, año, fin de semana), por sensor (tipo de variable, ubicación, nombre del sensor) y por surco (tratamiento, humedad objetivo). |
| **Prioridad** | Media |
| **Criterio de aceptación** | Las siguientes consultas tipo son ejecutables sin modificar la estructura: (a) promedio de temperatura por franja horaria y mes, (b) total de anomalías por sensor y trimestre, (c) horas de riego requerido por surco y mes, (d) comparación de humedad de suelo entre tratamientos al 100%, 75% y 50%. |

---

### RF-11 · Exportación del Modelo a Script SQL Portable

| Campo | Detalle |
|---|---|
| **Descripción** | El proceso ETL debe generar un archivo `.sql` único, autocontenido y ejecutable que incluya: (a) creación de la base de datos, (b) definición de las 4 tablas con tipos, restricciones, índices y claves foráneas (DDL), y (c) inserción de todos los datos en lotes (DML). El archivo debe ser directamente importable sin intervención manual. |
| **Prioridad** | Media |
| **Criterio de aceptación** | Importar el archivo en un servidor MySQL/MariaDB limpio crea la base de datos con las 4 tablas, las 3 relaciones y los 31.977+ registros sin errores. El diagrama de relaciones es visible automáticamente en phpMyAdmin Designer o DBeaver. |

---

## 2. Requerimientos No Funcionales (RNF)

### RNF-01 · Arquitectura de Datos — Esquema en Estrella (Kimball)

| Campo | Detalle |
|---|---|
| **Descripción** | La base de datos analítica debe implementar un esquema en estrella puro según la metodología de Ralph Kimball, compuesto por exactamente **1 tabla de hechos** central conectada directamente a **3 tablas de dimensiones**, sin tablas intermedias, vistas materializadas ni niveles de copo de nieve (snowflake). |
| **Categoría ISO 25010** | Mantenibilidad — Modularidad |
| **Criterio de aceptación** | El modelo resultante tiene exactamente 4 tablas. No existen vistas, tablas puente ni tablas de agregación. |

---

### RNF-02 · Integridad Referencial Estricta

| Campo | Detalle |
|---|---|
| **Descripción** | Todas las relaciones entre la tabla de hechos y las dimensiones deben estar formalizadas mediante claves foráneas explícitas (`FOREIGN KEY ... REFERENCES`) con políticas `ON DELETE CASCADE` y `ON UPDATE CASCADE`, asegurando que no puedan existir registros huérfanos bajo ninguna circunstancia. |
| **Categoría ISO 25010** | Fiabilidad — Integridad |
| **Criterio de aceptación** | Las 3 restricciones FK están nombradas (`fk_olap_fmi_dim_tiempo`, `fk_olap_fmi_dim_sensor`, `fk_olap_fmi_dim_surco`) y son verificables mediante `SHOW CREATE TABLE`. |

---

### RNF-03 · Rendimiento de Consulta

| Campo | Detalle |
|---|---|
| **Descripción** | Las consultas analíticas de agregación con múltiples `JOIN` y `GROUP BY` sobre los más de 31.000 registros deben ejecutarse en menos de **500 milisegundos** en un entorno de laboratorio o desarrollo con hardware estándar (4 GB RAM, disco SSD). |
| **Categoría ISO 25010** | Eficiencia de Desempeño — Tiempo de respuesta |
| **Criterio de aceptación** | Se define un índice secundario sobre las columnas FK (`idx_fk_tiempo`, `idx_fk_sensor`, `idx_fk_surco`) y sobre los atributos de filtrado frecuente de `dim_tiempo` (`idx_dim_fecha`, `idx_dim_hora`, `idx_dim_franja`, `idx_dim_mes`). |

---

### RNF-04 · Compatibilidad con Motores de Base de Datos

| Campo | Detalle |
|---|---|
| **Descripción** | El script SQL generado debe ser 100% compatible con **MySQL 8.0+** y **MariaDB 10.4+**, los motores disponibles en entornos académicos comunes como XAMPP. Debe utilizar el motor de almacenamiento **InnoDB** para soportar claves foráneas y transacciones. |
| **Categoría ISO 25010** | Portabilidad — Adaptabilidad |
| **Criterio de aceptación** | El archivo se importa sin errores en phpMyAdmin (XAMPP), MySQL Workbench y DBeaver conectado a MySQL o MariaDB. |

---

### RNF-05 · Codificación de Caracteres y Soporte Multilingüe

| Campo | Detalle |
|---|---|
| **Descripción** | Toda la base de datos, incluyendo nombres de tablas, campos, catálogos y cadenas de texto en español (tildes, eñes, caracteres especiales como `°C`, `Miércoles`, `Sábado`, `Mañana`), debe utilizar la codificación **UTF-8 (`utf8mb4`)** con collation `utf8mb4_unicode_ci`. |
| **Categoría ISO 25010** | Compatibilidad — Coexistencia |
| **Criterio de aceptación** | Los valores textuales en español se almacenan y recuperan sin corrupción de caracteres en cualquier herramienta de consulta. |

---

### RNF-06 · Interoperabilidad con Herramientas de BI

| Campo | Detalle |
|---|---|
| **Descripción** | La estructura dimensional (claves primarias, claves foráneas, nombres descriptivos de tablas y campos) debe permitir que herramientas de Business Intelligence como Power BI, Metabase o Tableau detecten automáticamente las relaciones entre tablas al conectarse a la base de datos, sin necesidad de configuración manual de relaciones. |
| **Categoría ISO 25010** | Interoperabilidad |
| **Criterio de aceptación** | Al conectar Power BI o Metabase a la base de datos, el modelo estrella se renderiza automáticamente con las líneas de relación visibles. |

---

### RNF-07 · Precisión Numérica

| Campo | Detalle |
|---|---|
| **Descripción** | Los valores cuantitativos de los sensores (`valor_medido`), rangos óptimos y límites de alerta deben almacenarse con tipo `FLOAT` y redondearse a 2 decimales durante el proceso ETL, evitando la acumulación de errores de punto flotante en los cálculos de promedios y agregaciones agronómicas. |
| **Categoría ISO 25010** | Exactitud funcional |
| **Criterio de aceptación** | Ningún valor en la tabla de hechos tiene más de 2 decimales. Las funciones `ROUND(AVG(...), 2)` producen resultados coherentes. |

---

### RNF-08 · Trazabilidad hacia la Fase 1

| Campo | Detalle |
|---|---|
| **Descripción** | Cada registro de la tabla de hechos debe conservar el identificador de sensado original de la base de datos transaccional de la Fase 1 (`id_sensado_origen`), permitiendo la auditoría cruzada y la verificación de cualquier medición contra el sistema fuente. |
| **Categoría ISO 25010** | Seguridad — Trazabilidad / No repudio |
| **Criterio de aceptación** | Dado un `id_sensado_origen`, se pueden recuperar las 9 mediciones atómicas que corresponden a esa captura original de la Fase 1. |

---

### RNF-09 · Reproducibilidad del Proceso ETL

| Campo | Detalle |
|---|---|
| **Descripción** | El script ETL (`generar_bd_olap.py`) debe ser determinista y reproducible: ejecutarlo múltiples veces con el mismo CSV de entrada debe producir exactamente el mismo archivo SQL de salida (salvo la marca de fecha de generación). No debe requerir intervención manual ni configuración externa. |
| **Categoría ISO 25010** | Mantenibilidad — Reusabilidad |
| **Criterio de aceptación** | Dos ejecuciones consecutivas del script producen archivos SQL estructuralmente idénticos. El script se ejecuta con un solo comando: `python scripts/generar_bd_olap.py`. |

---

### RNF-10 · Tamaño y Rendimiento de Importación

| Campo | Detalle |
|---|---|
| **Descripción** | El archivo SQL generado no debe exceder los **5 MB** para garantizar la compatibilidad con los límites de importación por defecto de phpMyAdmin (en XAMPP). La inserción de datos debe realizarse en lotes (`batch insert`) para optimizar el rendimiento de carga. |
| **Categoría ISO 25010** | Eficiencia de Desempeño — Utilización de recursos |
| **Criterio de aceptación** | El archivo generado pesa ~2.4 MB. Las inserciones se realizan en lotes de 250 filas (dimensiones) y 500 filas (hechos). La importación completa tarda menos de 30 segundos en un entorno XAMPP estándar. |

---

## 3. Matriz de Trazabilidad RF ↔ Artefactos del Proyecto

| Requerimiento | Artefacto que lo implementa |
|---|---|
| RF-01, RF-02 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 7–36 |
| RF-03 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 59–69 (mapeo), 110–127 (pivoteo) |
| RF-04 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 38–107 → tabla DDL en líneas 210–229 del SQL |
| RF-05 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 173–182 → tabla DDL en líneas 21–33 del SQL |
| RF-06 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 199–203 → tabla DDL en líneas 50–58 del SQL |
| RF-07 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 71–74, 112–113 |
| RF-08 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 76, 116 |
| RF-09 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 245–262 → tabla DDL con FK en el SQL |
| RF-10 | [`database/README_BASE_DATOS.md`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/database/README_BASE_DATOS.md) — consultas SQL de ejemplo |
| RF-11 | [`scripts/generar_bd_olap.py`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/scripts/generar_bd_olap.py) — líneas 137–275 → [`database/invernadero_olap_fase2.sql`](file:///c:/Users/LENOVO/Downloads/proyecto_grado_cotectronix/database/invernadero_olap_fase2.sql) |
| RNF-01 a RNF-10 | Verificables transversalmente en el SQL generado y el script ETL |
