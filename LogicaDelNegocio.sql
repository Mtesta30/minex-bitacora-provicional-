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

    -- Validar usuario y obtener su ID
    EXEC [dbo].[GET_UsuarioBiometrico] -- GET_UsuarioBiometrico
        @Identificacion, 
        @NombreCompleto, 
        @idUsuario OUTPUT;

    -- Validar biométrico y obtener su ID
    EXEC [dbo].[GET_Biometrico] -- GET_Biometrico
        @identificadorBiometrico, 
        @idBiometrico OUTPUT;

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
            EXEC [dbo].[GET_TipoMarcacion] -- GET_TipoMarcacion
                @Identificacion,
                @FechaHora,
                @idBiometrico,
                @horaInicioTurno,
                @horaFinTurno,
                @cruzaDia,
                @fechaTurno,
                @fechaFinTurno,
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
/* Procedimiento para procesar marcaciones biométricas */
/* -------------------------------------------------- */
CREATE PROCEDURE [dbo].[ProcesarMarcacionBiometrica]
    @identificacion VARCHAR(15),
    @fechaHora DATETIME,
    @idBiometrico UNIQUEIDENTIFIER,
    @tipoMarcacion VARCHAR(20) OUTPUT,
    @observacion VARCHAR(255) OUTPUT
AS
BEGIN
    DECLARE @fechaActual DATE = CAST(@fechaHora AS DATE)
    DECLARE @horaActual TIME = CAST(@fechaHora AS TIME)
    DECLARE @idUsuario UNIQUEIDENTIFIER
    DECLARE @toleranciaMinutos INT = 30
    -- Configurable

    -- Inicializar variables con valores por defecto
    SET @tipoMarcacion = 'Sin Definir'
    SET @observacion = NULL

    -- Obtener idUsuario
    SELECT @idUsuario = idUsuario
    FROM [dbo].[vUsuariosAppBiometrico]
    WHERE Identificacion = @identificacion

    -- Verificar si es primera marcación del día
    IF NOT EXISTS (
        SELECT 1
    FROM Bitacora
    WHERE Identificacion = @identificacion
        AND CAST(FechaHora AS DATE) = @fechaActual
    )
    BEGIN
        -- Verificar contra el turno programado
        DECLARE @horaInicioTurno TIME, @horaFinTurno TIME

        SELECT TOP 1
            @horaInicioTurno = T.horaInicio, @horaFinTurno = T.horaFin
        FROM ProgramacionTurnosDetalle PD
            INNER JOIN ProgramacionTurnos P ON PD.idProgramacion = P.idProgramacion
            INNER JOIN Turnos T ON PD.idTurno = T.idTurno
        WHERE P.idUsuario = @idUsuario
            AND PD.fecha = @fechaActual
            AND P.activo = 1
        ORDER BY P.fechaRegistro DESC

        IF @horaInicioTurno IS NOT NULL
        BEGIN
            -- Determinar si está dentro del rango de inicio de turno
            IF DATEDIFF(MINUTE, @horaInicioTurno, @horaActual) BETWEEN -@toleranciaMinutos AND 120
            BEGIN
                SET @tipoMarcacion = 'Entrada'

                -- Registrar llegada tardía si aplica
                IF @horaActual > @horaInicioTurno
                    SET @observacion = 'Llegada tardía: ' + CAST(DATEDIFF(MINUTE, @horaInicioTurno, @horaActual) AS VARCHAR) + ' minutos'
            END
            ELSE IF DATEDIFF(MINUTE, @horaFinTurno, @horaActual) BETWEEN -120 AND @toleranciaMinutos
            BEGIN
                SET @tipoMarcacion = 'Salida'

                -- Registrar salida anticipada si aplica
                IF @horaActual < @horaFinTurno
                    SET @observacion = 'Salida anticipada: ' + CAST(DATEDIFF(MINUTE, @horaActual, @horaFinTurno) AS VARCHAR) + ' minutos'
            END
            ELSE
            BEGIN
                SET @tipoMarcacion = 'Fuera de turno'
                SET @observacion = 'Marcación fuera del horario programado'
            END
        END
        ELSE
        BEGIN
            -- No hay turno programado, asumir entrada
            SET @tipoMarcacion = 'Entrada'
            SET @observacion = 'Sin turno programado'
        END
    END
    ELSE
    BEGIN
        -- Ya hay marcaciones previas ese día
        DECLARE @ultimaMarcacion DATETIME
        DECLARE @tipoUltimaMarcacion VARCHAR(20)

        SELECT TOP 1
            @ultimaMarcacion = FechaHora, @tipoUltimaMarcacion = tipoMarcacion
        FROM Bitacora
        WHERE Identificacion = @identificacion
            AND CAST(FechaHora AS DATE) = @fechaActual
        ORDER BY FechaHora DESC

        IF DATEDIFF(MINUTE, @ultimaMarcacion, @fechaHora) >= @toleranciaMinutos
        BEGIN
            -- Alternar entre entrada y salida
            SET @tipoMarcacion = CASE 
                     WHEN @tipoUltimaMarcacion = 'Entrada' THEN 'Salida' 
                     WHEN @tipoUltimaMarcacion = 'Salida' THEN 'Entrada'
                     WHEN @tipoUltimaMarcacion IS NULL THEN 'Entrada'
                     ELSE 'Entrada' 
                     END
        END
        ELSE
        BEGIN
            SET @tipoMarcacion = 'Duplicada'
            SET @observacion = 'Marcación duplicada dentro de ' + CAST(@toleranciaMinutos AS VARCHAR) + ' minutos'
        END
    END

    -- Garantizar que nunca se devuelva NULL
    IF @tipoMarcacion IS NULL
    BEGIN
        SET @tipoMarcacion = 'Sin Definir'
        SET @observacion = ISNULL(@observacion, '') + ' - Tipo de marcación indeterminado'
    END
END;

/* -------------------------------------------------- */
/* Procedimiento para obtener el tipo de marcacion    */
/* -------------------------------------------------- */
CREATE OR ALTER PROCEDURE [dbo].[GET_TipoMarcacion]
    @identificacion VARCHAR(15),
    @fechaHora DATETIME,
    @idBiometrico UNIQUEIDENTIFIER,
    @horaInicioTurno TIME = NULL,
    @horaFinTurno TIME = NULL,
    @cruzaDia BIT = 0,
    @fechaTurno DATE = NULL,
    @fechaFinTurno DATE = NULL,
    @tipoMarcacion VARCHAR(20) OUTPUT,
    @observacion VARCHAR(255) OUTPUT
AS
BEGIN
    DECLARE @fechaActual DATE = CAST(@fechaHora AS DATE);
    DECLARE @horaActual TIME = CAST(@fechaHora AS TIME);
    DECLARE @toleranciaMinutos INT = 30;
    -- Configurable
    DECLARE @margenEntradaMinutos INT = 120;
    -- Nueva variable configurable para el tiempo de entrada
    DECLARE @margenSalidaMinutos INT = 120;
    -- Nueva variable configurable para el tiempo de salida

    -- Inicializar variables
    SET @tipoMarcacion = NULL;
    SET @observacion = NULL;

    -- Si no hay información de turno (todos los parámetros son NULL)
    IF @horaInicioTurno IS NULL AND @horaFinTurno IS NULL
    BEGIN
        SET @tipoMarcacion = 'Sin turno asignado';
        SET @observacion = 'Marcación registrada sin turno programado';
        RETURN;
    END

    -- Obtener el total de marcaciones para este usuario en este día
    DECLARE @totalMarcacionesDia INT;
    SELECT @totalMarcacionesDia = COUNT(*)
    FROM [dbo].[Bitacora]
    WHERE 
        Identificacion = @identificacion
        AND CAST(FechaHora AS DATE) = @fechaActual;

    -- Obtener la posición de esta marcación en la secuencia diaria
    DECLARE @posicionMarcacion INT;
    SELECT @posicionMarcacion = COUNT(*)
    FROM [dbo].[Bitacora]
    WHERE 
        Identificacion = @identificacion
        AND CAST(FechaHora AS DATE) = @fechaActual
        AND FechaHora <= @fechaHora;

    -- Obtener secuencia anterior si existe
    DECLARE @tipoMarcacionAnterior VARCHAR(20) = NULL;
    SELECT TOP 1
        @tipoMarcacionAnterior = tipoMarcacion
    FROM [dbo].[Bitacora]
    WHERE 
        Identificacion = @identificacion
        AND CAST(FechaHora AS DATE) = @fechaActual
        AND FechaHora < @fechaHora
    ORDER BY FechaHora DESC;

    -- 1. Verificar si estamos en un turno que cruza días
    IF @cruzaDia = 1 
    BEGIN
        -- Si estamos en la fecha de fin del turno (segundo día)
        IF @fechaActual = @fechaFinTurno AND @fechaActual > @fechaTurno
        BEGIN
            -- Si es antes de la hora de fin, probablemente es una salida
            IF @horaActual <= @horaFinTurno
            BEGIN
                SET @tipoMarcacion = 'Salida';
                SET @observacion = 'Salida de turno nocturno';
            END
            -- Si es después de la hora de fin, podría ser una entrada para el siguiente turno
            ELSE
            BEGIN
                SET @tipoMarcacion = 'Entrada';
                SET @observacion = 'Posible entrada para turno posterior';
            END
        END
        -- Si estamos en la fecha de inicio del turno (primer día)
        ELSE IF @fechaActual = @fechaTurno
        BEGIN
            -- Si es cercano a la hora de inicio, es una entrada
            IF DATEDIFF(MINUTE, @horaInicioTurno, @horaActual) BETWEEN -@toleranciaMinutos AND @margenEntradaMinutos
            BEGIN
                SET @tipoMarcacion = 'Entrada';

                -- Registrar si llegó tarde
                IF @horaActual > @horaInicioTurno
                    SET @observacion = 'Llegada tardía: ' + CAST(DATEDIFF(MINUTE, @horaInicioTurno, @horaActual) AS VARCHAR) + ' minutos';
                ELSE
                    SET @observacion = 'Entrada a turno programado';
            END
            -- Si es muy tarde en el primer día, podría ser salida anticipada
            ELSE
            BEGIN
                SET @tipoMarcacion = 'Salida';
                SET @observacion = 'Posible salida anticipada de turno nocturno';
            END
        END
    END
    -- 2. Para turnos que no cruzan días
    ELSE
    BEGIN
        -- Primera marcación del día
        IF @posicionMarcacion = 1
        BEGIN
            -- Si está cerca del inicio del turno, es entrada
            IF DATEDIFF(MINUTE, @horaInicioTurno, @horaActual) BETWEEN -@toleranciaMinutos AND @margenEntradaMinutos
            BEGIN
                SET @tipoMarcacion = 'Entrada';

                -- Registrar si llegó tarde
                IF @horaActual > @horaInicioTurno
                    SET @observacion = 'Llegada tardía: ' + CAST(DATEDIFF(MINUTE, @horaInicioTurno, @horaActual) AS VARCHAR) + ' minutos';
                ELSE
                    SET @observacion = 'Entrada a turno programado';
            END
            -- Si está cerca del fin del turno pero es la primera marcación, algo raro pasa
            ELSE IF DATEDIFF(MINUTE, @horaFinTurno, @horaActual) BETWEEN -@margenSalidaMinutos AND @toleranciaMinutos
            BEGIN
                SET @tipoMarcacion = 'Salida';
                SET @observacion = 'Marcación única cerca del fin de turno';
            END
            ELSE
            BEGIN
                SET @tipoMarcacion = 'Entrada';
                SET @observacion = 'Entrada fuera de rango esperado';
            END
        END
        -- Segunda o posterior marcación del día
        ELSE
        BEGIN
            -- Si es la última marcación del día y está cerca del fin de turno
            IF @posicionMarcacion = @totalMarcacionesDia AND
                DATEDIFF(MINUTE, @horaActual, @horaFinTurno) BETWEEN -@margenSalidaMinutos AND @toleranciaMinutos
            BEGIN
                SET @tipoMarcacion = 'Salida';

                -- Registrar si salió antes
                IF @horaActual < @horaFinTurno
                    SET @observacion = 'Salida anticipada: ' + CAST(DATEDIFF(MINUTE, @horaActual, @horaFinTurno) AS VARCHAR) + ' minutos';
                ELSE
                    SET @observacion = 'Salida de turno programado';
            END
            -- Si no es la última pero el tipo anterior fue entrada, esta debe ser salida
            ELSE IF @tipoMarcacionAnterior = 'Entrada'
            BEGIN
                SET @tipoMarcacion = 'Salida';
                SET @observacion = 'Salida intermedia durante turno';
            END
            -- Si el tipo anterior fue salida, esta debe ser entrada
            ELSE IF @tipoMarcacionAnterior = 'Salida'
            BEGIN
                SET @tipoMarcacion = 'Entrada';
                SET @observacion = 'Reingreso durante turno';
            END
            -- Si no hay tipo anterior claro o es el caso de una duplicada
            ELSE
            BEGIN
                -- Verificar si está más cerca del inicio o del fin del turno
                IF ABS(DATEDIFF(MINUTE, @horaActual, @horaInicioTurno)) < ABS(DATEDIFF(MINUTE, @horaActual, @horaFinTurno))
                BEGIN
                    SET @tipoMarcacion = 'Entrada';
                    SET @observacion = 'Entrada determinada por cercanía a inicio de turno';
                END
                ELSE
                BEGIN
                    SET @tipoMarcacion = 'Salida';
                    SET @observacion = 'Salida determinada por cercanía a fin de turno';
                END
            END
        END
    END

END;
GO

/* -------------------------------------------------- */
/* Procedimiento para asociar marcaciones con turnos  */
/* programados  */
/* -------------------------------------------------- */
CREATE PROCEDURE [dbo].[AsociarMarcacionConTurno]
    @Identificacion VARCHAR(15),
    @FechaHora DATETIME,
    @idProgramacionDetalle UNIQUEIDENTIFIER OUTPUT,
    @idTurno UNIQUEIDENTIFIER OUTPUT
AS
BEGIN
    DECLARE @idUsuario UNIQUEIDENTIFIER;
    DECLARE @FechaMarcacion DATE = CAST(@FechaHora AS DATE);

    -- 1. Obtener el idUsuario a partir de la identificación usando la vista
    SELECT @idUsuario = idUsuario
    FROM [dbo].[vUsuariosAppBiometrico]
    WHERE Identificacion = @Identificacion;

    -- 2. Buscar la programación activa para esa fecha
    SELECT @idProgramacionDetalle = PD.idProgramacionDetalle,
        @idTurno = PD.idTurno
    FROM [dbo].[ProgramacionTurnos] PT
        INNER JOIN [dbo].[ProgramacionTurnosDetalle] PD
        ON PT.idProgramacion = PD.idProgramacion
    WHERE PT.idUsuario = @idUsuario
        AND PD.fecha = @FechaMarcacion
        AND PT.activo = 1
        AND PT.fechaInicio <= @FechaMarcacion
        AND PT.fechaFin >= @FechaMarcacion;

    -- 3. Si no se encuentra programación, asignar un ID genérico
    IF @idProgramacionDetalle IS NULL OR @idTurno IS NULL
    BEGIN
        SET @idProgramacionDetalle = '00000000-0000-0000-0000-000000000001';
        SET @idTurno = '00000000-0000-0000-0000-000000000001';
    END
END;


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
            -- La fecha de la marcación es igual a la fecha de inicio del turno
            (CAST(B.FechaHora AS DATE) = PD.fechaInicio)
        OR
        -- La fecha de la marcación es igual a la fecha de fin del turno (para turnos que cruzan días)
        (CAST(B.FechaHora AS DATE) = PD.fechaFin AND PD.fechaInicio < PD.fechaFin)
        );

    -- Declaración de variables para la actualización
    DECLARE @idBitacora UNIQUEIDENTIFIER;
    DECLARE @Identificacion VARCHAR(15);
    DECLARE @FechaHora DATETIME;
    DECLARE @idBiometrico UNIQUEIDENTIFIER;
    DECLARE @horaInicioTurno TIME;
    DECLARE @horaFinTurno TIME;
    DECLARE @cruzaDia BIT;
    DECLARE @fechaTurno DATE;
    DECLARE @fechaFinTurno DATE;
    DECLARE @tipoMarcacion VARCHAR(20);
    DECLARE @observacion VARCHAR(255);

    -- Cursor para procesar cada marcación individualmente
    DECLARE curMarcaciones CURSOR FOR 
        SELECT
        idBitacora,
        Identificacion,
        FechaHora,
        idBiometrico,
        horaInicioTurno,
        horaFinTurno,
        cruzaDia,
        fechaTurno,
        fechaFinTurno
    FROM #MarcacionesParaActualizar
    ORDER BY Identificacion, FechaHora;

    OPEN curMarcaciones;
    FETCH NEXT FROM curMarcaciones INTO 
        @idBitacora, @Identificacion, @FechaHora, @idBiometrico, 
        @horaInicioTurno, @horaFinTurno, @cruzaDia, @fechaTurno, @fechaFinTurno;

    WHILE @@FETCH_STATUS = 0
    BEGIN
        -- Llamar al nuevo procedimiento para determinar el tipo de marcación
        EXEC [dbo].[GET_TipoMarcacion]
            @Identificacion,
            @FechaHora,
            @idBiometrico,
            @horaInicioTurno,
            @horaFinTurno,
            @cruzaDia,
            @fechaTurno,
            @fechaFinTurno,
            @tipoMarcacion OUTPUT,
            @observacion OUTPUT;

        -- Actualizar la marcación con el tipo y la observación determinados
        UPDATE [dbo].[Bitacora]
        SET 
            tipoMarcacion = @tipoMarcacion,
            observacion = @observacion,
            -- Actualizar también la referencia al turno
            idProgramacionDetalle = (SELECT idProgramacionDetalle
        FROM #MarcacionesParaActualizar
        WHERE idBitacora = @idBitacora),
            idTurno = (SELECT idTurno
        FROM #MarcacionesParaActualizar
        WHERE idBitacora = @idBitacora)
        WHERE 
            idBitacora = @idBitacora;

        FETCH NEXT FROM curMarcaciones INTO 
            @idBitacora, @Identificacion, @FechaHora, @idBiometrico, 
            @horaInicioTurno, @horaFinTurno, @cruzaDia, @fechaTurno, @fechaFinTurno;
    END

    CLOSE curMarcaciones;
    DEALLOCATE curMarcaciones;

    -- Segunda pasada para corregir inconsistencias en la secuencia
    UPDATE B
    SET 
        tipoMarcacion = CASE 
            WHEN PrevMarcacion.tipoMarcacion = B.tipoMarcacion AND PrevMarcacion.tipoMarcacion = 'Entrada' THEN 'Salida'
            WHEN PrevMarcacion.tipoMarcacion = B.tipoMarcacion AND PrevMarcacion.tipoMarcacion = 'Salida' THEN 'Entrada'
            ELSE B.tipoMarcacion
        END,
        observacion = CASE
            WHEN PrevMarcacion.tipoMarcacion = B.tipoMarcacion THEN 'Corregido por secuencia inconsistente: ' + ISNULL(B.observacion, '')
            ELSE B.observacion
        END
    FROM [dbo].[Bitacora] B
    CROSS APPLY (
        SELECT TOP 1
            BB.tipoMarcacion
        FROM [dbo].[Bitacora] BB
        WHERE 
            BB.Identificacion = B.Identificacion
            AND CAST(BB.FechaHora AS DATE) = CAST(B.FechaHora AS DATE)
            AND BB.FechaHora < B.FechaHora
        ORDER BY BB.FechaHora DESC
    ) AS PrevMarcacion
        INNER JOIN #MarcacionesParaActualizar M ON B.idBitacora = M.idBitacora
    WHERE 
        PrevMarcacion.tipoMarcacion = B.tipoMarcacion;

    -- Limpiar tabla temporal
    DROP TABLE #MarcacionesParaActualizar;

    -- Retornar conteo de registros actualizados
    SELECT COUNT(*) AS RegistrosActualizados
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
END;
GO

/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */
CREATE OR ALTER PROCEDURE [dbo].[ActualizarMarcacionesSinTurno]
    @idUsuario UNIQUEIDENTIFIER,
    @fechaInicio DATE,
    @fechaFin DATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Crear una tabla temporal para almacenar las marcaciones actualizadas
    CREATE TABLE #MarcacionesActualizadas
    (
        idBitacora UNIQUEIDENTIFIER,
        Identificacion VARCHAR(15),
        FechaHora DATETIME,
        idBiometrico UNIQUEIDENTIFIER
    );

    -- Primero actualizamos los IDs de programación y turno
    -- y guardamos las marcaciones actualizadas en la tabla temporal
    UPDATE B
    SET B.idProgramacionDetalle = PD.idProgramacionDetalle,
        B.idTurno = PD.idTurno
    OUTPUT 
        INSERTED.idBitacora, 
        INSERTED.Identificacion,
        INSERTED.FechaHora,
        INSERTED.idBiometrico
    INTO #MarcacionesActualizadas
    FROM [dbo].[Bitacora] B
        INNER JOIN [dbo].[vUsuariosAppBiometrico] U ON B.Identificacion = U.Identificacion
        INNER JOIN [dbo].[ProgramacionTurnos] PT ON U.idUsuario = PT.idUsuario
        INNER JOIN [dbo].[ProgramacionTurnosDetalle] PD ON PT.idProgramacion = PD.idProgramacion
    WHERE (U.idUsuario = @idUsuario OR @idUsuario IS NULL)
        AND CAST(B.FechaHora AS DATE) BETWEEN @fechaInicio AND @fechaFin
        AND CAST(B.FechaHora AS DATE) = PD.fecha
        AND (B.idProgramacionDetalle IS NULL OR B.idProgramacionDetalle = '00000000-0000-0000-0000-000000000001'
        OR B.idTurno IS NULL OR B.idTurno = '00000000-0000-0000-0000-000000000001');

    -- Ahora procesamos cada marcación actualizada para recalcular su tipo
    DECLARE @idBitacora UNIQUEIDENTIFIER;
    DECLARE @Identificacion VARCHAR(15);
    DECLARE @FechaHora DATETIME;
    DECLARE @idBiometrico UNIQUEIDENTIFIER;
    DECLARE @tipoMarcacion VARCHAR(20);
    DECLARE @observacion VARCHAR(255);

    -- Cursor para recorrer las marcaciones actualizadas
    DECLARE curMarcaciones CURSOR FOR 
        SELECT idBitacora, Identificacion, FechaHora, idBiometrico
    FROM #MarcacionesActualizadas;

    OPEN curMarcaciones;
    FETCH NEXT FROM curMarcaciones INTO @idBitacora, @Identificacion, @FechaHora, @idBiometrico;

    WHILE @@FETCH_STATUS = 0
    BEGIN
        -- Llamar al procedimiento existente para determinar el tipo de marcación
        EXEC [dbo].[ProcesarMarcacionBiometrica]
            @identificacion = @Identificacion,
            @fechaHora = @FechaHora,
            @idBiometrico = @idBiometrico,
            @tipoMarcacion = @tipoMarcacion OUTPUT,
            @observacion = @observacion OUTPUT;

        -- Actualizar la marcación con el tipo recalculado
        UPDATE [dbo].[Bitacora]
        SET tipoMarcacion = @tipoMarcacion,
            observacion = @observacion
        WHERE idBitacora = @idBitacora;

        FETCH NEXT FROM curMarcaciones INTO @idBitacora, @Identificacion, @FechaHora, @idBiometrico;
    END

    CLOSE curMarcaciones;
    DEALLOCATE curMarcaciones;

    -- Eliminar la tabla temporal
    DROP TABLE #MarcacionesActualizadas;
END;

/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */

-- Script para reprocesar las programaciones de turnos existentes
-- y actualizar las marcaciones correctamente

-- 1. Primero, desactivamos temporalmente las programaciones existentes
-- (en vez de borrarlas, las desactivamos por seguridad)
BEGIN TRANSACTION;

BEGIN TRY
    -- Crear tabla temporal para almacenar las programaciones actuales
    CREATE TABLE #ProgramacionesParaReprocesar
(
    idProgramacion UNIQUEIDENTIFIER,
    idUsuario UNIQUEIDENTIFIER,
    idCentroTrabajo UNIQUEIDENTIFIER,
    fechaInicio DATE,
    fechaFin DATE,
    idUsuarioRegistra UNIQUEIDENTIFIER,
    fechaRegistro DATETIME
);

    -- Crear tabla temporal para almacenar los detalles por día de la semana
    CREATE TABLE #DetallesPorDia
(
    idProgramacion UNIQUEIDENTIFIER,
    diaSemana INT,
    idTurno UNIQUEIDENTIFIER
);

    -- 2. Obtener todas las programaciones activas
    INSERT INTO #ProgramacionesParaReprocesar
SELECT
    idProgramacion,
    idUsuario,
    idCentroTrabajo,
    fechaInicio,
    fechaFin,
    idUsuarioRegistra,
    fechaRegistro
FROM ProgramacionTurnos
WHERE activo = 1;

    -- 3. Obtener los detalles de programación agrupados por día de la semana
    INSERT INTO #DetallesPorDia
SELECT DISTINCT
    PD.idProgramacion,
    DATEPART(WEEKDAY, PD.fechaInicio) AS diaSemana,
    PD.idTurno
FROM ProgramacionTurnosDetalle PD
    INNER JOIN #ProgramacionesParaReprocesar PR ON PD.idProgramacion = PR.idProgramacion;

    -- 4. Desactivar las programaciones actuales
    UPDATE ProgramacionTurnos
    SET activo = 0
    WHERE idProgramacion IN (SELECT idProgramacion
FROM #ProgramacionesParaReprocesar);

    -- 5. Variables para el cursor
    DECLARE @idProgramacion UNIQUEIDENTIFIER;
    DECLARE @idUsuario UNIQUEIDENTIFIER;
    DECLARE @idCentroTrabajo UNIQUEIDENTIFIER;
    DECLARE @fechaInicio DATE;
    DECLARE @fechaFin DATE;
    DECLARE @idUsuarioRegistra UNIQUEIDENTIFIER;
    DECLARE @idTurnoLunes UNIQUEIDENTIFIER;
    DECLARE @idTurnoMartes UNIQUEIDENTIFIER;
    DECLARE @idTurnoMiercoles UNIQUEIDENTIFIER;
    DECLARE @idTurnoJueves UNIQUEIDENTIFIER;
    DECLARE @idTurnoViernes UNIQUEIDENTIFIER;
    DECLARE @idTurnoSabado UNIQUEIDENTIFIER;
    DECLARE @idTurnoDomingo UNIQUEIDENTIFIER;
    DECLARE @idTurnoDefault UNIQUEIDENTIFIER;

    -- 6. Cursor para procesar cada programación
    DECLARE curProgramaciones CURSOR FOR
    SELECT
    idProgramacion,
    idUsuario,
    idCentroTrabajo,
    fechaInicio,
    fechaFin,
    idUsuarioRegistra
FROM #ProgramacionesParaReprocesar;

    OPEN curProgramaciones;
    FETCH NEXT FROM curProgramaciones INTO 
        @idProgramacion, @idUsuario, @idCentroTrabajo, @fechaInicio, @fechaFin, @idUsuarioRegistra;

    WHILE @@FETCH_STATUS = 0
    BEGIN
    -- Inicializar variables de turnos
    SET @idTurnoLunes = NULL;
    SET @idTurnoMartes = NULL;
    SET @idTurnoMiercoles = NULL;
    SET @idTurnoJueves = NULL;
    SET @idTurnoViernes = NULL;
    SET @idTurnoSabado = NULL;
    SET @idTurnoDomingo = NULL;
    SET @idTurnoDefault = NULL;

    -- Obtener los turnos para cada día de la semana
    SELECT @idTurnoDomingo = idTurno
    FROM #DetallesPorDia
    WHERE idProgramacion = @idProgramacion AND diaSemana = 1;
    SELECT @idTurnoLunes = idTurno
    FROM #DetallesPorDia
    WHERE idProgramacion = @idProgramacion AND diaSemana = 2;
    SELECT @idTurnoMartes = idTurno
    FROM #DetallesPorDia
    WHERE idProgramacion = @idProgramacion AND diaSemana = 3;
    SELECT @idTurnoMiercoles = idTurno
    FROM #DetallesPorDia
    WHERE idProgramacion = @idProgramacion AND diaSemana = 4;
    SELECT @idTurnoJueves = idTurno
    FROM #DetallesPorDia
    WHERE idProgramacion = @idProgramacion AND diaSemana = 5;
    SELECT @idTurnoViernes = idTurno
    FROM #DetallesPorDia
    WHERE idProgramacion = @idProgramacion AND diaSemana = 6;
    SELECT @idTurnoSabado = idTurno
    FROM #DetallesPorDia
    WHERE idProgramacion = @idProgramacion AND diaSemana = 7;

    -- Si hay un solo turno igual para todos los días, usarlo como default
    IF (SELECT COUNT(DISTINCT idTurno)
    FROM #DetallesPorDia
    WHERE idProgramacion = @idProgramacion) = 1
        BEGIN
        SELECT TOP 1
            @idTurnoDefault = idTurno
        FROM #DetallesPorDia
        WHERE idProgramacion = @idProgramacion;

        -- Limpiar los valores individuales si usamos default
        SET @idTurnoLunes = NULL;
        SET @idTurnoMartes = NULL;
        SET @idTurnoMiercoles = NULL;
        SET @idTurnoJueves = NULL;
        SET @idTurnoViernes = NULL;
        SET @idTurnoSabado = NULL;
        SET @idTurnoDomingo = NULL;
    END

    -- Llamar al procedimiento SAVE_ProgramacionTurnos
    EXEC [dbo].[SAVE_ProgramacionTurnos]
            @idUsuario = @idUsuario,
            @idCentroTrabajo = @idCentroTrabajo,
            @fechaInicio = @fechaInicio,
            @fechaFin = @fechaFin,
            @idUsuarioRegistra = @idUsuarioRegistra,
            @idTurnoLunes = @idTurnoLunes,
            @idTurnoMartes = @idTurnoMartes,
            @idTurnoMiercoles = @idTurnoMiercoles,
            @idTurnoJueves = @idTurnoJueves,
            @idTurnoViernes = @idTurnoViernes,
            @idTurnoSabado = @idTurnoSabado,
            @idTurnoDomingo = @idTurnoDomingo,
            @idTurnoDefault = @idTurnoDefault;

    FETCH NEXT FROM curProgramaciones INTO 
            @idProgramacion, @idUsuario, @idCentroTrabajo, @fechaInicio, @fechaFin, @idUsuarioRegistra;
END

    CLOSE curProgramaciones;
    DEALLOCATE curProgramaciones;

    -- 7. Opcional: Actualizar marcaciones de períodos anteriores que quedaron sin procesar
    -- Esto actualizará las marcaciones históricas que no se procesaron correctamente la primera vez
    DECLARE @fechaInicioHistorico DATE = '2024-02-01';  -- Ajustar según necesidad
    DECLARE @fechaFinHistorico DATE = GETDATE();

    -- Recorrer cada usuario y actualizar sus marcaciones históricas
    DECLARE curUsuarios CURSOR FOR
    SELECT DISTINCT idUsuario
FROM ProgramacionTurnos
WHERE activo = 1;

    OPEN curUsuarios;
    FETCH NEXT FROM curUsuarios INTO @idUsuario;

    WHILE @@FETCH_STATUS = 0
    BEGIN
    EXEC [dbo].[ActualizarMarcacionesConTurnoAsignado] 
            @idUsuario = @idUsuario, 
            @fechaInicio = @fechaInicioHistorico, 
            @fechaFin = @fechaFinHistorico;

    FETCH NEXT FROM curUsuarios INTO @idUsuario;
END

    CLOSE curUsuarios;
    DEALLOCATE curUsuarios;

    -- 8. Limpieza de tablas temporales
    DROP TABLE #ProgramacionesParaReprocesar;
    DROP TABLE #DetallesPorDia;

    COMMIT TRANSACTION;
    
    -- Informe final
    PRINT 'Proceso completado exitosamente.';
    
    -- Mostrar estadísticas
                SELECT
        'Programaciones reprocesadas' AS Concepto,
        COUNT(*) AS Cantidad
    FROM ProgramacionTurnos
    WHERE activo = 1

UNION ALL

    SELECT
        'Marcaciones actualizadas' AS Concepto,
        COUNT(*) AS Cantidad
    FROM Bitacora
    WHERE idProgramacionDetalle <> '00000000-0000-0000-0000-000000000001'
        AND idTurno <> '00000000-0000-0000-0000-000000000001';

END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;
    
    DECLARE @ErrorMessage NVARCHAR(4000);
    DECLARE @ErrorSeverity INT;
    DECLARE @ErrorState INT;

    SELECT
    @ErrorMessage = ERROR_MESSAGE(),
    @ErrorSeverity = ERROR_SEVERITY(),
    @ErrorState = ERROR_STATE();

    RAISERROR (@ErrorMessage, @ErrorSeverity, @ErrorState);
END CATCH;

-- Consulta para verificar los resultados
/*
SELECT 
    U.NombreCompleto,
    PT.fechaInicio,
    PT.fechaFin,
    COUNT(PTD.idProgramacionDetalle) as TotalDiasProgramados,
    COUNT(DISTINCT B.idBitacora) as TotalMarcaciones,
    SUM(CASE WHEN B.idProgramacionDetalle IS NOT NULL AND B.idProgramacionDetalle <> '00000000-0000-0000-0000-000000000001' THEN 1 ELSE 0 END) as MarcacionesConTurno
FROM ProgramacionTurnos PT
INNER JOIN UsuariosBiometrico U ON PT.idUsuario = U.idUsuario
LEFT JOIN ProgramacionTurnosDetalle PTD ON PT.idProgramacion = PTD.idProgramacion
LEFT JOIN Bitacora B ON U.Identificacion = B.Identificacion 
    AND CAST(B.FechaHora AS DATE) BETWEEN PT.fechaInicio AND PT.fechaFin
GROUP BY U.NombreCompleto, PT.fechaInicio, PT.fechaFin
ORDER BY U.NombreCompleto, PT.fechaInicio;
*/