-- Tablas Usadas
SELECT *
FROM SubGrupos

SELECT *
FROM UsuarioGrupo

SELECT *
FROM UsuariosBiometrico

SELECT TOP 10
    *
FROM Destino

SELECT *
FROM biometrico.dbo.UsuariosBiometrico
SELECT *
FROM biometrico.dbo.bitacora


-- Crear tabla DestinoBiometricos
CREATE TABLE DestinoBiometricos
(
    idDestino UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
    Descripcion VARCHAR(50),
    Dispositivo VARCHAR(50),
);

INSERT INTO DestinoBiometricos
    (Descripcion)
VALUES
    ('Destino 1'),
    ('Destino 2'),
    ('Destino 3'),
    ('Destino 4'),
    ('Destino 5'),
    ('Destino 6'),
    ('Destino 7'),
    ('Destino 8'),
    ('Destino 9'),
    ('Dinastia');

SELECT *
FROM DestinoBiometricos

-- Crear Vista Uniendo Destinos y DestinosBiometrico
-- 
ALTER TABLE DestinoBiometricos
ALTER COLUMN Descripcion VARCHAR(255) COLLATE Modern_Spanish_CI_AS;

/* CREATE VIEW vDestinosUnidos AS
SELECT idDestino, Descripcion  
FROM Destino
UNION ALL
SELECT idDestino, Descripcion
FROM DestinoBiometricos; */

SELECT COLUMN_NAME, COLLATION_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'Destino' AND COLUMN_NAME = 'Descripcion';

SELECT COLUMN_NAME, COLLATION_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'DestinoBiometricos' AND COLUMN_NAME = 'Descripcion';

SELECT TOP 10
    *
FROM vDestinosUnidos
WHERE Descripcion LIKE '%dinastia%';


-- SubGrupos
SELECT idSubGrupo, nombreSubGrupo
FROM SubGrupos

-- SubGrupos por nombreSubGrupo
DECLARE @nombreSubGrupo VARCHAR(50) = 'D'
SELECT idSubGrupo, nombreSubGrupo
FROM SubGrupos
WHERE nombreSubGrupo LIKE '%' + @nombreSubGrupo + '%';


-- Usuarios por idSubGrupo
DECLARE @idSubGrupo VARCHAR(50) = 'bd8ad7a1-50c5-4d8d-8acc-083c239ac26f'
SELECT ub.idUsuario, NombreCompleto
FROM UsuariosBiometrico ub
    INNER JOIN UsuarioGrupo ug ON ub.idUsuario = ug.idUsuario
WHERE ug.idSubGrupo = @idSubGrupo;

SELECT *
FROM Minas
WHERE Descripcion LIKE '%dinastia%'

-- Generacion de Tabla
SELECT *
FROM biometrico.dbo.bitacoraV2
SELECT *
FROM biometrico.dbo.UsuariosBiometrico

DECLARE @FechaInicio DATE = '2025-01-01', @FechaFin DATE = '2025-02-03', @idUsuario UNIQUEIDENTIFIER = '7f764134-0963-49c1-869f-614921450c08';

SELECT b.id, b.access_date, b.access_time, b.nombres, b.apellidos, b.Estado
FROM biometrico.dbo.bitacoraV2 b
    INNER JOIN biometrico.dbo.UsuariosBiometrico ub ON b.id = ub.Identificador
WHERE b.access_date BETWEEN @FechaInicio AND @FechaFin
    AND ub.idUsuario = @idUsuario
ORDER BY b.access_date, b.access_time;

-- 
/* 
"{"FechaInicial":"2025-01-31",
  "FechaFinal":"2025-02-04",
  "Cargo":null,
  "Usuario":null,"CentroTrabajo":"UHJiK3dHN25oWWZuU3AyVlVQS1B6SC9KelVLUmY4M2IvWU5zbXVkRGlqMVo2aWpZa2Jic0hwSjdWdnArYW14TQ=="}" */

SELECT *
FROM dbo.fnObtenerHorasTrabajadas('2025-01-31', '2025-02-04', 'bio_calle 13', NULL);

SELECT *
FROM dbo.fnObtenerHorasTrabajadas('2025-01-31', '2025-02-04', 'bio_calle 13', NULL)
WHERE HorasTrabajadas != 'Sin Salida';

-- Agrupación por empleado
SELECT
    Nombre,
    Apellido,
    COUNT(*) AS DiasLaborados,
    SUM(CASE WHEN HorasTrabajadas != 'Sin Salida' THEN 1 ELSE 0 END) AS DiasCompletos
FROM dbo.fnObtenerHorasTrabajadas('2025-01-31', '2025-02-04', 'bio_calle 13', NULL)
GROUP BY Nombre, Apellido;

-- Agrupación por empleado y día

SELECT
    Nombre,
    Apellido,
    COUNT(DISTINCT Fecha) AS DiasLaborados,
    RIGHT('0' + CAST(
            SUM(CASE 
                WHEN HorasTrabajadas = 'Sin Salida' THEN 0
                ELSE CAST(SUBSTRING(HorasTrabajadas, 1, 2) AS INT) * 60 + 
                     CAST(SUBSTRING(HorasTrabajadas, 4, 2) AS INT)
            END) / 60 AS VARCHAR), 2) 
        + ':' +
        RIGHT('0' + CAST(
            SUM(CASE 
                WHEN HorasTrabajadas = 'Sin Salida' THEN 0
                ELSE CAST(SUBSTRING(HorasTrabajadas, 1, 2) AS INT) * 60 + 
                     CAST(SUBSTRING(HorasTrabajadas, 4, 2) AS INT)
            END) % 60 AS VARCHAR), 2) AS HorasTotales
FROM dbo.fnObtenerHorasTrabajadas('2025-01-31', '2025-02-04', 'bio_calle 13', NULL)
GROUP BY Nombre, Apellido

SELECT *
FROM dbo.fnObtenerHorasTrabajadas('2025-01-31', '2025-02-04', 'bio_calle 13', 'd001');