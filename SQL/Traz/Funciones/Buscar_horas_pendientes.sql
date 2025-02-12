SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Pedro Marciales>
-- Create date: <Create Date, ,2024-08-02>
-- Description:	<Description, ,Retorna las horas totales de un tiquete de personal>
-- =============================================
CREATE FUNCTION [dbo].[Buscar_horas_pendientes]
(	@idtiquete VARCHAR(12),  --  viene en formato  año-consecutivo
	@usuario UNIQUEIDENTIFIER
)
RETURNS DATETIME
AS
BEGIN
    DECLARE @horas_totales AS DATETIME
    SELECT @horas_totales= traz.dbo.buscar_horas (@idtiquete, @usuario)
    DECLARE @tiempo DATETIME
    DECLARE @tiempo_t DATETIME ='1900-01-01 00:00:00.000'
    DECLARE @fechainicial DATETIME
    DECLARE @fechafinal DATETIME
    DECLARE DT CURSOR FOR
		SELECT FechaInicial, FechaFinal
    FROM Jornada_Bitacora
    WHERE idUsuario=@usuario AND Tiquete_Registro=@idtiquete
    ORDER BY FechaRegistro

    OPEN DT

    FETCH NEXT
	FROM dt
	INTO  @fechainicial,@fechafinal

    WHILE @@FETCH_STATUS = 0
	BEGIN
        SELECT @tiempo=@fechafinal- @fechainicial
        SET @tiempo_t=@tiempo_t+@tiempo

        FETCH NEXT
		FROM dt
		INTO @fechainicial,@fechafinal
    END
    CLOSE dt
    DEALLOCATE dt

    RETURN (@horas_totales-@tiempo_t)

END


GO
