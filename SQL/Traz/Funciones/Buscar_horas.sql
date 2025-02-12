SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Pedro Marciales>
-- Create date: <Create Date, ,2024-08-02>
-- Description:	<Description, ,Retorna las horas totales de un tiquete de personal>
-- =============================================
CREATE FUNCTION [dbo].[Buscar_horas]
(	@idtiquete NVARCHAR(12),   -- viene en formato  año-consecutivo
	@usuario UNIQUEIDENTIFIER
)
RETURNS DATETIME
AS
BEGIN
    --///////////  separar el año y consecutivo
    DECLARE @posicion INT;
    DECLARE @anno NVARCHAR(10);
    DECLARE @consecutivo NVARCHAR(10);
    SET @posicion = CHARINDEX('-', @idtiquete);
    -- Encontrar la posición del delimitador '-'
    IF @posicion=0
		SET @anno = SUBSTRING(@idtiquete, 1, 0);
	ELSE
		SET @anno = SUBSTRING(@idtiquete, 1, @posicion - 1);
    -- Extraer la parte antes del delimitador
    SET @consecutivo = SUBSTRING(@idtiquete, @posicion + 1, LEN(@idtiquete) - @posicion);
    -- Extraer la parte después del delimitador


    DECLARE @tiempo DATETIME
    DECLARE @tiempo_t DATETIME ='1900-01-01 00:00:00.000'
    DECLARE @fechaparcial DATETIME
    DECLARE @fecha DATETIME
    DECLARE @estado VARCHAR(30)
    DECLARE @bandera BIT =0
    DECLARE @documento AS INT
    SELECT @documento = Identificacion
    FROM UsuariosDetalle
    WHERE idUsuario=@usuario
    DECLARE @cantidad INT
    SELECT @cantidad=count(*)
    FROM biometrico.dbo.bitacora
    WHERE id=@documento AND consecutivo=@consecutivo AND año =@anno
    --order by date_time 
    IF @cantidad%2=0
	BEGIN
        DECLARE DT CURSOR FOR
		SELECT date_time, Estado_real
        FROM biometrico.dbo.bitacora
        WHERE id=@documento AND consecutivo=@consecutivo AND año=@anno
        ORDER BY date_time
        OPEN DT

        FETCH NEXT
		FROM dt
		INTO @fecha, @estado

        WHILE @@FETCH_STATUS = 0
		BEGIN
            IF @bandera=0 AND @estado='Entrada'
			BEGIN
                SET @bandera=1
                SET @fechaparcial= @fecha
            END
			ELSE IF @bandera=1 AND @estado='Salida'
			BEGIN
                SET @bandera=0
                SELECT @tiempo=@fecha- @fechaparcial
                SET @tiempo_t=@tiempo_t+@tiempo
            END
			ELSE IF @estado='Pendiente'
			BEGIN
                SET @tiempo_t=0
                BREAK;
            END
            FETCH NEXT
			FROM dt
			INTO @fecha, @estado
        END
        CLOSE dt
        DEALLOCATE dt
    END
	ELSE
		SET @tiempo_t=0

    RETURN @tiempo_t
END
GO
