# 🌿 Base de Datos Analítica (OLAP) - Invernadero COTECNOVA (Fase 2)

**Semillero de Investigación:** COTECTRONIX  
**Nombre de la Base de Datos:** `invernadero_olap_fase2`  
**Archivo SQL:** `database/invernadero_olap_fase2.sql` (Texto plano, UTF-8, ~2.4 MB)  
**Total de Registros Cargados:** 31.977 mediciones atómicas de sensores (3.553 horas x 9 sensores)  
**Estructura:** **4 Tablas Relacionales Conectadas** (Esquema en Estrella Puro según Ralph Kimball)

---

## 🏛️ 1. Arquitectura del Modelo Dimensional (Esquema en Estrella Conectado)

La base de datos se estructuró estéticamente limpia en **exactamente 4 tablas**, eliminando vistas o tablas intermedias innecesarias para garantizar la máxima elegancia y velocidad en herramientas analíticas (DBeaver, phpMyAdmin Designer, MySQL Workbench EER Diagram, Power BI, Metabase).

```
                      ┌──────────────────────────┐
                      │        dim_sensor        │
                      ├──────────────────────────┤
                      │ id_sensor (PK)           │
                      │ codigo_variable          │
                      │ nombre_sensor            │
                      │ variable_medida          │
                      │ unidad_medida            │
                      │ rango_min_optimo         │
                      │ rango_max_optimo         │
                      └────────────┬─────────────┘
                                   │ (1:N)
 ┌──────────────────────────┐      │      ┌──────────────────────────┐
 │        dim_tiempo        │      │      │     dim_surco_suelo      │
 ├──────────────────────────┤      │      ├──────────────────────────┤
 │ id_tiempo (PK)           │      │      │ id_surco (PK)            │
 │ fecha_hora               │      │      │ nombre_surco             │
 │ fecha (DATE)             │      │      │ humedad_objetivo_pct     │
 │ anio, mes, dia, hora     │      │      │ num_bolsas_almacigo      │
 │ franja_horaria           │      │      │ descripcion_tratamiento  │
 │ dia_semana               │      │      └────────────┬─────────────┘
 │ es_fin_de_semana         │      │                   │
 └────────────┬─────────────┘      │                   │
              │ (1:N)              ▼                   │ (1:N)
              │       ┌──────────────────────────┐     │
              └──────►│fact_mediciones_invernad. │◄────┘
                      ├──────────────────────────┤
                      │ id_medicion (PK)         │
                      │ id_sensado_origen        │
                      │ id_tiempo (FK)           ├─► dim_tiempo(id_tiempo)
                      │ id_sensor (FK)           ├─► dim_sensor(id_sensor)
                      │ id_surco (FK)            ├─► dim_surco_suelo(id_surco)
                      │ fecha_hora               │
                      │ valor_medido             │
                      │ flag_anomalia            │
                      │ flag_riego               │
                      └──────────────────────────┘
```

---

## 🔗 2. Relaciones y Claves Foráneas (Foreign Keys)

La tabla central `fact_mediciones_invernadero` posee **3 Claves Foráneas (FK)** explícitas con integridad referencial completa:

1. `fk_olap_fmi_dim_tiempo`: `fact_mediciones_invernadero.id_tiempo` ➔ `dim_tiempo.id_tiempo` (`ON DELETE CASCADE ON UPDATE CASCADE`)
2. `fk_olap_fmi_dim_sensor`: `fact_mediciones_invernadero.id_sensor` ➔ `dim_sensor.id_sensor` (`ON DELETE CASCADE ON UPDATE CASCADE`)
3. `fk_olap_fmi_dim_surco`: `fact_mediciones_invernadero.id_surco` ➔ `dim_surco_suelo.id_surco` (`ON DELETE CASCADE ON UPDATE CASCADE`)

---

## 🚀 3. Instrucciones de Importación

### Opción A: Desde XAMPP (phpMyAdmin)
1. Abre el panel de control de **XAMPP** e inicia **Apache** y **MySQL**.
2. Ingresa en tu navegador a `http://localhost/phpmyadmin`.
3. Haz clic en la pestaña superior **Importar**.
4. Haz clic en **Seleccionar archivo** y busca:
   `c:\Users\LENOVO\Downloads\proyecto_grado_cotectronix\database\invernadero_olap_fase2.sql`
5. Haz clic en **Importar** (o **Continuar**).
6. Al ingresar a la base de datos `invernadero_olap_fase2`, verás exactamente **4 tablas** limpias y organizadas.
7. Puedes ir a la pestaña **Diseñador (Designer)** de phpMyAdmin para ver el esquema en estrella limpio con las líneas de relación visibles.

### Opción B: Desde DBeaver / MySQL Workbench
1. Abre tu gestor de base de datos.
2. Abre el archivo `database/invernadero_olap_fase2.sql`.
3. Ejecuta todo el script SQL.
4. Genera el **Diagrama ER (EER Diagram)** para observar la tabla de hechos en el centro conectada armónicamente a las 3 dimensiones.

---

## 💡 4. Consultas SQL de Ejemplo (OLAP Star Schema)

### Consulta 1: Promedio de Temperatura y Humedad por Sensor y Franja Horaria
```sql
SELECT 
    s.nombre_sensor,
    s.variable_medida,
    t.franja_horaria,
    ROUND(AVG(f.valor_medido), 2) AS promedio_medido,
    COUNT(CASE WHEN f.flag_anomalia = 1 THEN 1 END) AS total_anomalias
FROM fact_mediciones_invernadero f
JOIN dim_sensor s ON f.id_sensor = s.id_sensor
JOIN dim_tiempo t ON f.id_tiempo = t.id_tiempo
WHERE s.variable_medida IN ('Temperatura Ambiental', 'Humedad Relativa')
GROUP BY s.nombre_sensor, s.variable_medida, t.franja_horaria
ORDER BY s.nombre_sensor, t.franja_horaria;
```

### Consulta 2: Desempeño Agronómico por Surco de Suelo
```sql
SELECT 
    su.nombre_surco,
    su.humedad_objetivo_pct,
    t.nombre_mes,
    ROUND(AVG(f.valor_medido), 2) AS humedad_promedio_suelo,
    SUM(f.flag_riego) AS horas_riego_requerido
FROM fact_mediciones_invernadero f
JOIN dim_surco_suelo su ON f.id_surco = su.id_surco
JOIN dim_tiempo t ON f.id_tiempo = t.id_tiempo
WHERE su.id_surco IN (1, 2, 3)
GROUP BY su.nombre_surco, su.humedad_objetivo_pct, t.mes, t.nombre_mes
ORDER BY su.id_surco, t.mes;
```
