SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date, ,>
-- Description:	<Description, ,>
-- =============================================
CREATE FUNCTION [dbo].[Get_horas_extras]
(
	@idUsuario UNIQUEIDENTIFIER,
	@tiquete INT
)
RETURNS MONEY
AS
BEGIN

    --DECLARE @totalHoras MONEY
    --DECLARE @horasExtras MONEY =0


    --SELECT @totalHoras = SUM(Horas)
    --FROM vJornadaBitacora
    --WHERE idUsuario=@idUsuario AND Tiquete_Registro=@tiquete
    --group by Tiquete_Registro

    DECLARE @hour AS DATETIME
    DECLARE @hour_T AS DATETIME='1900-01-01 00:00:00'
    DECLARE dt CURSOR FOR
	 SELECT Horas
    FROM vJornadaBitacora
    WHERE idUsuario=@idUsuario AND Tiquete_Registro=@tiquete
    OPEN DT
    FETCH NEXT
		FROM dt
		INTO @hour
    WHILE @@FETCH_STATUS = 0
		BEGIN
        SET @hour_T=DATEADD(SECOND, DATEDIFF(SECOND, '1900-01-01', @hour)+ + DATEDIFF(SECOND, '1900-01-01', @hour_T), '1900-01-01')

        FETCH NEXT
			FROM DT
			INTO @hour
    END
    CLOSE dt
    DEALLOCATE dt
    DECLARE @VAR VARCHAR(18);
    SET @VAR = CAST(@hour_T AS VARCHAR)
    SET @VAR = SUBSTRING(@VAR, 13, 5);
    -- Extraer la parte de las horas
    SET @var = REPLACE(@var,':','.')



    IF cast(@VAR AS FLOAT) > 7.83
	SET @VAR =cast(@VAR AS FLOAT)-7.83
ELSE
	SET @var=0



    RETURN @var

END
GO
