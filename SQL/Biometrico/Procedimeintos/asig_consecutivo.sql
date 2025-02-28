SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE PROCEDURE [dbo].[asig_consecutivo]
    @id AS INTEGER
AS
BEGIN
    INSERT INTO Traz.dbo.logs
    VALUES(concat('entro_update_11  ',@id))
    --DECLARE @id as integer = 88209332
    SET NOCOUNT ON;
    DECLARE @consecutivo INTEGER
    DECLARE @tiempo DATETIME
    DECLARE @hora_difere AS INT =0
    DECLARE @hora_parcial AS DATETIME
    DECLARE @hora_ultima AS DATETIME
    DECLARE @estado AS VARCHAR(20)
    DECLARE @tipoEmpl AS INT
    DECLARE @marcado AS INT=0
    SELECT @hora_ultima=max(date_time)
    FROM bitacora
    WHERE  id= @id AND access_date = cast(getdate() AS DATE) AND Estado_real IS NULL
    SELECT TOP(1)
        @hora_parcial= date_time, @estado=estado_real
    FROM biometrico.dbo.bitacora
    WHERE id= @id AND Estado_real IS NOT NULL
    ORDER BY date_time DESC
    SELECT @hora_difere = DATEDIFF(HOUR,@hora_parcial,@hora_ultima)

    DECLARE @cuenta AS INT=0
    SELECT @cuenta= count(*)
    FROM biometrico.[dbo].[bitacora]
    WHERE id= @id
    SELECT @tipoEmpl= iTipoEmpleado
    FROM traz.dbo.usuariosempresa INNER JOIN traz.dbo.UsuariosDetalle ON traz.dbo.UsuariosEmpresa.idUsuario=traz.dbo.UsuariosDetalle.idUsuario
    WHERE  traz.dbo.UsuariosDetalle.Identificacion=cast(@id AS VARCHAR)
    IF (@tipoEmpl=0)
		SET @marcado=1
    IF @cuenta >1
		SELECT TOP(1)
        @consecutivo=consecutivo , @tiempo=date_time
    FROM biometrico.[dbo].[bitacora]
    WHERE /*access_date = cast(getdate() as date)  and*/ id= @id AND año= year(getdate()) AND consecutivo IS NOT NULL
    ORDER BY date_time DESC
	ELSE
		SELECT TOP(1)
        @consecutivo=consecutivo , @tiempo=date_time
    FROM biometrico.[dbo].[bitacora]
    WHERE /*access_date = cast(getdate() as date)  and*/ id= @id AND año= year(getdate()) AND consecutivo IS NULL
    ORDER BY date_time DESC

    --select top(1) @consecutivo=consecutivo , @tiempo=date_time from [dbo].[bitacora] where access_date = cast(getdate() as date)  and id= @id and año= year(getdate()) and consecutivo is not null order by date_time desc
    IF @consecutivo IS NULL
	BEGIN
        SELECT @consecutivo= max(consecutivo)
        FROM biometrico.dbo.bitacora
        WHERE año= year(getdate())
        IF @consecutivo IS NULL
			SET @consecutivo = 1
		ELSE
			SET @consecutivo = @consecutivo+1
    END
	ELSE
	BEGIN
        SELECT @consecutivo= max(consecutivo)
        FROM biometrico.dbo.bitacora
        WHERE año= year(getdate()) AND id=@id
    END
    IF @hora_difere IS NOT NULL
	BEGIN
        IF @hora_difere>10
		BEGIN
            --SET @consecutivo = @consecutivo+1
            IF @estado ='Entrada'
			BEGIN
                DECLARE @date1 AS DATE
                DECLARE @acces AS TIME(4)
                DECLARE @nombre AS VARCHAR(50)
                DECLARE @apellidos AS VARCHAR(50)
                DECLARE @estado_sistema AS VARCHAR(10)
                DECLARE @dispositivo AS VARCHAR(50)
                DECLARE @año AS INT
                DECLARE @consecutivo1 AS  INT

                SELECT TOP(1)
                    @date1=access_date, @hora_parcial= max(date_time), @acces=access_time, @nombre=nombres, @apellidos=apellidos, @dispositivo=dispositivo, @año=año, @consecutivo1=consecutivo
                FROM bitacora
                WHERE  id= @id AND Estado_real IS NOT NULL
                GROUP BY access_date,nombres,apellidos,dispositivo, año,consecutivo,access_time, estado_real
                ORDER BY access_date DESC, access_time DESC
                SET @hora_parcial = dateadd(MINUTE,1,@hora_parcial)
                SET @acces = dateadd(MINUTE,1,@acces)
                INSERT INTO Traz.dbo.logs
                VALUES(concat('entro_pendiente  ',cast(@hora_parcial AS VARCHAR)))--//////////////////////////////
                INSERT INTO biometrico.dbo.bitacora
                    (access_date,id,date_time,access_time,nombres,apellidos,Estado,dispositivo,consecutivo,Estado_real,marcado, Tipo, tipo_registro)
                VALUES(@date1, @id, @hora_parcial, @acces, @nombre, @apellidos, @estado, @dispositivo, @consecutivo1, 'Pendiente', 0, 'turno', 1)
                UPDATE biometrico.dbo.bitacora SET estado_real ='Entrada',marcado=@marcado, consecutivo=@consecutivo,Tipo='turno', bitacora.tipo_registro=0  WHERE date_time=@hora_ultima
            END
			ELSE
			BEGIN
                INSERT INTO Traz.dbo.logs
                VALUES(concat('entro_update_1  ',cast(@hora_ultima AS VARCHAR)))
                --//////////////////////////////
                UPDATE biometrico.dbo.bitacora SET estado_real ='Entrada',marcado=@marcado, consecutivo= @consecutivo,Tipo='turno', tipo_registro=0  WHERE date_time=@hora_ultima
            END
        END
		ELSE
		BEGIN
            DECLARE @ESTA AS VARCHAR(50)
            SELECT TOP(1)
                @ESTA=estado_real
            FROM bitacora
            WHERE  id= @id AND access_date = cast(getdate() AS DATE) AND Estado_real IS NOT NULL
            ORDER BY date_time DESC
            IF(@ESTA='Salida')
			BEGIN
                INSERT INTO Traz.dbo.logs
                VALUES(concat('entro_update_2  ',cast(@hora_ultima AS VARCHAR)))
                --//////////////////////////////
                UPDATE biometrico.dbo.bitacora SET consecutivo = @consecutivo, estado_real ='Entrada', marcado=@marcado,Tipo='turno', tipo_registro=0 WHERE date_time=@hora_ultima AND id=@id
            END
			ELSE
			BEGIN
                INSERT INTO Traz.dbo.logs
                VALUES(concat('entro_update_22  ',cast(@hora_ultima AS VARCHAR)))
                --//////////////////////////////
                UPDATE biometrico.dbo.bitacora SET consecutivo = @consecutivo, estado_real ='Salida', marcado=@marcado,Tipo='turno', tipo_registro=0 WHERE date_time=@hora_ultima AND id=@id
            END
        END
    --		update  [dbo].[bitacora] set consecutivo = @consecutivo  where  id=@id and  access_date = cast(getdate() as date) and date_time= @tiempo
    END
	ELSE
	BEGIN
        INSERT INTO Traz.dbo.logs
        VALUES(concat('entro_update_3  ',cast(@tiempo AS VARCHAR),'-',@marcado,'-',@id,'-',@tiempo ))
        --//////////////////////////////
        UPDATE  [dbo].[bitacora] SET consecutivo = @consecutivo, Estado_real='Entrada', marcado=@marcado,Tipo='turno', tipo_registro=0  WHERE  id=@id AND access_date = cast(getdate() AS DATE) AND date_time= @tiempo
    END
    RETURN @consecutivo
END


 
GO
