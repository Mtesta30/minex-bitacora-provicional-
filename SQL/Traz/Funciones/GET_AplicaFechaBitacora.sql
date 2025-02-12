SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date, ,>
-- Description:	<Description, ,>
-- =============================================
CREATE FUNCTION [dbo].[GET_AplicaFechaBitacora] 
(
	@FechaInicial DATETIME,
	@FechaFinal DATETIME,
	@idUsuario UNIQUEIDENTIFIER,
	@id_Bitacora UNIQUEIDENTIFIER
)
RETURNS VARCHAR(255)
AS
BEGIN
    -- Declare the return variable here
    DECLARE @A VARCHAR(255) = ''
    DECLARE @B VARCHAR(255) = ''
    DECLARE @C VARCHAR(255) = ''

    SELECT @C='Entre las fechas inicial y final se encuentra en el rango: ' + cast(FechaInicial AS VARCHAR(100)) + '--' + cast(FechaFinal AS VARCHAR(100)) + ','
    FROM Jornada_Bitacora
    WHERE idUsuario=@idUsuario AND id_Bitacora<>@id_Bitacora AND ((FechaInicial BETWEEN @FechaInicial AND @FechaFinal) OR (FechaFinal BETWEEN @FechaInicial AND @FechaFinal))

    IF @C=''
	BEGIN
        SELECT @A= 'La fecha inicial se encuentra en el rango: '  + cast(FechaInicial AS VARCHAR(100)) + '--' + cast(FechaFinal AS VARCHAR(100)) + ','
        FROM Jornada_Bitacora
        WHERE idUsuario=@idUsuario AND id_Bitacora<>@id_Bitacora AND @FechaInicial BETWEEN FechaInicial AND FechaFinal

        SELECT @B='La fecha final se encuentra en el rango: ' + cast(FechaInicial AS VARCHAR(100)) + '--' + cast(FechaFinal AS VARCHAR(100)) + ','
        FROM Jornada_Bitacora
        WHERE idUsuario=@idUsuario AND id_Bitacora<>@id_Bitacora AND @FechaFinal BETWEEN FechaInicial AND FechaFinal
    -- Return the result of the function
    END

    SET @A = @A + @B + @C
    IF @A<>'' OR @B<>'' OR @C<>''
		SET @A=SUBSTRING(@A,1, LEN(@A) - 1)
	ELSE
		SET @A = ''

    RETURN @A

END
GO
