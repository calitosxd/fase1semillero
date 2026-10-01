import math
import pandas as pd
from datetime import datetime
from pathlib import Path

project_dir = Path(__file__).resolve().parents[1]
csv_input = project_dir / "documentacion_fase1" / "backup_servidor" / "datos_sensores_2024-04-16_a_2026-04-16.-v2csv.csv"
output_dir = project_dir / "database"
sql_output = output_dir / "invernadero_olap_fase2.sql"

if not csv_input.is_file():
    raise FileNotFoundError(f"No se encontró el CSV de entrada: {csv_input}")

print("Procesando dataset para esquema en estrella puro...")
df = pd.read_csv(csv_input, sep=';', skiprows=2)
columnas_sensores = [
    'Lux Int', 'Lux Ext', 'Tem Int', 'Tem Ext', 'Hum Int', 'Hum Ext',
    'Hum Sue 100', 'Hum Sue 75', 'Hum Sue 50'
]
columnas_requeridas = ['Id_Sensado', 'Fecha', *columnas_sensores]
columnas_faltantes = sorted(set(columnas_requeridas) - set(df.columns))
if columnas_faltantes:
    raise ValueError(f"Faltan columnas requeridas en el CSV: {', '.join(columnas_faltantes)}")

df['Fecha_parsed'] = pd.to_datetime(df['Fecha'], format='%d/%m/%Y %H:%M', errors='raise')
if df['Fecha_parsed'].isna().any():
    raise ValueError("La columna Fecha contiene capturas sin fecha.")
df['Id_Sensado'] = pd.to_numeric(df['Id_Sensado'], errors='raise')
if df['Id_Sensado'].isna().any() or df['Id_Sensado'].duplicated().any():
    raise ValueError("Id_Sensado debe estar informado y ser único por captura.")
df['Id_Sensado'] = df['Id_Sensado'].astype('int64')

for columna in columnas_sensores:
    df[columna] = pd.to_numeric(df[columna], errors='raise')
    if df[columna].isna().any() or not df[columna].map(math.isfinite).all():
        raise ValueError(f"La columna '{columna}' contiene mediciones vacías o no finitas.")

def asignar_franja_horaria(hora):
    if 0 <= hora < 6: return 'Madrugada'
    elif 6 <= hora < 12: return 'Mañana'
    elif 12 <= hora < 18: return 'Tarde'
    else: return 'Noche'

dias_esp = {
    'Monday': 'Lunes', 'Tuesday': 'Martes', 'Wednesday': 'Miércoles',
    'Thursday': 'Jueves', 'Friday': 'Viernes', 'Saturday': 'Sábado', 'Sunday': 'Domingo'
}

meses_esp = {
    1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril', 5: 'Mayo', 6: 'Junio',
    7: 'Julio', 8: 'Agosto', 9: 'Septiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre'
}

registros_tiempo = {}
registros_mediciones = []

# Mapeo de columnas a id_sensor e id_surco
# id_surco: 1=100%, 2=75%, 3=50%, 4=Ambiente General
sensores_map = [
    ('Lux Int', 5, 4),      # BH1750 Int
    ('Lux Ext', 6, 4),      # BH1750 Ext
    ('Tem Int', 1, 4),      # DHT20 Temp Int
    ('Tem Ext', 3, 4),      # DHT20 Temp Ext
    ('Hum Int', 2, 4),      # DHT20 Hum Int
    ('Hum Ext', 4, 4),      # DHT20 Hum Ext
    ('Hum Sue 100', 7, 1),  # LM393 Surco 100%
    ('Hum Sue 75', 8, 2),   # LM393 Surco 75%
    ('Hum Sue 50', 9, 3)    # LM393 Surco 50%
]

limites_alerta = {
    1: (10.0, 38.0), 2: (40.0, 95.0), 3: (8.0, 42.0),
    4: (30.0, 100.0), 5: (1000.0, 50000.0), 6: (2000.0, 65000.0),
    7: (40.0, 100.0), 8: (30.0, 90.0), 9: (15.0, 75.0)
}
humedad_objetivo_surco = {1: 100.0, 2: 75.0, 3: 50.0}

for _, row in df.iterrows():
    dt = row['Fecha_parsed']
    id_sensado = int(row['Id_Sensado'])
    hora = dt.replace(minute=0, second=0, microsecond=0)
    id_tiempo = int(hora.strftime("%Y%m%d%H"))
    
    if id_tiempo not in registros_tiempo:
        dia_nom_en = dt.strftime("%A")
        dia_nom = dias_esp.get(dia_nom_en, dia_nom_en)
        mes_nom = meses_esp.get(dt.month, str(dt.month))
        es_fds = 1 if dt.weekday() >= 5 else 0
        franja = asignar_franja_horaria(dt.hour)
        trimestre = (dt.month - 1) // 3 + 1
        semana_anio = dt.isocalendar()[1]
        
        registros_tiempo[id_tiempo] = {
            'id_tiempo': id_tiempo,
            'fecha_hora': hora.strftime("%Y-%m-%d %H:%M:%S"),
            'fecha': hora.strftime("%Y-%m-%d"),
            'anio': dt.year,
            'mes': dt.month,
            'nombre_mes': mes_nom,
            'dia': dt.day,
            'hora': dt.hour,
            'franja_horaria': franja,
            'dia_semana': dia_nom,
            'trimestre': trimestre,
            'semana_anio': semana_anio,
            'es_fin_de_semana': es_fds
        }

    # Desglosar en las 9 mediciones individuales conectadas a dim_sensor y dim_surco_suelo
    for col_name, id_sensor, id_surco in sensores_map:
        val = round(float(row[col_name]), 2)
        limite_minimo, limite_maximo = limites_alerta[id_sensor]
        anom = int(val < limite_minimo or val > limite_maximo)

        # Se marca riego cuando la humedad medida está por debajo del objetivo del surco.
        riego = int(id_surco in humedad_objetivo_surco and val < humedad_objetivo_surco[id_surco])

        registros_mediciones.append({
            'id_sensado_origen': id_sensado,
            'id_tiempo': id_tiempo,
            'id_sensor': id_sensor,
            'id_surco': id_surco,
            'fecha_hora': dt.strftime("%Y-%m-%d %H:%M:%S"),
            'valor_medido': val,
            'flag_anomalia': anom,
            'flag_riego': riego
        })

print(f"Total capturas: {len(df)}")
print(f"Total horas distintas: {len(registros_tiempo)}")
print(f"Total mediciones atómicas: {len(registros_mediciones)}")

if len(registros_mediciones) != len(df) * len(sensores_map):
    raise RuntimeError("El total de hechos generado no coincide con capturas x sensores.")

output_dir.mkdir(parents=True, exist_ok=True)
with open(sql_output, 'w', encoding='utf-8') as f:
    f.write("-- ====================================================================\n")
    f.write("-- PROYECTO DE GRADO: INVERNADERO COTECNOVA - FASE 2\n")
    f.write("-- SEMILLERO DE INVESTIGACIÓN COTECTRONIX\n")
    f.write("-- BASE DE DATOS ANALÍTICA: ESQUEMA EN ESTRELLA PURO (KIMBALL)\n")
    f.write("-- Compatible con: MySQL 8.0, MariaDB, XAMPP (phpMyAdmin) y MySQL Workbench\n")
    f.write(f"-- Fecha de Generación: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
    f.write("-- ====================================================================\n\n")

    f.write("SET FOREIGN_KEY_CHECKS = 0;\n")
    f.write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n")
    f.write("SET time_zone = '+00:00';\n\n")
    f.write("-- ATENCION: este archivo reconstruye las tablas y elimina sus datos previos.\n")

    f.write("CREATE DATABASE IF NOT EXISTS `invernadero_olap_fase2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n")
    f.write("USE `invernadero_olap_fase2`;\n\n")

    # 1. DIM_SENSOR
    f.write("-- --------------------------------------------------------------------\n")
    f.write("-- 1. TABLA DIM_SENSOR: Catálogo y umbrales agronómicos\n")
    f.write("-- --------------------------------------------------------------------\n")
    f.write("DROP TABLE IF EXISTS `dim_sensor`;\n")
    f.write("""CREATE TABLE `dim_sensor` (
  `id_sensor` INT NOT NULL,
  `codigo_variable` VARCHAR(30) NOT NULL UNIQUE,
  `nombre_sensor` VARCHAR(50) NOT NULL,
  `variable_medida` VARCHAR(50) NOT NULL,
  `ubicacion` VARCHAR(30) NOT NULL,
  `unidad_medida` VARCHAR(15) NOT NULL,
  `rango_min_optimo` FLOAT NOT NULL,
  `rango_max_optimo` FLOAT NOT NULL,
  `limite_alerta_superior` FLOAT NOT NULL,
  `limite_alerta_inferior` FLOAT NOT NULL,
  PRIMARY KEY (`id_sensor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo dimensional de sensores IoT';\n\n""")

    f.write("INSERT INTO `dim_sensor` VALUES\n")
    f.write("(1, 'DHT20_TEMP_INT', 'DHT20 Interior', 'Temperatura Ambiental', 'Interior', '°C', 18.0, 28.0, 38.0, 10.0),\n")
    f.write("(2, 'DHT20_HUM_INT', 'DHT20 Interior', 'Humedad Relativa', 'Interior', '%', 60.0, 80.0, 95.0, 40.0),\n")
    f.write("(3, 'DHT20_TEMP_EXT', 'DHT20 Exterior', 'Temperatura Ambiental', 'Exterior', '°C', 15.0, 32.0, 42.0, 8.0),\n")
    f.write("(4, 'DHT20_HUM_EXT', 'DHT20 Exterior', 'Humedad Relativa', 'Exterior', '%', 50.0, 85.0, 100.0, 30.0),\n")
    f.write("(5, 'BH1750_LUX_INT', 'BH1750 Interior', 'Luminosidad', 'Interior', 'Lux', 10000.0, 35000.0, 50000.0, 1000.0),\n")
    f.write("(6, 'BH1750_LUX_EXT', 'BH1750 Exterior', 'Luminosidad', 'Exterior', 'Lux', 15000.0, 50000.0, 65000.0, 2000.0),\n")
    f.write("(7, 'LM393_SUE_100', 'LM393 Surco 1', 'Humedad Suelo 100%', 'Surco 1', '%', 70.0, 95.0, 100.0, 40.0),\n")
    f.write("(8, 'LM393_SUE_75', 'LM393 Surco 2', 'Humedad Suelo 75%', 'Surco 2', '%', 55.0, 80.0, 90.0, 30.0),\n")
    f.write("(9, 'LM393_SUE_50', 'LM393 Surco 3', 'Humedad Suelo 50%', 'Surco 3', '%', 35.0, 60.0, 75.0, 15.0);\n\n")

    # 2. DIM_SURCO_SUELO
    f.write("-- --------------------------------------------------------------------\n")
    f.write("-- 2. TABLA DIM_SURCO_SUELO: Configuración agronómica de los surcos\n")
    f.write("-- --------------------------------------------------------------------\n")
    f.write("DROP TABLE IF EXISTS `dim_surco_suelo`;\n")
    f.write("""CREATE TABLE `dim_surco_suelo` (
  `id_surco` INT NOT NULL,
  `nombre_surco` VARCHAR(50) NOT NULL,
  `humedad_objetivo_pct` INT NOT NULL,
  `num_bolsas_almacigo` INT NOT NULL,
  `cuadriculas` INT NOT NULL,
  `descripcion_tratamiento` TEXT NOT NULL,
  PRIMARY KEY (`id_surco`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dimension de tratamientos de suelo';\n\n""")

    f.write("INSERT INTO `dim_surco_suelo` VALUES\n")
    f.write("(1, 'Surco 100% Humedad', 100, 27, 3, 'Tratamiento de alta saturación hídrica (3 cuadrículas de 9 bolsas).'),\n")
    f.write("(2, 'Surco 75% Humedad', 75, 27, 3, 'Tratamiento medio-alto óptimo (3 cuadrículas de 9 bolsas).'),\n")
    f.write("(3, 'Surco 50% Humedad', 50, 27, 3, 'Tratamiento de restricción hídrica (3 cuadrículas de 9 bolsas).'),\n")
    f.write("(4, 'Ambiente General / N/A', 0, 0, 0, 'No aplica para sensores ambientales de aire y luz.');\n\n")

    # 3. DIM_TIEMPO
    f.write("-- --------------------------------------------------------------------\n")
    f.write("-- 3. TABLA DIM_TIEMPO: Jerarquía temporal completa\n")
    f.write("-- --------------------------------------------------------------------\n")
    f.write("DROP TABLE IF EXISTS `dim_tiempo`;\n")
    f.write("""CREATE TABLE `dim_tiempo` (
  `id_tiempo` INT NOT NULL,
  `fecha_hora` DATETIME NOT NULL,
  `fecha` DATE NOT NULL,
  `anio` INT NOT NULL,
  `mes` INT NOT NULL,
  `nombre_mes` VARCHAR(20) NOT NULL,
  `dia` INT NOT NULL,
  `hora` INT NOT NULL,
  `franja_horaria` VARCHAR(20) NOT NULL,
  `dia_semana` VARCHAR(15) NOT NULL,
  `trimestre` INT NOT NULL,
  `semana_anio` INT NOT NULL,
  `es_fin_de_semana` TINYINT(1) NOT NULL,
  PRIMARY KEY (`id_tiempo`),
  KEY `idx_dim_fecha` (`fecha`),
  KEY `idx_dim_hora` (`hora`),
  KEY `idx_dim_franja` (`franja_horaria`),
  KEY `idx_dim_mes` (`nombre_mes`, `anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dimensión temporal con jerarquías y franjas';\n\n""")

    valores_tiempo = list(registros_tiempo.values())
    batch_size = 250
    for i in range(0, len(valores_tiempo), batch_size):
        batch = valores_tiempo[i:i+batch_size]
        f.write("INSERT INTO `dim_tiempo` VALUES\n")
        lines = [f"({t['id_tiempo']}, '{t['fecha_hora']}', '{t['fecha']}', {t['anio']}, {t['mes']}, '{t['nombre_mes']}', {t['dia']}, {t['hora']}, '{t['franja_horaria']}', '{t['dia_semana']}', {t['trimestre']}, {t['semana_anio']}, {t['es_fin_de_semana']})" for t in batch]
        f.write(",\n".join(lines) + ";\n\n")

    # 4. TABLA CENTRAL DE HECHOS EN ESTRELLA: FACT_MEDICIONES_INVERNADERO
    # Conecta DIRECTAMENTE con dim_tiempo, dim_sensor y dim_surco_suelo
    f.write("-- --------------------------------------------------------------------\n")
    f.write("-- 4. TABLA FACT_MEDICIONES_INVERNADERO: HECHOS EN ESTRELLA CONECTADA A LAS 3 DIMENSIONES\n")
    f.write("-- --------------------------------------------------------------------\n")
    f.write("DROP TABLE IF EXISTS `fact_mediciones_invernadero`;\n")
    f.write("""CREATE TABLE `fact_mediciones_invernadero` (
  `id_medicion` INT NOT NULL AUTO_INCREMENT,
  `id_sensado_origen` INT NOT NULL COMMENT 'ID original de la Fase 1',
  `id_tiempo` INT NOT NULL COMMENT 'Clave Foránea hacia dim_tiempo',
  `id_sensor` INT NOT NULL COMMENT 'Clave Foránea hacia dim_sensor',
  `id_surco` INT NOT NULL COMMENT 'Clave Foránea hacia dim_surco_suelo',
  `fecha_hora` DATETIME NOT NULL,
  `valor_medido` FLOAT NOT NULL,
  `flag_anomalia` TINYINT(1) NOT NULL DEFAULT 0,
  `flag_riego` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_medicion`),
  KEY `idx_fk_tiempo` (`id_tiempo`),
  KEY `idx_fk_sensor` (`id_sensor`),
  KEY `idx_fk_surco` (`id_surco`),
    CONSTRAINT `fk_olap_fmi_dim_tiempo` FOREIGN KEY (`id_tiempo`) REFERENCES `dim_tiempo` (`id_tiempo`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_olap_fmi_dim_sensor` FOREIGN KEY (`id_sensor`) REFERENCES `dim_sensor` (`id_sensor`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_olap_fmi_dim_surco` FOREIGN KEY (`id_surco`) REFERENCES `dim_surco_suelo` (`id_surco`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla central de hechos conectada relacionalmente con las 3 dimensiones';\n\n""")

    # Batch insert mediciones (31.977 filas en lotes de 500)
    batch_size_med = 500
    for i in range(0, len(registros_mediciones), batch_size_med):
        batch = registros_mediciones[i:i+batch_size_med]
        f.write("INSERT INTO `fact_mediciones_invernadero` (`id_sensado_origen`, `id_tiempo`, `id_sensor`, `id_surco`, `fecha_hora`, `valor_medido`, `flag_anomalia`, `flag_riego`) VALUES\n")
        lines = [f"({m['id_sensado_origen']}, {m['id_tiempo']}, {m['id_sensor']}, {m['id_surco']}, '{m['fecha_hora']}', {m['valor_medido']}, {m['flag_anomalia']}, {m['flag_riego']})" for m in batch]
        f.write(",\n".join(lines) + ";\n\n")

    f.write("SET FOREIGN_KEY_CHECKS = 1;\n")
    f.write("-- ====================================================================\n")
    f.write("-- FIN DEL MODELO ESTRELLA CONECTADO (4 TABLAS LIMPIAS)\n")
    f.write("-- ====================================================================\n")

print("¡Nuevo script SQL de 4 tablas generado con éxito!")

