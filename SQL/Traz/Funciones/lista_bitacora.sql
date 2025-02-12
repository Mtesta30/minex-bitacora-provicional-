SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE FUNCTION [dbo].[lista_bitacora]
(	@desde DATE
	,@hasta DATE
	,@idEmpresa UNIQUEIDENTIFIER
	,@idDestino VARCHAR(100) = NULL
	,@idusuario UNIQUEIDENTIFIER =NULL
)
RETURNS 
@CAL TABLE 
(
    nombre VARCHAR(200),
    id VARCHAR(30),
    ann INT,
    consecutivo INT,
    hora_entrada DATETIME,
    hora_salida DATETIME,
    horas DATETIME,
    dispositivo VARCHAR(50),
    tarde INT
)
AS
BEGIN
    DECLARE @id VARCHAR(50)
    DECLARE @var VARCHAR(50)=''
    DECLARE @dispositivo VARCHAR(50)=''
    DECLARE @nombre VARCHAR(150)
    DECLARE @año INT
    DECLARE @conse INT
    DECLARE @tiempo DATETIME
    DECLARE @hora_entra DATETIME
    DECLARE @hora_sali DATETIME
    DECLARE @iduser UNIQUEIDENTIFIER

    DECLARE @disp VARCHAR(100)=''
    IF @idDestino IS NOT NULL AND @idusuario IS NOT NULL
	BEGIN
        SELECT @id = identificacion, @nombre= NombreCompleto
        FROM traz.dbo.UsuariosDetalle
        WHERE idUsuario = @idusuario
        DECLARE DT CURSOR FOR
		SELECT id, año, consecutivo
        FROM biometrico.dbo.bitacora
        WHERE id =@id AND access_date>=@desde AND dispositivo=@idDestino
        GROUP BY id,año,consecutivo
        ORDER BY consecutivo
    -- and marcado=0
    END	
	ELSE IF @idDestino IS NULL AND @idusuario IS NOT NULL
	BEGIN
        SELECT @id = identificacion, @nombre= NombreCompleto
        FROM traz.dbo.UsuariosDetalle
        WHERE idUsuario = @idusuario
        DECLARE DT CURSOR FOR
		SELECT id, año, consecutivo
        FROM biometrico.dbo.bitacora
        WHERE id =@id AND access_date>=@desde
        GROUP BY id,año,consecutivo
        ORDER BY consecutivo
    -- and marcado=0
    END
	ELSE IF @idDestino IS NOT NULL AND @idusuario IS NULL
	BEGIN
        DECLARE DT CURSOR FOR
		SELECT id, año, consecutivo
        FROM biometrico.dbo.bitacora
        WHERE access_date>=@desde AND dispositivo=@idDestino
        GROUP BY id,año,consecutivo
        ORDER BY id, consecutivo
    -- MARCADO =0
    END
	ELSE IF @idDestino IS NULL AND @idusuario IS NULL
	BEGIN
        DECLARE DT CURSOR FOR
		SELECT id, año, consecutivo
        FROM biometrico.dbo.bitacora
        WHERE access_date>=@desde
        GROUP BY id,año,consecutivo
        ORDER BY id, consecutivo
    -- MARCADO =0
    END

    OPEN DT

    FETCH NEXT
	FROM dt
	INTO @id,@año, @conse

    WHILE @@FETCH_STATUS = 0
	BEGIN
        IF @idusuario IS NULL
			SELECT @iduser= idusuario, @nombre= NombreCompleto
        FROM traz.dbo.UsuariosDetalle
        WHERE Identificacion =@id
		ELSE
			SET @iduser= @idusuario

        SELECT @tiempo =traz.dbo.buscar_horas (CONCAT(@año,'-',@conse), @iduser)
        SELECT @hora_entra= date_time, @dispositivo=dispositivo
        FROM biometrico.dbo.bitacora
        WHERE id =@id AND consecutivo = @conse AND año=@año
        ORDER BY date_time DESC
        --and MARCADO =0 
        SELECT @hora_sali=date_time, @dispositivo=dispositivo
        FROM biometrico.dbo.bitacora
        WHERE id =@id AND consecutivo = @conse AND año=@año
        ORDER BY date_time
        --and MARCADO =0 

        DECLARE @hora_acceso AS TIME
        DECLARE @fecha_acceso AS DATE
        DECLARE @inicio AS TIME
        DECLARE @turno AS UNIQUEIDENTIFIER
        DECLARE @bandera AS INT =0
        DECLARE @tarde INT =0
        DECLARE TT CURSOR FOR
			SELECT access_date, access_time
        FROM biometrico.dbo.bitacora
        WHERE id=@id AND consecutivo=@conse AND año=@año AND Estado_real='entrada' AND tipo ='turno'
        ORDER BY date_time
        OPEN TT

        FETCH NEXT
		FROM TT
		INTO @fecha_acceso, @hora_acceso
        WHILE @@FETCH_STATUS = 0
		BEGIN
            SELECT @turno=id_turno
            FROM turnos_empleados INNER JOIN BitacoraTurnos ON turnos_empleados.id_turno= BitacoraTurnos.idTurno
                    AND @fecha_acceso BETWEEN FechaInicio AND FechaFin
                    AND idUsuario=(SELECT idUsuario
                    FROM UsuariosDetalle
                    WHERE Identificacion=@id)
                    AND dias= (CASE DATENAME(dw,@fecha_acceso)
				 WHEN 'Monday' THEN 'lunes'
				 WHEN 'Tuesday' THEN 'martes'
				 WHEN 'Wednesday' THEN 'miercoles'
				 WHEN 'Thursday' THEN 'jueves'
				 WHEN 'Friday' THEN 'viernes'
				 WHEN 'Saturday' THEN 'sábado'
				 WHEN 'Sunday' THEN 'domingo'  END)

            IF @bandera=0
				BEGIN
                SELECT TOP(1)
                    @inicio= hora_inicio
                FROM Bitacora_horarios
                WHERE id_turno =@turno AND idactividad ='47C297B3-411E-445E-8357-797687831DC2'
                ORDER BY fecharegistro ASC
                SET @bandera=1
            END
			ELSE
				SELECT TOP(1)
                @inicio= hora_fin
            FROM Bitacora_horarios
            WHERE id_turno =@turno AND idactividad ='1AC30E97-8F11-47E2-A5EA-96BBEEA575AC'
            ORDER BY fecharegistro ASC

            SET @inicio = dateadd(MINUTE,5,@inicio)
            IF @hora_acceso>@inicio
				SET @tarde=1

            FETCH NEXT
			FROM TT
			INTO  @fecha_acceso, @hora_acceso
        END
        CLOSE TT
        DEALLOCATE TT


        INSERT INTO @cal
            (
            nombre
            ,id
            ,ann
            ,consecutivo
            ,hora_entrada
            ,hora_salida
            ,horas
            ,dispositivo
            ,tarde
            )
        VALUES
            (
                @nombre
			, @id
			, @año
			, @conse
			, @hora_entra
			, @hora_sali
			, @tiempo
			, @dispositivo
			, @tarde
			)
        FETCH NEXT
		FROM dt
		INTO  @id,@año, @conse
    END
    CLOSE DT
    DEALLOCATE DT
    RETURN

END




GO
