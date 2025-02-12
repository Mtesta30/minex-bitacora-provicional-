SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE PROCEDURE [dbo].[SAVE_JornadaBitacora]
    -- Add the parameters for the stored procedure here
    @idBitacora UNIQUEIDENTIFIER = NULL,
    @idUsuario UNIQUEIDENTIFIER,
    @idCentroTrabajo UNIQUEIDENTIFIER,
    @idActividad UNIQUEIDENTIFIER,
    @Descripcion VARCHAR(MAX),
    @idUsuario_Registra UNIQUEIDENTIFIER,
    @idUnidadNegocio UNIQUEIDENTIFIER,
    @tiquete NVARCHAR(12),
    --- viene en formato   año-consecutivo
    @horas MONEY,
    @minutos MONEY
AS
BEGIN
    --///////////  separar el año y consecutivo
    DECLARE @posicion INT;
    DECLARE @anno NVARCHAR(10);
    DECLARE @consecutivo NVARCHAR(10);
    SET @posicion = CHARINDEX('-', @tiquete);
    -- Encontrar la posición del delimitador '-'
    SET @anno = SUBSTRING(@tiquete, 1, @posicion - 1);
    -- Extraer la parte antes del delimitador
    SET @consecutivo = SUBSTRING(@tiquete, @posicion + 1, LEN(@tiquete) - @posicion);
    -- Extraer la parte después del delimitador

    DECLARE @año  INT = year(getdate())
    DECLARE @hora_gral INT
    DECLARE @minu_gral INT
    DECLARE @gral INT
    DECLARE @gral_teclado INT
    DECLARE @tt_gral DATETIME
    SELECT @tt_gral= traz.dbo.Buscar_horas_pendientes (@tiquete, @idUsuario)
    -- saca las horas totales del turno completo
    SELECT @hora_gral= DATEPART(hour, @tt_gral), @minu_gral= DATEPART(MINUTE, @tt_gral)
    -- lo francionamos en horas  minutos
    SET @gral = (@hora_gral*60)+(@minu_gral)
    -- lo convertimos a solo minutos
    SET @gral_teclado = (@horas*60)+(@minutos)
    -- convertirmos el tiempo de entrada del usuario a minutos

    IF(@gral_teclado<=@gral)  -- si el tiempo que ingresa el usuario es menor que del turno o el tiempo restante  pasa
	BEGIN
        DECLARE @fechaini  DATETIME
        DECLARE @fechafin  DATETIME
        DECLARE @identificacion INT

        SELECT @fechaini= FechaInicial, @fechafin= FechaFinal
        FROM Jornada_Bitacora
        WHERE idUsuario = @idUsuario AND Tiquete_Registro=@tiquete AND year(FechaRegistro)= @año
        ORDER BY FechaRegistro DESC

        SELECT @identificacion=Identificacion
        FROM UsuariosDetalle
        WHERE idUsuario =@idUsuario
        DECLARE @datetime  DATETIME
        DECLARE @datetime_nueva  DATETIME
        IF @fechaini IS NULL   -- si ya existe un registro en la tabla jornada bitacora
		BEGIN
            --select @identificacion
            --select /*top(1)*/ access_time, date_time, access_date from biometrico.dbo.bitacora where id=@identificacion AND consecutivo=@tiquete order by date_time 
            SELECT TOP(1)
                @datetime= date_time
            FROM biometrico.dbo.bitacora
            WHERE id=@identificacion AND consecutivo=@consecutivo AND año=@anno
            ORDER BY date_time
            --	select  @datetime
            SELECT @datetime_nueva= DATEADD(minute,@minutos, dateadd(HOUR, @horas, @datetime))
            --select @datetime,@datetime_nueva
            --	insertamos el nuevo registro
            INSERT INTO Jornada_Bitacora
                (id_Bitacora,idUsuario, idCentroTrabajo, idActividad, FechaInicial, FechaFinal, Descripcion, idusuarioRegistra, FechaRegistro, idUnidadNegocio, Tiquete_Registro)
            VALUES
                (NEWID(), @idUsuario, @idCentroTrabajo, @idActividad, @datetime, @datetime_nueva, @Descripcion, @idUsuario_Registra, getdate(), @idUnidadNegocio, @tiquete )
        --	select 'paso'

        END
		ELSE
		BEGIN
            SELECT @fechaini= FechaInicial, @fechafin= FechaFinal
            FROM Jornada_Bitacora
            WHERE idUsuario = @idUsuario AND Tiquete_Registro=@tiquete AND year(FechaRegistro)= @año
            ORDER BY FechaInicial
            ---  que sea el ultimo  faltaria ordenar por fecha
            --select @fechaini, @fechafin
            SELECT @datetime_nueva= DATEADD(minute,@minutos, dateadd(HOUR, @horas, @fechafin))-- agregamos el tiempo ingresado por el usuario y obtenemos un nuevo tiempo
            SELECT TOP(1)
                @datetime= date_time
            FROM biometrico.dbo.bitacora
            WHERE id=@identificacion AND consecutivo=@consecutivo AND año=@anno AND date_time NOT IN (@fechaini) AND marcado=0
            ORDER BY date_time
            -- o que no este marcado =1

            IF @datetime_nueva>@datetime  --si la nueva fecha es mayor al corte del registro de salida
			BEGIN
                DECLARE @t_restante DATETIME
                DECLARE @hora_par INT
                DECLARE @min_par INT
                --select 'aca'
                SELECT @t_restante=@datetime-@fechafin
                --	select @t_restante as tiempo_restante_corte---   tiempo restante
                SELECT @hora_par= DATEPART(hour, @t_restante), @min_par= DATEPART(MINUTE, @t_restante)
                -- lo separamos en  horas, minutos
                --select @hora_par, @min_par

                SELECT @datetime_nueva= DATEADD(minute,@min_par, dateadd(HOUR, @hora_par, @fechafin))-- le sumamos el tiempo 
                --	select @datetime_nueva as nueva_fecha  -- se deberia insertar el nuevo registro desde la fecha anterior a la nueva 
                -- luego se deberia preguntar si falta mas tiempo por asignar
                --	insertamos el nuevo registro  hasta el corte de la primera fecha
                INSERT INTO Jornada_Bitacora
                    (id_Bitacora,idUsuario, idCentroTrabajo, idActividad, FechaInicial, FechaFinal, Descripcion, idusuarioRegistra, FechaRegistro, idUnidadNegocio, Tiquete_Registro)
                VALUES
                    (NEWID(), @idUsuario, @idCentroTrabajo, @idActividad, @fechafin, @datetime_nueva, @Descripcion, @idUsuario_Registra, getdate(), @idUnidadNegocio, @tiquete )
                UPDATE biometrico.DBO.bitacora SET marcado=1  WHERE date_time =@fechaini OR date_time =@datetime

                DECLARE @tt_restante INT
                DECLARE @t_entrada INT
                DECLARE @t_salida INT
                DECLARE @t_salida_hor INT
                DECLARE @t_salida_min INT
                SET @tt_restante = (@hora_par*60)+(@min_par)
                --convertimos el tiempo restante corte en minutos
                SET @t_entrada = (@horas*60)+(@minutos)
                -- convertirmos el tiempo de entrada por teclado en minutos
                --	select @tt_restante as tiempo_restante, @t_entrada as entrada_teclado
                SET @t_salida = @t_entrada-@tt_restante
                -- restamos del tiempo de entrada teclado  menos tiempo restante
                --select @t_salida
                SET @t_salida_hor= @t_salida/60
                --convertimos la diferencia en horas
                SET @t_salida_min= @t_salida%60
                --convertimos la diferencia en minutos
                --	select @t_salida_hor,@t_salida_min  -- tiempo restante a insertar

                DECLARE @datetime_new DATETIME
                SELECT TOP(1)
                    @datetime_new= date_time
                FROM biometrico.dbo.bitacora
                WHERE id=@identificacion AND consecutivo=@consecutivo AND año=@anno AND date_time NOT IN (@datetime,@fechaini) OR marcado =0
                ORDER BY date_time
                -- o que no este marcado =1
                --	select @datetime_new as tiempo_corte
                SELECT @datetime_nueva= DATEADD(minute,@t_salida_min, dateadd(HOUR, @t_salida_hor, @datetime_new))-- le sumamos el tiempo 
                --	select @datetime_nueva as nueva_fecha  -- se deberia insertar el nuevo registro desde la fecha anterior a la nueva 
                --insertamos el nuevo registro  hasta el corte de la primera fecha
                INSERT INTO Jornada_Bitacora
                    (id_Bitacora,idUsuario, idCentroTrabajo, idActividad, FechaInicial, FechaFinal, Descripcion, idusuarioRegistra, FechaRegistro, idUnidadNegocio, Tiquete_Registro)
                VALUES
                    (NEWID(), @idUsuario, @idCentroTrabajo, @idActividad, @datetime_new, @datetime_nueva, @Descripcion, @idUsuario_Registra, getdate(), @idUnidadNegocio, @tiquete )

            END
			ELSE  -- si no es mayor a la fecha de corte, insertamos el nuevo registro en la tabla jornada bitacora
			BEGIN
                SELECT @datetime_nueva= DATEADD(minute,@minutos, dateadd(HOUR, @horas, @fechafin))-- le sumamos el tiempo 
                --select @datetime_nueva as nueva_fecha  -- se deberia insertar el nuevo registro desde la fecha anterior a la nueva 
                -- luego se deberia preguntar si falta mas tiempo por asignar
                --	insertamos el nuevo registro  hasta el corte de la primera fecha
                INSERT INTO Jornada_Bitacora
                    (id_Bitacora,idUsuario, idCentroTrabajo, idActividad, FechaInicial, FechaFinal, Descripcion, idusuarioRegistra, FechaRegistro, idUnidadNegocio, Tiquete_Registro)
                VALUES
                    (NEWID(), @idUsuario, @idCentroTrabajo, @idActividad, @fechafin, @datetime_nueva, @Descripcion, @idUsuario_Registra, getdate(), @idUnidadNegocio, @tiquete )
                IF(@datetime_nueva=@datetime) -- preguntamos si es igual al tiempo restante
					UPDATE biometrico.DBO.bitacora SET marcado=1  WHERE date_time =@fechaini OR date_time =@datetime

            END
        END
    END

/*
	SET NOCOUNT ON;
	IF @idBitacora = '00000000-0000-0000-0000-000000000000' or @idBitacora IS NULL
	BEGIN
		SET @idBitacora = NEWID()
		INSERT INTO Jornada_Bitacora(
			id_Bitacora,
			idUsuario,
			idCentroTrabajo,
			idActividad,
			FechaInicial,
			FechaFinal,
			Descripcion,
			idusuarioRegistra,
			FechaRegistro,
			idUnidadNegocio,
			Tiquete_Registro
		)
		VALUES(
			@idBitacora,
			@idUsuario,
			@idCentroTrabajo,
			@idActividad,
			@fechaInicio,
			@fechaFinal,
			@Descripcion,
			@idUsuario_Registra,
			GETDATE(),
			@idUnidadNegocio,
			@tiquete
		)

	END
	ELSE
	BEGIN
		UPDATE [dbo].[Jornada_Bitacora]
		SET [idUsuario]=@idUsuario,
		[idCentroTrabajo]=@idCentroTrabajo,
		[idActividad]=@idActividad,
		[FechaInicial]=@fechaInicio,
		[FechaFinal]=@fechaFinal,
		[Descripcion]=@Descripcion,
		[idusuarioRegistra]=@idUsuario_Registra,
		[idUnidadNegocio]=@idUnidadNegocio,
		[tiquete_registro]=@tiquete
		WHERE id_Bitacora = @idBitacora
    -- Insert statements for procedure here
	END  */



END
GO
