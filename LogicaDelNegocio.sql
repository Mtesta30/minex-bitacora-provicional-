/*  -------------------------------------------------- */
/*  --------------------- TABLAS --------------------- */
/*  -------------------------------------------------- */


/* -------------------------------------------------- */
/* Tabla Usuarios Biometrico */
/* -------------------------------------------------- */
CREATE TABLE [dbo].[UsuariosBiometrico]
(
    [idUsuario] [UNIQUEIDENTIFIER] NOT NULL,
    [TipoDocumento] [VARCHAR](10) NULL,
    [Identificacion] [VARCHAR](15) NOT NULL,
    [NombreCompleto] [VARCHAR](100) NOT NULL,
    [Cargo] [VARCHAR](50) NULL,
    [Identificador] [VARCHAR](50) NULL,
    Habilitado [BIT] NOT NULL DEFAULT 1

        CONSTRAINT [PK_UsuariosBiometrico]
PRIMARY KEY CLUSTERED
    (
        [idUsuario] ASC
    )
    WITH
    (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO

/* Índice para búsquedas por identificación */
CREATE NONCLUSTERED INDEX [IX_UsuariosBiometrico_Identificacion] ON [dbo].[UsuariosBiometrico]
(
    [Identificacion] ASC
)
GO

/* -------------------------------------------------- */
/* Tabla Biometricos */
/* -------------------------------------------------- */
CREATE TABLE [dbo].[Biometricos]
(
    [idBiometrico] [UNIQUEIDENTIFIER]NOT NULL,
    [idCentroTrabajo] [UNIQUEIDENTIFIER] NOT NULL,
    [identificadorBiometrico] [VARCHAR](50) NOT NULL,
    [nombreDispositivo] [VARCHAR](100) NULL,

    CONSTRAINT [PK_Biometricos]
PRIMARY KEY CLUSTERED
(
        [idBiometrico] ASC
    )
WITH
(PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO

-- Agregar índice para búsquedas por identificador
CREATE NONCLUSTERED INDEX [IX_Biometricos_identificadorBiometrico] ON [dbo].[Biometricos]
(
    [identificadorBiometrico] ASC
)
GO

-- Agregar clave foránea a la tabla Destino
ALTER TABLE [dbo].[Biometricos] WITH CHECK ADD CONSTRAINT [FK_Biometricos_Destino] 
FOREIGN KEY([idCentroTrabajo])
REFERENCES [dbo].[Destino] ([idDestino])
GO

/* -------------------------------------------------- */
/* Tabla Bitácora */
/* -------------------------------------------------- */
CREATE TABLE [dbo].[Bitacora]
(
    [idBitacora] [UNIQUEIDENTIFIER] NOT NULL,
    [idBiometrico] [UNIQUEIDENTIFIER] NOT NULL,
    [Identificacion] [VARCHAR](15) NULL,
    [NombreCompleto] [VARCHAR](100) NULL,
    [FechaHora] [DATETIME] NOT NULL,
    [tipoMarcacion] [VARCHAR](50) NOT NULL,
    [idProgramacionDetalle] [UNIQUEIDENTIFIER] NULL,
    [idTurno] [UNIQUEIDENTIFIER] NULL,
    [observacion] [VARCHAR](255) NULL,

    CONSTRAINT [PK_Bitacora]
PRIMARY KEY CLUSTERED
(
        [idBitacora] ASC
    )
WITH
(PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO


/* -------------------------------------------------- */
/* Tabla Turnos */
/* -------------------------------------------------- */
CREATE TABLE [dbo].[Turnos]
(
    [idTurno] [UNIQUEIDENTIFIER] NOT NULL,
    [descripcion] [VARCHAR](100) NOT NULL,
    [horaInicio] [TIME](7) NOT NULL,
    [horaFin] [TIME](7) NOT NULL,
    [duracionHoras] [DECIMAL](5,2) NOT NULL,
    [idUsuarioCreador] UNIQUEIDENTIFIER,
    -- Nuevo parámetro
    [activo] [BIT] NOT NULL DEFAULT 1,

    CONSTRAINT [PK_Turnos] PRIMARY KEY CLUSTERED
    ([idTurno] ASC)
)

/* -------------------------------------------------- */
/* Tabla Turnos Descansos */
/* -------------------------------------------------- */
CREATE TABLE [dbo].[TurnosDescansos]
(
    [idTurnoDescanso] [UNIQUEIDENTIFIER] NOT NULL,
    [idTurno] [UNIQUEIDENTIFIER] NOT NULL,
    [horaInicio] [TIME](7) NOT NULL,
    [horaFin] [TIME](7) NOT NULL,
    [duracionMinutos] [INT] NOT NULL,
    [descripcion] [VARCHAR](100) NULL,
    [activo] [BIT] NOT NULL DEFAULT 1,

    CONSTRAINT [PK_TurnosDescansos] PRIMARY KEY CLUSTERED ([idTurnoDescanso] ASC),
    CONSTRAINT [FK_TurnosDescansos_Turnos] FOREIGN KEY ([idTurno]) 
        REFERENCES [dbo].[Turnos] ([idTurno])
)


/* -------------------------------------------------- */
/* Tabla Programacion de Turnos */
/* -------------------------------------------------- */
CREATE TABLE [dbo].[ProgramacionTurnos]
(
    [idProgramacion] [UNIQUEIDENTIFIER] NOT NULL,
    [idUsuario] [UNIQUEIDENTIFIER] NOT NULL,
    [idCentroTrabajo] [UNIQUEIDENTIFIER] NOT NULL,
    [fechaInicio] [DATE] NOT NULL,
    [fechaFin] [DATE] NOT NULL,
    [fechaRegistro] [DATETIME] NOT NULL DEFAULT GETDATE(),
    [idUsuarioRegistra] [UNIQUEIDENTIFIER] NOT NULL,
    [activo] [BIT] NOT NULL DEFAULT 1,

    CONSTRAINT [PK_ProgramacionTurnos] PRIMARY KEY CLUSTERED ([idProgramacion] ASC),
    CONSTRAINT [FK_ProgramacionTurnos_Destino] FOREIGN KEY
    ([idCentroTrabajo]) 
        REFERENCES [dbo].[Destino]
    ([idDestino])
)

/* -------------------------------------------------- */
/* Tabla Programación Turnos Detalle */
/* -------------------------------------------------- */
CREATE TABLE [dbo].[ProgramacionTurnosDetalle]
(
    [idProgramacionDetalle] [UNIQUEIDENTIFIER] NOT NULL,
    [idProgramacion] [UNIQUEIDENTIFIER] NOT NULL,
    [fechaInicio] [DATE] NOT NULL,
    [fechaFin] [DATE] NOT NULL,
    [idTurno] [UNIQUEIDENTIFIER] NOT NULL,

    CONSTRAINT [PK_ProgramacionTurnosDetalle] PRIMARY KEY CLUSTERED ([idProgramacionDetalle] ASC),
    CONSTRAINT [FK_ProgramacionTurnosDetalle_ProgramacionTurnos] FOREIGN KEY ([idProgramacion]) 
        REFERENCES [dbo].[ProgramacionTurnos] ([idProgramacion]),
    CONSTRAINT [FK_ProgramacionTurnosDetalle_Turnos] FOREIGN KEY ([idTurno]) 
        REFERENCES [dbo].[Turnos] ([idTurno])
)

/*  -------------------------------------------------- */
/*  --------------------- VISTAS --------------------- */
/*  -------------------------------------------------- */

/* -------------------------------------------------- */
/* Vista de todos los Usuarios de las tablas UsuariosBiometrico y UsuariosDetalle */
/* -------------------------------------------------- */
CREATE OR ALTER VIEW [dbo].[vUsuariosAppBiometrico]
AS
            SELECT
            ub.idUsuario,
            ub.NombreCompleto,
            ub.Identificacion,
            ub.Cargo,
            'Biométrico'
AS Origen
        FROM
            [dbo].[UsuariosBiometrico] ub
        WHERE
            ub.Habilitado = 1
    UNION ALL
        SELECT
            ud.idUsuario,
            ud.NombreCompleto,
            ud.Identificacion,
            ud.Cargo,
            'Trazapp' AS Origen
        FROM
            [dbo].[UsuariosDetalle] ud
            INNER JOIN [dbo].[Usuarios] u ON ud.idUsuario = u.idUsuario
        WHERE
            u.Habilitado = 1
GO;

/* -------------------------------------------------- */
/* Vista de todos los Usuarios asociados a un centro  */
/* de trabajo consultando por su idBiometrico         */
/* -------------------------------------------------- */
CREATE OR ALTER VIEW [dbo].[vUsuariosCentroTrabajo]
AS
    SELECT DISTINCT
        u.idUsuario,
        u.NombreCompleto,
        u.Identificacion,
        u.Cargo,
        u.Origen,
        d.idDestino AS idCentroTrabajo,
        d.Descripcion AS CentroTrabajo,
        b.idBiometrico,
        b.identificadorBiometrico,
        b.nombreDispositivo AS DispositivoBiometrico
    --MAX(bit.FechaHora) AS UltimaActividad,
    --COUNT(bit.idBitacora) AS TotalRegistros
    FROM
        [dbo].[vUsuariosAppBiometrico] u
        INNER JOIN [dbo].[Bitacora] bit ON u.Identificacion = bit.Identificacion
        INNER JOIN [dbo].[Biometricos] b ON bit.idBiometrico = b.idBiometrico
        INNER JOIN [dbo].[Destino] d ON b.idCentroTrabajo = d.idDestino
    GROUP BY 
    u.idUsuario, 
    u.NombreCompleto, 
    u.Identificacion, 
    u.Cargo,
    u.Origen,
    d.idDestino,
    d.Descripcion,
	b.idBiometrico,
    b.identificadorBiometrico,
    b.nombreDispositivo
GO;

/*  -------------------------------------------------- */
/* Vista de Asignación de Turnos a Usuarios */
/*  -------------------------------------------------- */
CREATE VIEW [dbo].[vAsignacionTurnosUsuario]
AS
    SELECT
        -- Información del usuario
        vua.NombreCompleto,
        vua.Identificacion,
        vua.Cargo,

        -- Información del turno
        pt.fechaInicio,
        pt.fechaFin,

        -- Información del horario del turno
        t.descripcion,
        FORMAT(CAST(t.horaInicio AS DATETIME), 'HH:mm') AS 'horaInicio',
        FORMAT(CAST(t.horaFin AS DATETIME), 'HH:mm') AS 'horaFin',

        -- Formato de duración en horas y minutos
        RIGHT('00' + CAST(FLOOR(t.duracionHoras) AS VARCHAR), 2) + ':' + 
        RIGHT('00' + CAST(FLOOR((t.duracionHoras - FLOOR(t.duracionHoras)) * 60) AS VARCHAR), 2) AS 'duracion',

        -- Información del centro de trabajo
        d.Descripcion AS 'CentroTrabajo',

        -- IDs originales para referencias o filtros adicionales
        pt.idProgramacion,
        pt.idUsuario,
        pt.idCentroTrabajo,
        t.idTurno

    FROM
        -- Tabla principal de programación de turnos
        ProgramacionTurnos pt

        -- Unión con detalles de programación para obtener el turno asignado
        INNER JOIN ProgramacionTurnosDetalle ptd ON pt.idProgramacion = ptd.idProgramacion

        -- Unión con la tabla de turnos para obtener información del horario
        INNER JOIN Turnos t ON ptd.idTurno = t.idTurno

        -- Unión con vista de usuarios para obtener información personal
        INNER JOIN vUsuariosAppBiometrico vua ON pt.idUsuario = vua.idUsuario

        -- Unión con tabla destino (centros de trabajo)
        INNER JOIN Destino d ON pt.idCentroTrabajo = d.idDestino

    WHERE 
    pt.activo = 1
GO;

/* -------------------------------------------------- */
/* PROCEDIMIENTOS */
/* -------------------------------------------------- */

/* -------------------------------------------------- */
/* PROCEDURE principal que orquesta las validaciones  */
/* -------------------------------------------------- */
CREATE PROCEDURE [dbo].[SAVE_Bitacora]
    @identificadorBiometrico VARCHAR(50),
    @Identificacion VARCHAR(15),
    @NombreCompleto VARCHAR(100),
    @FechaHora DATETIME
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @idBiometrico UNIQUEIDENTIFIER;
    DECLARE @idUsuario UNIQUEIDENTIFIER;
    DECLARE @tipoMarcacion VARCHAR(20);
    DECLARE @idProgramacionDetalle UNIQUEIDENTIFIER;
    DECLARE @idTurno UNIQUEIDENTIFIER;
    DECLARE @observacion VARCHAR(255);
    DECLARE @tieneTurnoAsignado BIT = 0;

    -- Variables para información del turno
    DECLARE @horaInicioTurno TIME;
    DECLARE @horaFinTurno TIME;
    DECLARE @cruzaDia BIT;
    DECLARE @fechaTurno DATE;
    DECLARE @fechaFinTurno DATE;

    -- Validar biométrico y obtener su ID
    EXEC [dbo].[GET_Biometrico]
        @identificadorBiometrico, 
        @idBiometrico OUTPUT;

    -- si id biometrico es Dinastia, enviar un nuevo parametro llamado identificador si no se envía identificación 
    -- Validar usuario y obtener su ID
    EXEC [dbo].[GET_UsuarioBiometrico]
        @Identificacion, 
        @NombreCompleto, 
        @idUsuario OUTPUT;

    -- Insertar registro si el biométrico existe
    IF @idBiometrico IS NOT NULL
    BEGIN
        -- Usar directamente el idUsuario obtenido
        DECLARE @fechaMarcacion DATE = CAST(@FechaHora AS DATE);

        -- 2. Obtener información del turno programado si existe
        SELECT TOP 1
            @horaInicioTurno = T.horaInicio,
            @horaFinTurno = T.horaFin,
            @cruzaDia = CASE WHEN T.horaFin < T.horaInicio THEN 1 ELSE 0 END,
            @fechaTurno = PD.fechaInicio,
            @fechaFinTurno = PD.fechaFin,
            @idProgramacionDetalle = PD.idProgramacionDetalle,
            @idTurno = PD.idTurno,
            @tieneTurnoAsignado = 1
        FROM ProgramacionTurnosDetalle PD
            INNER JOIN ProgramacionTurnos P ON PD.idProgramacion = P.idProgramacion
            INNER JOIN Turnos T ON PD.idTurno = T.idTurno
        WHERE P.idUsuario = @idUsuario
            AND (
                -- La marcación es en la fecha de inicio del turno
                (CAST(@FechaHora AS DATE) = PD.fechaInicio)
            OR
            -- La marcación es en la fecha de fin del turno (para turnos que cruzan días)
            (CAST(@FechaHora AS DATE) = PD.fechaFin AND PD.fechaInicio < PD.fechaFin)
            )
            AND P.activo = 1
        ORDER BY P.fechaRegistro DESC;

        -- 3. Procesar la marcación
        IF @tieneTurnoAsignado = 1
        BEGIN
            -- Llamar al procedimiento para determinar el tipo de marcación
            EXEC [dbo].[GET_TipoMarcacion]
                @Identificacion,
                @FechaHora,
                @idBiometrico,
                @horaInicioTurno,
                @horaFinTurno,
                @cruzaDia,
                @fechaTurno,
                @fechaFinTurno,
                @idTurno,  -- Agregamos el parámetro @idTurno
                @tipoMarcacion OUTPUT,
                @observacion OUTPUT;
        END
        ELSE
        BEGIN
            -- No tiene turno asignado, establecer valores predeterminados
            SET @tipoMarcacion = 'Sin turno asignado';
            SET @observacion = 'Marcación registrada sin turno programado';
            SET @idProgramacionDetalle = '00000000-0000-0000-0000-000000000001';
            SET @idTurno = '00000000-0000-0000-0000-000000000001';
        END

        -- 4. Insertar el registro en la bitácora con toda la información
        INSERT INTO [dbo].[Bitacora]
            (
            idBitacora,
            idBiometrico,
            Identificacion,
            NombreCompleto,
            FechaHora,
            tipoMarcacion,
            idProgramacionDetalle,
            idTurno,
            observacion
            )
        VALUES
            (
                NEWID(),
                @idBiometrico,
                @Identificacion,
                @NombreCompleto,
                @FechaHora,
                @tipoMarcacion,
                @idProgramacionDetalle,
                @idTurno,
                @observacion
            );

        -- Opcional: Registrar en log para análisis posterior
        IF @tieneTurnoAsignado = 0
        BEGIN
            INSERT INTO [dbo].[Logs]
                (text)
            VALUES
                ('Marcación sin turno: ' + @Identificacion + ' - ' + CONVERT(VARCHAR, @FechaHora, 120));
        END
    END
END;
GO
/* -------------------------------------------------- */
/* Procedimiento para validar usuarios */
/* -------------------------------------------------- */
CREATE PROCEDURE [dbo].[GET_UsuarioBiometrico]
    @identificacion VARCHAR(15),
    @nombreCompleto VARCHAR(100),
    @idUsuario UNIQUEIDENTIFIER OUTPUT
AS
BEGIN
    -- Verificar si existe en cualquiera de las tablas
    SELECT TOP 1
        @idUsuario = idUsuario
    FROM [dbo].[vUsuariosAppBiometrico]
    WHERE Identificacion = @identificacion;

    -- Si no existe, crearlo en UsuariosBiometrico
    IF @idUsuario IS NULL
    BEGIN
        SET @idUsuario = NEWID();

        INSERT INTO [dbo].[UsuariosBiometrico]
            (idUsuario, Identificacion, NombreCompleto)
        VALUES
            (@idUsuario, @identificacion, @nombreCompleto);
    END

    RETURN 0;
END;
GO

/* -------------------------------------------------- */
/* Procedimiento para validar dispositivos biométricos */
/* -------------------------------------------------- */
CREATE PROCEDURE [dbo].[GET_Biometrico]
    @identificadorBiometrico VARCHAR(50),
    @idBiometrico UNIQUEIDENTIFIER OUTPUT
AS
BEGIN
    -- Intentar obtener el ID del biométrico
    SELECT @idBiometrico = idBiometrico
    FROM [dbo].[Biometricos]
    WHERE identificadorBiometrico = @identificadorBiometrico;

    -- Si no existe, asignar el ID genérico
    IF @idBiometrico IS NULL
    BEGIN
        SET @idBiometrico = '00000000-0000-0000-0000-000000000001';

        -- Registrar en log
        INSERT INTO [dbo].[Logs]
        VALUES
            ('Biometrico No Registrado - Dispositivo: '+ @identificadorBiometrico + ' - Fecha: ' + CONVERT(VARCHAR, GETDATE(), 120));
    END

    RETURN 0;
END;


/* -------------------------------------------------- */
/* Procedimiento para obtener el tipo de marcacion    */
/* -------------------------------------------------- */
CREATE PROCEDURE [dbo].[GET_TipoMarcacion]
    @identificacion VARCHAR(15),
    @fechaHora DATETIME,
    @idBiometrico UNIQUEIDENTIFIER,
    @horaInicioTurno TIME = NULL,
    @horaFinTurno TIME = NULL,
    @cruzaDia BIT = 0,
    @fechaTurno DATE = NULL,
    @fechaFinTurno DATE = NULL,
    @idTurno UNIQUEIDENTIFIER = NULL,
    @tipoMarcacion VARCHAR(20) OUTPUT,
    @observacion VARCHAR(255) OUTPUT
AS
BEGIN
    DECLARE @fechaActual DATE = CAST(@fechaHora AS DATE);
    DECLARE @horaActual TIME = CAST(@fechaHora AS TIME);
    DECLARE @toleranciaMinutos INT = 30;
    DECLARE @margenEntradaMinutos INT = 120;
    DECLARE @margenSalidaMinutos INT = 180;
    -- Aumentado para capturar salidas tempranas

    -- Variables para descansos
    DECLARE @horaInicioDescanso TIME = NULL;
    DECLARE @horaFinDescanso TIME = NULL;
    DECLARE @estaEnDescanso BIT = 0;
    DECLARE @esHoraCercanaDescanso BIT = 0;

    -- Variables para marcaciones previas
    DECLARE @ultimaMarcacion DATETIME = NULL;
    DECLARE @tipoUltimaMarcacion VARCHAR(20) = NULL;
    DECLARE @horaUltimaMarcacion TIME = NULL;
    DECLARE @observacionUltimaMarcacion VARCHAR(255) = NULL;

    -- Variables para conteo de marcaciones
    DECLARE @totalMarcacionesDia INT;
    DECLARE @totalEntradasDia INT = 0;
    DECLARE @totalSalidasDia INT = 0;
    DECLARE @esperaSalida BIT = 0;

    -- Variables para análisis de marcaciones de descanso
    DECLARE @haySalidaDescanso BIT = 0;
    DECLARE @hayReingresoDescanso BIT = 0;

    -- Variable para verificar si ya hay una salida de fin de turno
    DECLARE @yaHaySalidaFinTurno BIT = 0;
    DECLARE @horaUltimaSalidaTurno TIME = NULL;

    -- Inicializar variables
    SET @tipoMarcacion = NULL;
    SET @observacion = NULL;

    -- Si no hay información de turno
    IF @horaInicioTurno IS NULL AND @horaFinTurno IS NULL
    BEGIN
        SET @tipoMarcacion = 'Sin turno asignado';
        SET @observacion = 'Marcación registrada sin turno programado';
        RETURN;
    END

    -- Obtener información del turno y descansos
    IF @idTurno IS NOT NULL AND @idTurno != '00000000-0000-0000-0000-000000000001'
    BEGIN
        SELECT TOP 1
            @horaInicioDescanso = TD.horaInicio,
            @horaFinDescanso = TD.horaFin
        FROM [dbo].[TurnosDescansos] TD
        WHERE TD.idTurno = @idTurno AND TD.activo = 1;

        -- Verificar si está dentro del período de descanso con tolerancia
        IF @horaInicioDescanso IS NOT NULL AND @horaFinDescanso IS NOT NULL
        BEGIN
            IF @horaFinDescanso > @horaInicioDescanso
            BEGIN
                -- Descanso normal (no cruza medianoche)
                IF @horaActual BETWEEN DATEADD(MINUTE, -15, @horaInicioDescanso) AND DATEADD(MINUTE, 15, @horaFinDescanso)
                    SET @estaEnDescanso = 1;
            END
            ELSE
            BEGIN
                -- Descanso cruza medianoche
                IF @horaActual >= DATEADD(MINUTE, -15, @horaInicioDescanso) OR @horaActual <= DATEADD(MINUTE, 15, @horaFinDescanso)
                    SET @estaEnDescanso = 1;
            END

            -- Verificar si está cerca del período de descanso
            IF DATEDIFF(MINUTE, @horaActual, @horaInicioDescanso) BETWEEN -30 AND 30 OR
                DATEDIFF(MINUTE, @horaActual, @horaFinDescanso) BETWEEN -30 AND 30
                SET @esHoraCercanaDescanso = 1;
        END
    END

    -- Obtener última marcación válida del día (excluyendo duplicadas)
    SELECT TOP 1
        @ultimaMarcacion = FechaHora,
        @tipoUltimaMarcacion = tipoMarcacion,
        @horaUltimaMarcacion = CAST(FechaHora AS TIME),
        @observacionUltimaMarcacion = observacion
    FROM [dbo].[Bitacora]
    WHERE Identificacion = @identificacion
        AND CAST(FechaHora AS DATE) = @fechaActual
        AND FechaHora < @fechaHora
        AND tipoMarcacion IN ('Entrada', 'Salida', 'Reingreso')
        AND tipoMarcacion != 'Duplicada'
    ORDER BY FechaHora DESC;

    -- Obtener total de marcaciones del día (excluyendo duplicadas)
    SELECT
        @totalMarcacionesDia = COUNT(*),
        @totalEntradasDia = SUM(CASE WHEN tipoMarcacion IN ('Entrada', 'Reingreso') THEN 1 ELSE 0 END),
        @totalSalidasDia = SUM(CASE WHEN tipoMarcacion = 'Salida' THEN 1 ELSE 0 END)
    FROM [dbo].[Bitacora]
    WHERE Identificacion = @identificacion
        AND CAST(FechaHora AS DATE) = @fechaActual
        AND FechaHora < @fechaHora
        AND tipoMarcacion IN ('Entrada', 'Salida', 'Reingreso')
        AND tipoMarcacion != 'Duplicada';

    -- Verificar si ya existen marcaciones de descanso
    SELECT
        @haySalidaDescanso = CASE WHEN EXISTS (
            SELECT 1
        FROM [dbo].[Bitacora]
        WHERE Identificacion = @identificacion
            AND CAST(FechaHora AS DATE) = @fechaActual
            AND tipoMarcacion = 'Salida'
            AND observacion LIKE '%Salida a descanso%'
        ) THEN 1 ELSE 0 END,
        @hayReingresoDescanso = CASE WHEN EXISTS (
            SELECT 1
        FROM [dbo].[Bitacora]
        WHERE Identificacion = @identificacion
            AND CAST(FechaHora AS DATE) = @fechaActual
            AND tipoMarcacion = 'Reingreso'
            AND observacion LIKE '%Reingreso%descanso%'
        ) THEN 1 ELSE 0 END;

    -- Verificar si ya existe una salida de fin de turno y obtener su hora
    SELECT TOP 1
        @horaUltimaSalidaTurno = CAST(FechaHora AS TIME)
    FROM [dbo].[Bitacora]
    WHERE Identificacion = @identificacion
        AND CAST(FechaHora AS DATE) = @fechaActual
        AND tipoMarcacion = 'Salida'
        AND observacion = 'Salida de turno'
    ORDER BY FechaHora DESC;

    IF @horaUltimaSalidaTurno IS NOT NULL
        SET @yaHaySalidaFinTurno = 1;
    ELSE
        SET @yaHaySalidaFinTurno = 0;

    -- LÓGICA PRINCIPAL DE DETERMINACIÓN DE TIPO DE MARCACIÓN

    -- 1. Primera marcación del día
    IF @ultimaMarcacion IS NULL OR @tipoUltimaMarcacion IS NULL
    BEGIN
        -- Primera marcación siempre es entrada
        SET @tipoMarcacion = 'Entrada';

        -- Determinar si está a tiempo o tarde
        IF DATEDIFF(MINUTE, @horaInicioTurno, @horaActual) > 0
            SET @observacion = 'Llegada tardía: ' + CAST(DATEDIFF(MINUTE, @horaInicioTurno, @horaActual) AS VARCHAR) + ' minutos';
        ELSE
            SET @observacion = 'Entrada a turno programado';
    END
    -- 2. Marcaciones subsecuentes
    ELSE
    BEGIN
        -- REGLA NUEVA: Validar secuencias de descanso incompletas cerca del fin de turno
        IF @haySalidaDescanso = 1 AND @hayReingresoDescanso = 0 AND
            DATEDIFF(MINUTE, @horaActual, @horaFinTurno) BETWEEN 0 AND 120
        BEGIN
            -- Si está en horario de salida y faltó reingreso de descanso
            SET @tipoMarcacion = 'Salida';
            SET @observacion = 'Salida de turno - reingreso de descanso faltante';
        END
        -- Si es la hora de fin de turno (dentro del margen)
        ELSE IF DATEDIFF(MINUTE, @horaActual, @horaFinTurno) BETWEEN -@margenSalidaMinutos AND @margenSalidaMinutos
        BEGIN
            -- Caso especial: Si la última marcación fue salida a descanso sin reingreso
            IF @tipoUltimaMarcacion = 'Salida' AND
                @observacionUltimaMarcacion LIKE '%descanso%' AND
                @hayReingresoDescanso = 0
            BEGIN
                SET @tipoMarcacion = 'Salida';
                SET @observacion = 'Salida de turno - reingreso de descanso faltante';
            END
            -- Caso especial: Si la última marcación fue entrada después de salida a descanso
            ELSE IF @tipoUltimaMarcacion = 'Entrada' AND
                @haySalidaDescanso = 1 AND
                @hayReingresoDescanso = 0
            BEGIN
                SET @tipoMarcacion = 'Salida';
                SET @observacion = 'Salida de turno - secuencia de descanso incompleta';
            END
            -- Verificar si es realmente una salida de turno o una marcación intermedia
            ELSE
            BEGIN
                DECLARE @minutosDesdeUltimaMarcacion INT = DATEDIFF(MINUTE, ISNULL(@ultimaMarcacion, '1900-01-01'), @fechaHora);

                -- Solo considerar duplicada si:
                -- 1. Ya hay una salida de turno registrada
                -- 2. La diferencia con la última marcación es menor a 30 minutos
                -- 3. La última marcación fue una salida de turno
                IF @yaHaySalidaFinTurno = 1 AND
                    @minutosDesdeUltimaMarcacion < 30 AND
                    @tipoUltimaMarcacion = 'Salida' AND
                    @observacionUltimaMarcacion LIKE '%Salida de turno%'
                BEGIN
                    SET @tipoMarcacion = 'Duplicada';
                    SET @observacion = 'Marcación duplicada - ya existe una salida registrada para el fin de turno';
                END
                ELSE
                BEGIN
                    -- Es una salida legítima de turno
                    SET @tipoMarcacion = 'Salida';
                    SET @observacion = 'Salida de turno';

                    -- Verificar secuencia de descanso
                    IF @haySalidaDescanso = 1 AND @hayReingresoDescanso = 0
                        SET @observacion = @observacion + ' - posible reingreso de descanso faltante';

                    -- Si la última marcación fue una salida (no de turno), cambiar a entrada
                    IF @tipoUltimaMarcacion = 'Salida' AND @observacionUltimaMarcacion NOT LIKE '%Salida de turno%'
                    BEGIN
                        SET @tipoMarcacion = 'Entrada';
                        SET @observacion = 'Entrada adicional - posible error en secuencia';
                    END
                END
            END
        END
        -- Si la marcación está en horario de salida pero está clasificada como entrada
        ELSE IF @tipoMarcacion = 'Entrada' AND
            DATEDIFF(MINUTE, @horaActual, @horaFinTurno) BETWEEN -@margenSalidaMinutos AND @margenSalidaMinutos
        BEGIN
            -- Reclasificar como salida si hay inconsistencias previas
            IF @haySalidaDescanso = 1 AND @hayReingresoDescanso = 0
            BEGIN
                SET @tipoMarcacion = 'Salida';
                SET @observacion = 'Salida de turno (reclasificada) - secuencia de descanso incompleta';
            END
        END
        -- Si está en período de descanso o cerca
        ELSE IF @estaEnDescanso = 1 OR @esHoraCercanaDescanso = 1
        BEGIN
            -- Si la última marcación fue entrada/reingreso y estamos cerca del inicio del descanso
            IF @tipoUltimaMarcacion IN ('Entrada', 'Reingreso') AND
                DATEDIFF(MINUTE, @horaActual, @horaInicioDescanso) BETWEEN -30 AND 30
            BEGIN
                SET @tipoMarcacion = 'Salida';
                SET @observacion = 'Salida a descanso';
            END
            -- Si la última marcación fue salida y estamos cerca del fin del descanso
            ELSE IF @tipoUltimaMarcacion = 'Salida' AND
                (@observacionUltimaMarcacion LIKE '%descanso%' OR @observacionUltimaMarcacion LIKE '%Salida a%') AND
                DATEDIFF(MINUTE, @horaActual, @horaFinDescanso) BETWEEN -30 AND 30
            BEGIN
                SET @tipoMarcacion = 'Reingreso';
                SET @observacion = 'Reingreso de descanso';
            END
            -- Si está dentro del período de descanso
            ELSE IF @estaEnDescanso = 1
            BEGIN
                -- Si la última fue entrada/reingreso, esta debe ser salida
                IF @tipoUltimaMarcacion IN ('Entrada', 'Reingreso')
                BEGIN
                    SET @tipoMarcacion = 'Salida';
                    SET @observacion = 'Salida a descanso';
                END
                -- Si la última fue salida (de descanso), esta debe ser reingreso
                ELSE IF @tipoUltimaMarcacion = 'Salida' AND
                    (@observacionUltimaMarcacion LIKE '%descanso%' OR @observacionUltimaMarcacion LIKE '%Salida a%')
                BEGIN
                    SET @tipoMarcacion = 'Reingreso';
                    SET @observacion = 'Reingreso de descanso';
                END
            END
        END
        -- Lógica estándar de alternancia
        ELSE
        BEGIN
            -- Si la última marcación fue entrada/reingreso, esta debe ser salida
            IF @tipoUltimaMarcacion IN ('Entrada', 'Reingreso')
            BEGIN
                SET @tipoMarcacion = 'Salida';

                -- Determinar el tipo de salida según la hora
                IF @horaInicioDescanso IS NOT NULL AND
                    ABS(DATEDIFF(MINUTE, @horaActual, @horaInicioDescanso)) <= 60
                BEGIN
                    SET @observacion = 'Salida a descanso';
                END
                -- Si está cerca del fin del turno pero no lo suficiente para ser salida de turno
                ELSE IF DATEDIFF(MINUTE, @horaActual, @horaFinTurno) BETWEEN 30 AND @margenSalidaMinutos
                BEGIN
                    SET @observacion = 'Salida intermedia cerca del fin de turno';
                END
                ELSE
                BEGIN
                    SET @observacion = 'Salida intermedia durante turno';
                END
            END
            -- Si la última marcación fue salida, esta debe ser entrada/reingreso
            ELSE
            BEGIN
                -- Si ya hubo salida a descanso pero no reingreso
                IF @haySalidaDescanso = 1 AND @hayReingresoDescanso = 0
                BEGIN
                    SET @tipoMarcacion = 'Reingreso';
                    SET @observacion = 'Reingreso de descanso';
                END
                -- Si estamos después del período típico de descanso
                ELSE IF @horaInicioDescanso IS NOT NULL AND @horaActual > DATEADD(MINUTE, 30, @horaFinDescanso)
                BEGIN
                    SET @tipoMarcacion = 'Reingreso';
                    SET @observacion = 'Reingreso durante turno';
                END
                ELSE
                BEGIN
                    SET @tipoMarcacion = 'Entrada';
                    SET @observacion = 'Entrada adicional durante turno';
                END
            END
        END
    END

    -- Verificar marcaciones muy cercanas (posibles duplicados)
    IF @ultimaMarcacion IS NOT NULL AND DATEDIFF(MINUTE, @ultimaMarcacion, @fechaHora) < 2
    BEGIN
        -- No marcar como duplicada si es una salida de turno legítima
        IF NOT (@tipoMarcacion = 'Salida' AND @observacion = 'Salida de turno' AND
            DATEDIFF(MINUTE, @horaActual, @horaFinTurno) BETWEEN -@margenSalidaMinutos AND @margenSalidaMinutos)
        BEGIN
            SET @tipoMarcacion = 'Duplicada';
            SET @observacion = 'Marcación duplicada - muy cercana a la anterior';
        END
    END

    -- Garantizar que siempre haya un tipo de marcación
    IF @tipoMarcacion IS NULL
    BEGIN
        SET @tipoMarcacion = 'Sin Definir';
        SET @observacion = ISNULL(@observacion, '') + ' - Tipo de marcación indeterminado';
    END

    -- Registrar alerta si hay secuencia de descanso incompleta
    IF @haySalidaDescanso = 1 AND @hayReingresoDescanso = 0 AND
        @tipoMarcacion = 'Salida' AND @observacion LIKE '%faltante%'
    BEGIN
        DECLARE @idUsuario UNIQUEIDENTIFIER;

        -- Obtener ID del usuario
        SELECT TOP 1
            @idUsuario = idUsuario
        FROM [dbo].[vUsuariosAppBiometrico]
        WHERE Identificacion = @identificacion;

        -- Insertar alerta (si existe la tabla)
        IF OBJECT_ID('dbo.AlertasMarcaciones', 'U') IS NOT NULL
        BEGIN
            INSERT INTO AlertasMarcaciones
                (idUsuario, fecha, tipo, descripcion)
            VALUES
                (@idUsuario, @fechaActual, 'DescansoIncompleto',
                    'Secuencia de descanso incompleta - Falta reingreso después de salida a descanso');
        END
    END
END;
GO

/*  -------------------------------------------------- */
/* Procedimiento para guardar turnos */
/*  -------------------------------------------------- */
CREATE PROCEDURE [dbo].[SAVE_Turnos]
    @descripcion VARCHAR
(100),
    @horaInicio TIME
(7),
    @horaFin TIME
(7),
    @duracionHoras DECIMAL
(5,2),
    @activo BIT = 1,
    @idUsuario UNIQUEIDENTIFIER,
    @inicioDescanso TIME
(7) = NULL,
    @finDescanso TIME
(7) = NULL,
    @duracionDescansoMinutos INT = NULL,
    @descripcionDescanso VARCHAR
(100) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @idTurno UNIQUEIDENTIFIER;
    DECLARE @duracionCalculada DECIMAL(5,2);
    DECLARE @minutosEntreTurnos INT;
    DECLARE @errorMessage NVARCHAR(255);
    DECLARE @tieneDescanso BIT = 0;

    -- Validar que los datos no sean nulos
    IF @descripcion IS NULL OR @horaInicio IS NULL OR @horaFin IS NULL OR @duracionHoras IS NULL OR @idUsuario IS NULL
    BEGIN
        SET @errorMessage = 'La descripción, hora de inicio, hora de fin, duración y usuario son campos obligatorios.';
        RAISERROR(@errorMessage, 16, 1);
        RETURN -1;
    END

    -- Determinar si se incluye información de descanso
    IF @inicioDescanso IS NOT NULL AND @finDescanso IS NOT NULL
    BEGIN
        SET @tieneDescanso = 1;

        -- Validar que el período de descanso esté dentro del horario del turno
        -- Caso 1: Si el turno no cruza la medianoche
        IF @horaFin > @horaInicio 
        BEGIN
            IF NOT (@inicioDescanso >= @horaInicio AND @finDescanso <= @horaFin)
            BEGIN
                SET @errorMessage = 'El período de descanso debe estar dentro del horario del turno.';
                RAISERROR(@errorMessage, 16, 1);
                RETURN -3;
            END
        END
        -- Caso 2: Si el turno cruza la medianoche
        ELSE 
        BEGIN
            IF NOT ((@inicioDescanso >= @horaInicio OR @inicioDescanso <= @horaFin) AND
                (@finDescanso >= @horaInicio OR @finDescanso <= @horaFin) AND
                (@inicioDescanso < @finDescanso OR
                (@inicioDescanso > @finDescanso AND @inicioDescanso >= @horaInicio AND @finDescanso <= @horaFin)))
            BEGIN
                SET @errorMessage = 'El período de descanso debe estar dentro del horario del turno.';
                RAISERROR(@errorMessage, 16, 1);
                RETURN -4;
            END
        END

        -- Calcular duración del descanso en minutos si no fue proporcionada
        IF @duracionDescansoMinutos IS NULL
        BEGIN
            IF @finDescanso > @inicioDescanso
                SET @duracionDescansoMinutos = DATEDIFF(MINUTE, @inicioDescanso, @finDescanso);
            ELSE -- Si el descanso cruza medianoche
                SET @duracionDescansoMinutos = DATEDIFF(MINUTE, @inicioDescanso, '23:59:59.9999999') + 
                                              DATEDIFF(MINUTE, '00:00:00.0000000', @finDescanso) + 1;
        END
    END

    -- Verificar si ya existe un turno con el mismo horario
    IF EXISTS (
        SELECT 1
    FROM [dbo].[Turnos]
    WHERE horaInicio = @horaInicio
        AND horaFin = @horaFin
        AND activo = 1
    )
    BEGIN
        SET @errorMessage = 'Error: Ya existe un turno activo con el mismo horario de ' + 
                          CAST(@horaInicio AS VARCHAR(8)) + ' a ' + CAST(@horaFin AS VARCHAR(8));
        RAISERROR(@errorMessage, 16, 1);
        RETURN -5;
    END

    -- Calcular los minutos entre la hora de inicio y fin
    IF @horaFin > @horaInicio
        SET @minutosEntreTurnos = DATEDIFF(MINUTE, @horaInicio, @horaFin);
    ELSE -- Si la hora fin es menor (cruza medianoche)
        SET @minutosEntreTurnos = DATEDIFF(MINUTE, @horaInicio, '23:59:59.9999999') + 
                                 DATEDIFF(MINUTE, '00:00:00.0000000', @horaFin) + 1;

    -- Convertir minutos a horas decimales para comparar con @duracionHoras
    SET @duracionCalculada = CAST((@minutosEntreTurnos / 60.0) AS DECIMAL(5,2));

    -- Ajustar la duración calculada si hay descanso
    IF @tieneDescanso = 1
        SET @duracionCalculada = CAST((@minutosEntreTurnos - @duracionDescansoMinutos) / 60.0 AS DECIMAL(5,2));

    -- Validar que la duración proporcionada sea correcta con un margen de error de 0.1 horas (6 minutos)
    IF ABS(@duracionHoras - @duracionCalculada) > 0.1
    BEGIN
        SET @errorMessage = 'Error: La duración proporcionada (' + 
                          CAST(@duracionHoras AS VARCHAR(10)) + 
                          ' horas) no coincide con el cálculo entre la hora de inicio y fin (' + 
                          CAST(@duracionCalculada AS VARCHAR(10)) + ' horas).';
        RAISERROR(@errorMessage, 16, 1);
        RETURN -6;
    END

    BEGIN TRY
        -- Generar nuevo ID para el turno
        SET @idTurno = NEWID();
        
        -- Iniciar transacción
        BEGIN TRANSACTION;
        
        -- Insertar el nuevo turno
        INSERT INTO [dbo].[Turnos]
        (idTurno, descripcion, horaInicio, horaFin, duracionHoras, activo, idUsuarioCreador)
    VALUES
        (@idTurno, @descripcion, @horaInicio, @horaFin, @duracionHoras, @activo, @idUsuario);
            
        -- Insertar el registro de descanso si aplica
        IF @tieneDescanso = 1
        BEGIN
        INSERT INTO [dbo].[TurnosDescansos]
            (idTurnoDescanso, idTurno, horaInicio, horaFin, duracionMinutos, descripcion, activo)
        VALUES
            (NEWID(), @idTurno, @inicioDescanso, @finDescanso, @duracionDescansoMinutos,
                ISNULL(@descripcionDescanso, 'Período de descanso'), 1);
    END
        
        COMMIT TRANSACTION;
        
        -- Retornar el ID del turno creado
        SELECT @idTurno AS idTurno;
        
        RETURN 0; -- Éxito
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
            
        SET @errorMessage = 'Error al guardar el turno: ' + ERROR_MESSAGE();
        RAISERROR(@errorMessage, 16, 1);
        RETURN -100; -- Error general de base de datos
    END CATCH
END;


/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */
CREATE PROCEDURE [dbo].[SAVE_ProgramacionTurnos]
    @idUsuario UNIQUEIDENTIFIER,
    @idCentroTrabajo UNIQUEIDENTIFIER,
    @fechaInicio DATE,
    @fechaFin DATE,
    @idUsuarioRegistra UNIQUEIDENTIFIER,
    @idTurnoLunes UNIQUEIDENTIFIER = NULL,
    @idTurnoMartes UNIQUEIDENTIFIER = NULL,
    @idTurnoMiercoles UNIQUEIDENTIFIER = NULL,
    @idTurnoJueves UNIQUEIDENTIFIER = NULL,
    @idTurnoViernes UNIQUEIDENTIFIER = NULL,
    @idTurnoSabado UNIQUEIDENTIFIER = NULL,
    @idTurnoDomingo UNIQUEIDENTIFIER = NULL,
    @idTurnoDefault UNIQUEIDENTIFIER = NULL
-- Turno para usar si no se especifica para un día particular
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @idProgramacion UNIQUEIDENTIFIER = NEWID();
    DECLARE @ErrorMessage NVARCHAR(255);

    -- Validaciones básicas
    IF @fechaInicio > @fechaFin
    BEGIN
        SET @ErrorMessage = 'La fecha de inicio no puede ser posterior a la fecha de fin';
        RAISERROR(@ErrorMessage, 16, 1);
        RETURN -1;
    END

    -- Si no se proporciona al menos un turno por día o un turno default, error
    IF @idTurnoDefault IS NULL AND @idTurnoLunes IS NULL AND @idTurnoMartes IS NULL
        AND @idTurnoMiercoles IS NULL AND @idTurnoJueves IS NULL AND @idTurnoViernes IS NULL
        AND @idTurnoSabado IS NULL AND @idTurnoDomingo IS NULL
    BEGIN
        SET @ErrorMessage = 'Debe especificar al menos un turno predeterminado o un turno para cada día';
        RAISERROR(@ErrorMessage, 16, 1);
        RETURN -2;
    END

    -- Iniciar transacción para asegurar consistencia
    BEGIN TRANSACTION;

    BEGIN TRY
        -- Insertar cabecera de programación
        INSERT INTO [dbo].[ProgramacionTurnos]
        (idProgramacion, idUsuario, idCentroTrabajo, fechaInicio, fechaFin, fechaRegistro, idUsuarioRegistra)
    VALUES
        (@idProgramacion, @idUsuario, @idCentroTrabajo, @fechaInicio, @fechaFin, GETDATE(), @idUsuarioRegistra);
        
        -- Insertar detalles para cada día en el rango
        DECLARE @fechaActual DATE = @fechaInicio;
        DECLARE @diaSemana INT;
        DECLARE @idTurnoDia UNIQUEIDENTIFIER;
        DECLARE @cruzaDia BIT;
        DECLARE @fechaFinTurno DATE;
        
        WHILE @fechaActual <= @fechaFin
        BEGIN
        -- Determinar el día de la semana (1=Domingo, 2=Lunes, ..., 7=Sábado)
        SET @diaSemana = DATEPART(WEEKDAY, @fechaActual);

        -- Determinar qué turno usar para este día
        SET @idTurnoDia = CASE 
                    WHEN @diaSemana = 1 AND @idTurnoDomingo IS NOT NULL THEN @idTurnoDomingo
                    WHEN @diaSemana = 2 AND @idTurnoLunes IS NOT NULL THEN @idTurnoLunes
                    WHEN @diaSemana = 3 AND @idTurnoMartes IS NOT NULL THEN @idTurnoMartes
                    WHEN @diaSemana = 4 AND @idTurnoMiercoles IS NOT NULL THEN @idTurnoMiercoles
                    WHEN @diaSemana = 5 AND @idTurnoJueves IS NOT NULL THEN @idTurnoJueves
                    WHEN @diaSemana = 6 AND @idTurnoViernes IS NOT NULL THEN @idTurnoViernes
                    WHEN @diaSemana = 7 AND @idTurnoSabado IS NOT NULL THEN @idTurnoSabado
                    ELSE @idTurnoDefault
                END;

        -- Solo insertar si hay un turno válido para este día
        IF @idTurnoDia IS NOT NULL
            BEGIN
            -- Verificar si el turno cruza días (horaFin < horaInicio indica que termina al día siguiente)
            SELECT @cruzaDia = CASE WHEN horaFin < horaInicio THEN 1 ELSE 0 END
            FROM [dbo].[Turnos]
            WHERE idTurno = @idTurnoDia;

            -- Calcular la fecha de fin del turno
            SET @fechaFinTurno = CASE WHEN @cruzaDia = 1 THEN DATEADD(DAY, 1, @fechaActual) ELSE @fechaActual END;

            -- Insertar el detalle de programación con la fecha de fin correcta
            INSERT INTO [dbo].[ProgramacionTurnosDetalle]
                (idProgramacionDetalle, idProgramacion, fechaInicio, fechaFin, idTurno)
            VALUES
                (NEWID(), @idProgramacion, @fechaActual, @fechaFinTurno, @idTurnoDia);
        END

        -- Avanzar al siguiente día
        SET @fechaActual = DATEADD(DAY, 1, @fechaActual);
    END

        -- Después de insertar todos los detalles, actualizar las marcaciones
        EXEC [dbo].[UPDATE_MarcacionesConTurnoAsignado] @idUsuario, @fechaInicio, @fechaFin;
        
        COMMIT TRANSACTION;
        
        -- Retornar el ID de la programación creada
        SELECT @idProgramacion AS idProgramacion;
        RETURN 0; -- Éxito
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        
        SET @ErrorMessage = 'Error al guardar la programación de turnos: ' + ERROR_MESSAGE();
        RAISERROR(@ErrorMessage, 16, 1);
        RETURN -100; -- Error general de base de datos
    END CATCH
END


/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */
CREATE OR ALTER PROCEDURE [dbo].[UPDATE_MarcacionesConTurnoAsignado]
    @idUsuario UNIQUEIDENTIFIER = NULL,
    @fechaInicio DATE = NULL,
    @fechaFin DATE = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- Tabla temporal para almacenar las marcaciones que necesitan actualización
    CREATE TABLE #MarcacionesParaActualizar
    (
        idBitacora UNIQUEIDENTIFIER,
        Identificacion VARCHAR(15),
        FechaHora DATETIME,
        idBiometrico UNIQUEIDENTIFIER,
        idProgramacionDetalle UNIQUEIDENTIFIER,
        idTurno UNIQUEIDENTIFIER,
        horaInicioTurno TIME,
        horaFinTurno TIME,
        cruzaDia BIT,
        fechaTurno DATE,
        fechaFinTurno DATE
    );

    -- Identificar las marcaciones que tienen turnos asignados y necesitan actualización
    INSERT INTO #MarcacionesParaActualizar
    SELECT
        B.idBitacora,
        B.Identificacion,
        B.FechaHora,
        B.idBiometrico,
        PD.idProgramacionDetalle,
        PD.idTurno,
        T.horaInicio,
        T.horaFin,
        CASE WHEN T.horaFin < T.horaInicio THEN 1 ELSE 0 END AS cruzaDia,
        PD.fechaInicio AS fechaTurno,
        PD.fechaFin AS fechaFinTurno
    FROM [dbo].[Bitacora] B
        INNER JOIN [dbo].[vUsuariosAppBiometrico] U ON B.Identificacion = U.Identificacion
        INNER JOIN [dbo].[ProgramacionTurnos] PT ON U.idUsuario = PT.idUsuario
        INNER JOIN [dbo].[ProgramacionTurnosDetalle] PD ON PT.idProgramacion = PD.idProgramacion
        INNER JOIN [dbo].[Turnos] T ON PD.idTurno = T.idTurno
    WHERE 
        (U.idUsuario = @idUsuario OR @idUsuario IS NULL)
        AND CAST(B.FechaHora AS DATE) BETWEEN @fechaInicio AND @fechaFin
        AND PT.activo = 1
        AND (
            (CAST(B.FechaHora AS DATE) = PD.fechaInicio)
        OR
        (CAST(B.FechaHora AS DATE) = PD.fechaFin AND PD.fechaInicio < PD.fechaFin)
        );

    -- Declaración de variables para la actualización
    DECLARE @idBitacora UNIQUEIDENTIFIER;
    DECLARE @Identificacion VARCHAR(15);
    DECLARE @FechaHora DATETIME;
    DECLARE @idBiometrico UNIQUEIDENTIFIER;
    DECLARE @idTurno UNIQUEIDENTIFIER;
    DECLARE @horaInicioTurno TIME;
    DECLARE @horaFinTurno TIME;
    DECLARE @cruzaDia BIT;
    DECLARE @fechaTurno DATE;
    DECLARE @fechaFinTurno DATE;
    DECLARE @tipoMarcacion VARCHAR(20);
    DECLARE @observacion VARCHAR(255);
    DECLARE @idProgramacionDetalleActual UNIQUEIDENTIFIER;

    -- Cursor para procesar cada marcación individualmente
    DECLARE curMarcaciones CURSOR FOR 
        SELECT
        idBitacora,
        Identificacion,
        FechaHora,
        idBiometrico,
        idTurno,
        horaInicioTurno,
        horaFinTurno,
        cruzaDia,
        fechaTurno,
        fechaFinTurno,
        idProgramacionDetalle
    FROM #MarcacionesParaActualizar
    ORDER BY Identificacion, FechaHora;

    OPEN curMarcaciones;
    FETCH NEXT FROM curMarcaciones INTO 
        @idBitacora, @Identificacion, @FechaHora, @idBiometrico, @idTurno,
        @horaInicioTurno, @horaFinTurno, @cruzaDia, @fechaTurno, @fechaFinTurno,
        @idProgramacionDetalleActual;

    WHILE @@FETCH_STATUS = 0
    BEGIN
        -- Llamar al procedimiento para determinar el tipo de marcación
        EXEC [dbo].[GET_TipoMarcacion]
            @Identificacion,
            @FechaHora,
            @idBiometrico,
            @horaInicioTurno,
            @horaFinTurno,
            @cruzaDia,
            @fechaTurno,
            @fechaFinTurno,
            @idTurno,
            @tipoMarcacion OUTPUT,
            @observacion OUTPUT;

        -- Actualizar la marcación con el tipo y la observación determinados
        UPDATE [dbo].[Bitacora]
        SET 
            tipoMarcacion = @tipoMarcacion,
            observacion = @observacion,
            idProgramacionDetalle = @idProgramacionDetalleActual,
            idTurno = @idTurno
        WHERE 
            idBitacora = @idBitacora;

        FETCH NEXT FROM curMarcaciones INTO 
            @idBitacora, @Identificacion, @FechaHora, @idBiometrico, @idTurno,
            @horaInicioTurno, @horaFinTurno, @cruzaDia, @fechaTurno, @fechaFinTurno,
            @idProgramacionDetalleActual;
    END

    CLOSE curMarcaciones;
    DEALLOCATE curMarcaciones;

    -- Segunda pasada para corregir inconsistencias en la secuencia SOLO cuando sea necesario
    ;
    WITH
        SecuenciaCorrecta
        AS
        (
            SELECT
                B.idBitacora,
                B.Identificacion,
                B.FechaHora,
                B.tipoMarcacion,
                B.observacion,
                ROW_NUMBER() OVER (PARTITION BY B.Identificacion, CAST(B.FechaHora AS DATE) ORDER BY B.FechaHora) AS NumMarcacion,
                LAG(B.tipoMarcacion) OVER (PARTITION BY B.Identificacion, CAST(B.FechaHora AS DATE) ORDER BY B.FechaHora) AS tipoMarcacionAnterior
            FROM [dbo].[Bitacora] B
                INNER JOIN #MarcacionesParaActualizar M ON B.idBitacora = M.idBitacora
            WHERE B.tipoMarcacion != 'Duplicada'
        )
    UPDATE B
    SET 
        tipoMarcacion = CASE 
            WHEN SC.tipoMarcacionAnterior = SC.tipoMarcacion
        AND SC.tipoMarcacion = 'Entrada'
        AND SC.observacion NOT LIKE '%descanso%'
            THEN 'Salida'
            WHEN SC.tipoMarcacionAnterior = SC.tipoMarcacion
        AND SC.tipoMarcacion = 'Salida'
        AND SC.observacion NOT LIKE '%Salida de turno%'
        AND SC.observacion NOT LIKE '%descanso%'
            THEN 'Entrada'
            ELSE B.tipoMarcacion
        END,
        observacion = CASE
            WHEN SC.tipoMarcacionAnterior = SC.tipoMarcacion
        AND SC.tipoMarcacion = 'Entrada'
        AND SC.observacion NOT LIKE '%descanso%'
            THEN 'Salida de turno - posible marcación intermedia faltante'
            WHEN SC.tipoMarcacionAnterior = SC.tipoMarcacion
        AND SC.tipoMarcacion = 'Salida'
        AND SC.observacion NOT LIKE '%Salida de turno%'
        AND SC.observacion NOT LIKE '%descanso%'
            THEN 'Entrada adicional - posible marcación intermedia faltante'
            ELSE B.observacion
        END
    FROM [dbo].[Bitacora] B
        INNER JOIN SecuenciaCorrecta SC ON B.idBitacora = SC.idBitacora
    WHERE SC.tipoMarcacionAnterior = SC.tipoMarcacion
        AND SC.NumMarcacion > 1
        AND SC.tipoMarcacion != 'Duplicada'
        AND (
        (SC.tipoMarcacion = 'Entrada' AND SC.observacion NOT LIKE '%descanso%')
        OR
        (SC.tipoMarcacion = 'Salida' AND SC.observacion NOT LIKE '%Salida de turno%' AND SC.observacion NOT LIKE '%descanso%')
    );

    -- Limpiar tabla temporal
    DROP TABLE #MarcacionesParaActualizar;

    -- Retornar conteo de registros actualizados
    DECLARE @registrosActualizados INT;

    SELECT @registrosActualizados = COUNT(*)
    FROM [dbo].[Bitacora] B
    WHERE EXISTS (
        SELECT 1
    FROM [dbo].[vUsuariosAppBiometrico] U
        INNER JOIN [dbo].[ProgramacionTurnos] PT ON U.idUsuario = PT.idUsuario
        INNER JOIN [dbo].[ProgramacionTurnosDetalle] PD ON PT.idProgramacion = PD.idProgramacion
    WHERE B.Identificacion = U.Identificacion
        AND (U.idUsuario = @idUsuario OR @idUsuario IS NULL)
        AND CAST(B.FechaHora AS DATE) BETWEEN @fechaInicio AND @fechaFin
        AND (
                CAST(B.FechaHora AS DATE) = PD.fechaInicio OR
        (CAST(B.FechaHora AS DATE) = PD.fechaFin AND PD.fechaInicio < PD.fechaFin)
            )
        AND PT.activo = 1
    );

    SELECT @registrosActualizados AS RegistrosActualizados;
END;
GO

/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */
ALTER PROCEDURE [dbo].[GET_ReporteJornadasLaborales]
    @FechaInicio DATE,
    @FechaFin DATE,
    @IdCentroTrabajo UNIQUEIDENTIFIER,
    @IdUsuario UNIQUEIDENTIFIER = NULL,
    @Cargo VARCHAR(100) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- Validar parámetros requeridos
    IF @FechaInicio IS NULL OR @FechaFin IS NULL OR @IdCentroTrabajo IS NULL
    BEGIN
        RAISERROR('Los parámetros FechaInicio, FechaFin e IdCentroTrabajo son obligatorios', 16, 1);
        RETURN;
    END

    -- Crear tabla temporal con tamaños ampliados
    CREATE TABLE #Resultados
    (
        Fecha DATE,
        DiaSemana VARCHAR(15),
        Cargo VARCHAR(100),
        NombreTrabajador VARCHAR(150),
        Cedula VARCHAR(20),
        Jornada VARCHAR(15),
        InicioJornada TIME,
        InicioReceso TIME NULL,
        FinReceso TIME NULL,
        FinJornada TIME,
        Total1raJornada VARCHAR(20) NULL,
        Total2daJornada VARCHAR(20) NULL,
        TotalTrabajado VARCHAR(20),
        Sobretiempo VARCHAR(20)
    );

    -- Obtener datos base de marcaciones con manejo explícito de NULLs
    WITH
        MarcacionesTurno
        AS
        (
            SELECT
                u.idUsuario,
                ISNULL(u.NombreCompleto, '') AS NombreCompleto,
                ISNULL(u.Identificacion, '') AS Identificacion,
                ISNULL(u.Cargo, '') AS Cargo,
                b.FechaHora,
                ISNULL(b.tipoMarcacion, '') AS tipoMarcacion,
                ISNULL(b.observacion, '') AS observacion,
                t.idTurno,
                ISNULL(t.descripcion, '') AS DescripcionTurno,
                t.horaInicio AS HoraInicioTurno,
                t.horaFin AS HoraFinTurno,
                td.horaInicio AS HoraInicioDescanso,
                td.horaFin AS HoraFinDescanso,
                ptd.fechaInicio AS FechaInicioTurno,
                ptd.fechaFin AS FechaFinTurno,
                CASE WHEN t.horaFin < t.horaInicio THEN 1 ELSE 0 END AS TurnoNocturno
            FROM [dbo].[Bitacora] b
                INNER JOIN [dbo].[vUsuariosAppBiometrico] u ON b.Identificacion = u.Identificacion
                INNER JOIN [dbo].[Biometricos] bio ON b.idBiometrico = bio.idBiometrico
                INNER JOIN [dbo].[ProgramacionTurnosDetalle] ptd ON b.idProgramacionDetalle = ptd.idProgramacionDetalle
                INNER JOIN [dbo].[ProgramacionTurnos] pt ON ptd.idProgramacion = pt.idProgramacion
                INNER JOIN [dbo].[Turnos] t ON ptd.idTurno = t.idTurno
                LEFT JOIN [dbo].[TurnosDescansos] td ON t.idTurno = td.idTurno AND td.activo = 1
            WHERE 
            CAST(b.FechaHora AS DATE) BETWEEN @FechaInicio AND @FechaFin
                AND bio.idCentroTrabajo = @IdCentroTrabajo
                AND (@IdUsuario IS NULL OR u.idUsuario = @IdUsuario)
                AND (@Cargo IS NULL OR u.Cargo LIKE '%' + @Cargo + '%')
        ),

        -- Agrupar marcaciones por día y usuario
        JornadasAgrupadas
        AS
        (
            SELECT
                idUsuario,
                MAX(NombreCompleto) AS NombreCompleto,
                MAX(Identificacion) AS Identificacion,
                MAX(Cargo) AS Cargo,
                FechaInicioTurno AS FechaTurno,
                DATENAME(WEEKDAY, FechaInicioTurno) AS DiaSemana,
                MAX(DescripcionTurno) AS DescripcionTurno,
                MAX(HoraInicioTurno) AS HoraInicioTurno,
                MAX(HoraFinTurno) AS HoraFinTurno,
                MAX(HoraInicioDescanso) AS HoraInicioDescanso,
                MAX(HoraFinDescanso) AS HoraFinDescanso,
                MAX(TurnoNocturno) AS TurnoNocturno,
                MIN(CASE WHEN tipoMarcacion IN ('Entrada', 'Reingreso') THEN FechaHora ELSE NULL END) AS EntradaReal,
                MAX(CASE WHEN tipoMarcacion = 'Salida' AND observacion NOT LIKE '%descanso%' THEN FechaHora ELSE NULL END) AS SalidaReal,
                MIN(CASE WHEN tipoMarcacion = 'Salida' AND observacion LIKE '%descanso%' THEN FechaHora ELSE NULL END) AS SalidaDescanso,
                MAX(CASE WHEN tipoMarcacion = 'Reingreso' THEN FechaHora ELSE NULL END) AS ReingresoDescanso
            FROM MarcacionesTurno
            GROUP BY 
            idUsuario,
            FechaInicioTurno,
            DATENAME(WEEKDAY, FechaInicioTurno)
        )

    -- Calcular tiempos con manejo de NULLs
    INSERT INTO #Resultados
    SELECT
        FechaTurno AS Fecha,
        DiaSemana,
        Cargo,
        NombreCompleto AS NombreTrabajador,
        Identificacion AS Cedula,
        CASE WHEN TurnoNocturno = 1 THEN 'Nocturna' ELSE 'Diurna' END AS Jornada,

        CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) AS InicioJornada,
        CASE 
            WHEN SalidaDescanso IS NOT NULL THEN CAST(SalidaDescanso AS TIME)
            WHEN HoraInicioDescanso IS NOT NULL THEN HoraInicioDescanso
            ELSE NULL 
        END AS InicioReceso,

        CASE 
            WHEN ReingresoDescanso IS NOT NULL THEN CAST(ReingresoDescanso AS TIME)
            WHEN HoraFinDescanso IS NOT NULL THEN HoraFinDescanso
            ELSE NULL 
        END AS FinReceso,

        CAST(ISNULL(SalidaReal, '23:59:59') AS TIME) AS FinJornada,

        -- Total 1ra Jornada
        CASE 
            WHEN SalidaDescanso IS NOT NULL THEN 
                CAST(CAST(DATEDIFF(MINUTE, EntradaReal, SalidaDescanso)/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
            WHEN HoraInicioDescanso IS NOT NULL THEN 
                CAST(CAST(DATEDIFF(MINUTE, 
                    CASE WHEN CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) < HoraInicioDescanso 
                         THEN EntradaReal 
                         ELSE DATEADD(DAY, -1, EntradaReal) END,
                    DATEADD(DAY, CASE WHEN CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) < HoraInicioDescanso THEN 0 ELSE 1 END, 
                           DATEADD(SECOND, DATEDIFF(SECOND, 0, HoraInicioDescanso), CAST(CAST(EntradaReal AS DATE) AS DATETIME))))/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
            ELSE NULL
        END AS Total1raJornada,

        -- Total 2da Jornada
        CASE 
            WHEN ReingresoDescanso IS NOT NULL THEN 
                CAST(CAST(DATEDIFF(MINUTE, ReingresoDescanso, ISNULL(SalidaReal, '23:59:59'))/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
            WHEN HoraFinDescanso IS NOT NULL THEN 
                CAST(CAST(DATEDIFF(MINUTE, 
                    DATEADD(SECOND, DATEDIFF(SECOND, 0, HoraFinDescanso), CAST(CAST(EntradaReal AS DATE) AS DATETIME)),
                    CASE WHEN CAST(ISNULL(SalidaReal, '23:59:59') AS TIME) > HoraFinDescanso 
                         THEN SalidaReal 
                         ELSE DATEADD(DAY, 1, SalidaReal) END)/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
            ELSE NULL
        END AS Total2daJornada,

        -- Total trabajado
        CASE
            WHEN SalidaDescanso IS NOT NULL AND ReingresoDescanso IS NOT NULL THEN
                CAST(CAST((DATEDIFF(MINUTE, EntradaReal, SalidaDescanso) + 
                      DATEDIFF(MINUTE, ReingresoDescanso, SalidaReal))/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
            WHEN HoraInicioDescanso IS NOT NULL AND HoraFinDescanso IS NOT NULL THEN
                CAST(CAST((DATEDIFF(MINUTE, 
                    CASE WHEN CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) < HoraInicioDescanso 
                         THEN EntradaReal 
                         ELSE DATEADD(DAY, -1, EntradaReal) END,
                    DATEADD(DAY, CASE WHEN CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) < HoraInicioDescanso THEN 0 ELSE 1 END, 
                           DATEADD(SECOND, DATEDIFF(SECOND, 0, HoraInicioDescanso), CAST(CAST(EntradaReal AS DATE) AS DATETIME)))) +
                      DATEDIFF(MINUTE, 
                    DATEADD(SECOND, DATEDIFF(SECOND, 0, HoraFinDescanso), CAST(CAST(EntradaReal AS DATE) AS DATETIME)),
                    CASE WHEN CAST(ISNULL(SalidaReal, '23:59:59') AS TIME) > HoraFinDescanso 
                         THEN SalidaReal 
                         ELSE DATEADD(DAY, 1, SalidaReal) END))/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
            ELSE
                CAST(CAST(DATEDIFF(MINUTE, ISNULL(EntradaReal, '00:00:00'), ISNULL(SalidaReal, '23:59:59'))/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
        END AS TotalTrabajado,

        -- Sobretiempo (asumiendo 8 horas como jornada estándar)
        CASE
            WHEN SalidaDescanso IS NOT NULL AND ReingresoDescanso IS NOT NULL THEN
                CASE WHEN (DATEDIFF(MINUTE, EntradaReal, SalidaDescanso) + 
                          DATEDIFF(MINUTE, ReingresoDescanso, SalidaReal)) > 480 THEN
                    CAST(CAST(((DATEDIFF(MINUTE, EntradaReal, SalidaDescanso) + 
                           DATEDIFF(MINUTE, ReingresoDescanso, SalidaReal)) - 480)/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
                ELSE '0 hrs' END
            WHEN HoraInicioDescanso IS NOT NULL AND HoraFinDescanso IS NOT NULL THEN
                CASE WHEN (DATEDIFF(MINUTE, 
                    CASE WHEN CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) < HoraInicioDescanso 
                         THEN EntradaReal 
                         ELSE DATEADD(DAY, -1, EntradaReal) END,
                    DATEADD(DAY, CASE WHEN CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) < HoraInicioDescanso THEN 0 ELSE 1 END, 
                           DATEADD(SECOND, DATEDIFF(SECOND, 0, HoraInicioDescanso), CAST(CAST(EntradaReal AS DATE) AS DATETIME)))) +
                      DATEDIFF(MINUTE, 
                    DATEADD(SECOND, DATEDIFF(SECOND, 0, HoraFinDescanso), CAST(CAST(EntradaReal AS DATE) AS DATETIME)),
                    CASE WHEN CAST(ISNULL(SalidaReal, '23:59:59') AS TIME) > HoraFinDescanso 
                         THEN SalidaReal 
                         ELSE DATEADD(DAY, 1, SalidaReal) END)) > 480 THEN
                    CAST(CAST(((DATEDIFF(MINUTE, 
                        CASE WHEN CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) < HoraInicioDescanso 
                             THEN EntradaReal 
                             ELSE DATEADD(DAY, -1, EntradaReal) END,
                        DATEADD(DAY, CASE WHEN CAST(ISNULL(EntradaReal, '00:00:00') AS TIME) < HoraInicioDescanso THEN 0 ELSE 1 END, 
                               DATEADD(SECOND, DATEDIFF(SECOND, 0, HoraInicioDescanso), CAST(CAST(EntradaReal AS DATE) AS DATETIME)))) +
                          DATEDIFF(MINUTE, 
                        DATEADD(SECOND, DATEDIFF(SECOND, 0, HoraFinDescanso), CAST(CAST(EntradaReal AS DATE) AS DATETIME)),
                        CASE WHEN CAST(ISNULL(SalidaReal, '23:59:59') AS TIME) > HoraFinDescanso 
                             THEN SalidaReal 
                             ELSE DATEADD(DAY, 1, SalidaReal) END)) - 480)/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
                ELSE '0 hrs' END
            ELSE
                CASE WHEN DATEDIFF(MINUTE, ISNULL(EntradaReal, '00:00:00'), ISNULL(SalidaReal, '23:59:59')) > 480 THEN
                    CAST(CAST((DATEDIFF(MINUTE, ISNULL(EntradaReal, '00:00:00'), ISNULL(SalidaReal, '23:59:59')) - 480)/60.0 AS DECIMAL(10,2)) AS VARCHAR(20)) + ' hrs'
                ELSE '0 hrs' END
        END AS Sobretiempo
    FROM JornadasAgrupadas
    WHERE EntradaReal IS NOT NULL AND SalidaReal IS NOT NULL;

    -- Retornar resultados en el formato solicitado
    SELECT
        Fecha,
        DiaSemana,
        Cargo,
        NombreTrabajador,
        Cedula,
        Jornada,
        InicioJornada AS [1eraJornada.inicio],
        InicioReceso AS [1eraJornada.inicioreceso],
        Total1raJornada AS [1eraJornada.total],
        FinReceso AS [2daJornada.finreceso],
        FinJornada AS [2daJornada.final],
        Total2daJornada AS [2daJornada.total],
        TotalTrabajado AS [horas.TiempoTotalTrabajado],
        Sobretiempo AS [horas.Sobretiempo]
    FROM #Resultados
    ORDER BY Fecha, NombreTrabajador;

    DROP TABLE #Resultados;
END;
/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */