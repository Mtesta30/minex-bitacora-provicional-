/* Tabla Usuarios biometrico */
CREATE TABLE [dbo].[UsuariosBiometrico]
(
    [idUsuario] [UNIQUEIDENTIFIER] NOT NULL,
    [TipoDocumento] [VARCHAR](10) NULL,
    [Identificacion] [VARCHAR](15) NOT NULL,
    [NombreCompleto] [VARCHAR](100) NOT NULL,
    [Identificador] [VARCHAR](50) NULL

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
/* Vista de todos los Usuarios de las tablas UsuariosBiometrico y UsuariosDetalle */
/* -------------------------------------------------- */
CREATE VIEW [dbo].[vUsuariosAppBiometrico]
AS
            SELECT
            ub.idUsuario,
            ub.NombreCompleto,
            ub.Identificacion,
            'Biométrico' AS Origen
        FROM
            [dbo].[UsuariosBiometrico] ub
    UNION ALL
        SELECT
            ud.idUsuario,
            ud.NombreCompleto,
            ud.Identificacion,
            'Trazapp' AS Origen
        FROM
            [dbo].[UsuariosDetalle] ud
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
/* Procedimientos */
/* -------------------------------------------------- */

-- Procedimiento para validar usuarios
CREATE PROCEDURE [dbo].[ValidarUsuarioBiometrico]
    @identificacion VARCHAR(15),
    @nombreCompleto VARCHAR(100)
AS
BEGIN
    DECLARE @idUsuario UNIQUEIDENTIFIER;

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
-- Éxito
END;
GO

-- Procedimiento para validar dispositivos biométricos
CREATE PROCEDURE [dbo].[ValidarBiometrico]
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
            ('BiometricoNoRegistrado', @identificadorBiometrico, GETDATE());
    END

    RETURN 0;
END;

/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */

/* 
CREATE PROCEDURE [dbo].[ProcesarMarcacionBiometrica]
    @identificacion VARCHAR(15),
    @fechaHora DATETIME,
    @idBiometrico UNIQUEIDENTIFIER,
    @tipoMarcacion VARCHAR(20) OUTPUT
AS
BEGIN
    DECLARE @fechaActual DATE = CAST(@fechaHora AS DATE)
    DECLARE @fechaHoraActual TIME = CAST(@fechaHora AS TIME)
    DECLARE @idUsuario UNIQUEIDENTIFIER

    -- Obtener idUsuario
    SELECT @idUsuario = idUsuario
    FROM [dbo].[vUsuariosAppBiometrico]
    WHERE Identificacion = @identificacion

    -- Para una nueva marcación:
    IF EXISTS (SELECT 1
    FROM Bitacora
    WHERE Identificacion = @identificacion
        AND CAST(FechaHora AS DATE) = @fechaActual)
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

        -- Si pasaron al menos 30 minutos desde la última marcación
        IF DATEDIFF(MINUTE, @ultimaMarcacion, @fechaHoraActual) >= 30
        BEGIN
            -- Si la última fue entrada, esta es salida y viceversa
            SET @tipoMarcacion = CASE WHEN @tipoUltimaMarcacion = 'Entrada' THEN 'Salida' ELSE 'Entrada' END
        END
        ELSE
        BEGIN
            -- Marcación muy cercana a la anterior, posible error o paso casual
            SET @tipoMarcacion = 'Indeterminado'
        -- Opcionalmente, registrar en log de errores para revisión manual
        END
    END
    ELSE
    BEGIN
        -- Primera marcación del día
        DECLARE @horaInicioTurno TIME

        -- Obtener horario programado
        SELECT @horaInicioTurno = T.horaInicio
        FROM ProgramacionTurnosDetalle PD
            INNER JOIN ProgramacionTurnos P ON PD.idProgramacion = P.idProgramacion
            INNER JOIN Turnos T ON PD.idTurno = T.idTurno
        WHERE P.idUsuario = @idUsuario
            AND PD.fecha = @fechaActual

        -- Si está cerca del inicio de turno (± 2 horas) es entrada, si no podría ser salida de turno anterior
        IF ABS(DATEDIFF(MINUTE, @horaInicioTurno, @fechaHoraActual)) <= 120
        BEGIN
            SET @tipoMarcacion = 'Entrada'
        END
        ELSE
        BEGIN
            -- Marcación lejos del inicio de turno, posible salida o error
            SET @tipoMarcacion = 'Verificar'
        -- Marcar para revisión manual
        END
    END
END;
 */

/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* 
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
END; */

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

    CONSTRAINT [PK_Bitacora]
PRIMARY KEY CLUSTERED
(
        [idBitacora] ASC
    )
WITH
(PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO

-- Agregar clave foránea a la tabla Biometricos
ALTER TABLE [dbo].[Bitacora] WITH CHECK ADD CONSTRAINT [FK_Bitacora_Biometricos] 
FOREIGN KEY([idBiometrico])
REFERENCES [dbo].[Biometricos] ([idBiometrico])
GO

-- Procedure principal que orquesta las validaciones
CREATE PROCEDURE [dbo].[SAVE_Bitacora]
    @Identificacion VARCHAR(15),
    @NombreCompleto VARCHAR(100),
    @identificadorBiometrico VARCHAR(50),
    @FechaHora DATETIME
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @idBiometrico UNIQUEIDENTIFIER;
    DECLARE @tipoMarcacion VARCHAR(20);
    DECLARE @idProgramacionDetalle UNIQUEIDENTIFIER;
    DECLARE @idTurno UNIQUEIDENTIFIER;

    -- Validar usuario
    EXEC [dbo].[ValidarUsuarioBiometrico] @Identificacion, @NombreCompleto;

    -- Validar biométrico y obtener su ID
    EXEC [dbo].[ValidarBiometrico] @identificadorBiometrico, @idBiometrico OUTPUT;

    -- Insertar registro si el biométrico existe
    IF @idBiometrico IS NOT NULL
    BEGIN
        -- 1. Procesar la marcación para determinar el tipo (entrada/salida)
        EXEC [dbo].[ProcesarMarcacionBiometrica]
            @Identificacion,
            @FechaHora,
            @idBiometrico,
            @tipoMarcacion OUTPUT;

        -- 2. Asociar la marcación con el turno programado
        EXEC [dbo].[AsociarMarcacionConTurno]
            @Identificacion,
            @FechaHora,
            @idProgramacionDetalle OUTPUT,
            @idTurno OUTPUT;

        -- 3. Insertar el registro en la bitácora con toda la información
        INSERT INTO [dbo].[Bitacora]
            (
            idBitacora,
            idBiometrico,
            Identificacion,
            NombreCompleto,
            FechaHora,
            tipoMarcacion,
            idProgramacionDetalle,
            idTurno
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
                @idTurno
            );
    END
END;
/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */

CREATE TABLE [dbo].[Turnos]
(
    [idTurno] [UNIQUEIDENTIFIER] NOT NULL,
    [descripcion] [VARCHAR](100) NOT NULL,
    [horaInicio] [TIME](7) NOT NULL,
    [horaFin] [TIME](7) NOT NULL,
    [duracionHoras] [DECIMAL](5,2) NOT NULL,
    [activo] [BIT] NOT NULL DEFAULT 1,

    CONSTRAINT [PK_Turnos] PRIMARY KEY CLUSTERED ([idTurno] ASC)
)


/* -------------------------------------------------- */
/* -------------------------------------------------- */
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
    CONSTRAINT [FK_ProgramacionTurnos_UsuariosBiometrico] FOREIGN KEY ([idUsuario]) 
        REFERENCES [dbo].[UsuariosBiometrico] ([idUsuario]),
    CONSTRAINT [FK_ProgramacionTurnos_Destino] FOREIGN KEY ([idCentroTrabajo]) 
        REFERENCES [dbo].[Destino] ([idDestino])
)

/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */


CREATE TABLE [dbo].[ProgramacionTurnosDetalle]
(
    [idProgramacionDetalle] [UNIQUEIDENTIFIER] NOT NULL,
    [idProgramacion] [UNIQUEIDENTIFIER] NOT NULL,
    [fecha] [DATE] NOT NULL,
    [idTurno] [UNIQUEIDENTIFIER] NOT NULL,

    CONSTRAINT [PK_ProgramacionTurnosDetalle] PRIMARY KEY CLUSTERED ([idProgramacionDetalle] ASC),
    CONSTRAINT [FK_ProgramacionTurnosDetalle_ProgramacionTurnos] FOREIGN KEY ([idProgramacion]) 
        REFERENCES [dbo].[ProgramacionTurnos] ([idProgramacion]),
    CONSTRAINT [FK_ProgramacionTurnosDetalle_Turnos] FOREIGN KEY ([idTurno]) 
        REFERENCES [dbo].[Turnos] ([idTurno])
)

/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* 
CREATE PROCEDURE [dbo].[InsertarProgramacionTurnos]
    @idUsuario UNIQUEIDENTIFIER,
    @idCentroTrabajo UNIQUEIDENTIFIER,
    @fechaInicio DATE,
    @fechaFin DATE,
    @observaciones VARCHAR(500) = NULL,
    @idUsuarioRegistra UNIQUEIDENTIFIER,
    @idTurnoLunes UNIQUEIDENTIFIER = NULL,
    @idTurnoMartes UNIQUEIDENTIFIER = NULL,
    @idTurnoMiercoles UNIQUEIDENTIFIER = NULL,
    @idTurnoJueves UNIQUEIDENTIFIER = NULL,
    @idTurnoViernes UNIQUEIDENTIFIER = NULL,
    @idTurnoSabado UNIQUEIDENTIFIER = NULL,
    @idTurnoDomingo UNIQUEIDENTIFIER = NULL,
    @idTurnoDefault UNIQUEIDENTIFIER = NULL,
    -- Turno para usar si no se especifica para un día particular
    @idProgramacion UNIQUEIDENTIFIER OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    -- Validaciones básicas
    IF @fechaInicio > @fechaFin
    BEGIN
        RAISERROR('La fecha de inicio no puede ser posterior a la fecha de fin', 16, 1)
        RETURN -1
    END

    -- Si no se proporciona al menos un turno por día o un turno default, error
    IF @idTurnoDefault IS NULL AND @idTurnoLunes IS NULL AND @idTurnoMartes IS NULL
        AND @idTurnoMiercoles IS NULL AND @idTurnoJueves IS NULL AND @idTurnoViernes IS NULL
        AND @idTurnoSabado IS NULL AND @idTurnoDomingo IS NULL
    BEGIN
        RAISERROR('Debe especificar al menos un turno predeterminado o un turno para cada día', 16, 1)
        RETURN -2
    END

    -- Generar ID para la programación si no se proporciona
    IF @idProgramacion IS NULL
        SET @idProgramacion = NEWID();

    -- Iniciar transacción para asegurar consistencia
    BEGIN TRANSACTION;

    BEGIN TRY
        -- Insertar cabecera de programación
        INSERT INTO [dbo].[ProgramacionTurnos]
        (idProgramacion, idUsuario, idCentroTrabajo, fechaInicio, fechaFin,
        observaciones, fechaRegistro, idUsuarioRegistra)
    VALUES
        (@idProgramacion, @idUsuario, @idCentroTrabajo, @fechaInicio, @fechaFin,
            @observaciones, GETDATE(), @idUsuarioRegistra);
        
        -- Insertar detalles para cada día en el rango
        DECLARE @fechaActual DATE = @fechaInicio;
        DECLARE @diaSemana INT;
        DECLARE @idTurnoDia UNIQUEIDENTIFIER;
        
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
            INSERT INTO [dbo].[ProgramacionTurnosDetalle]
                (idProgramacionDetalle, idProgramacion, fecha, idTurno)
            VALUES
                (NEWID(), @idProgramacion, @fechaActual, @idTurnoDia);
        END

        -- Avanzar al siguiente día
        SET @fechaActual = DATEADD(DAY, 1, @fechaActual);
    END
        
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        
        DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
        DECLARE @ErrorSeverity INT = ERROR_SEVERITY();
        DECLARE @ErrorState INT = ERROR_STATE();
        
        RAISERROR(@ErrorMessage, @ErrorSeverity, @ErrorState);
        RETURN -100;
    END CATCH

    RETURN 0;
END */

/* -------------------------------------------------- */
/* -------------------------------------------------- */
/* -------------------------------------------------- */
SELECT TOP 10
    *
FROM Usuarios

SELECT TOP 10
    *
FROM UsuariosDetalle

SELECT *
FROM Destino
WHERE CentroCosto = 59 OR CentroCosto = 60
-- WHERE Descripcion LIKE '%di%'

SELECT TOP 10
    *
FROM logs

SELECT TOP 10
    *
FROM turnos_empleados

SELECT TOP 10
    *
FROM Bitacora_horarios

SELECT TOP 10
    *
FROM BitacoraTurnos