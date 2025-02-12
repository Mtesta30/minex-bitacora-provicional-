SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO


CREATE FUNCTION [dbo].[GET_AplicaFechaSueldo] 
(
	@Fecha DATE,
	@idUsuario UNIQUEIDENTIFIER,
	@idxid UNIQUEIDENTIFIER
)
RETURNS VARCHAR(255)
AS
BEGIN
    -- Declare the return variable here
    DECLARE @A VARCHAR(255) = ''
    DECLARE @B VARCHAR(255) = ''

    IF (@idxid != '00000000-0000-0000-0000-000000000000')
BEGIN
        SELECT TOP 1
            @A = 'Esta repetida: ' + CAST(Fecha AS VARCHAR(100))
        FROM vusuarios_sueldos
        WHERE idUsuario = @idUsuario
            AND idxid <> @idxid
            AND Fecha = @Fecha
        ORDER BY Fecha ASC
    END

ELSE 
BEGIN
        SELECT @B=Fecha
        FROM vusuarios_sueldos
        WHERE idUsuario = @idUsuario
        IF(@B<>'')
		BEGIN
            SELECT @A =
			CASE
			WHEN @Fecha > (SELECT MAX(Fecha)
                FROM vusuarios_sueldos
                WHERE idUsuario = @idUsuario)
			THEN ''
			WHEN @Fecha < (SELECT MIN(Fecha)
                    FROM vusuarios_sueldos
                    WHERE idUsuario = @idUsuario) OR ((SELECT COUNT(*)
                    FROM vusuarios_sueldos
                    WHERE idUsuario = @idUsuario) = 1 AND @Fecha < (SELECT Fecha
                    FROM vusuarios_sueldos
                    WHERE idUsuario = @idUsuario))
			THEN 'La fecha no debe ser menor'
			WHEN @Fecha IN (SELECT Fecha
                FROM vusuarios_sueldos
                WHERE idUsuario = @idUsuario
                GROUP BY Fecha
                HAVING COUNT(*) = 1)
			THEN 'Ya esta registrada'
			ELSE 'Estas intentando registrar en medio de dos fechas'
			END;
        END
		ELSE
		BEGIN
            SET @A=''
        END
    END
    RETURN @A
END
GO
