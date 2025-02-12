SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date, ,>
-- Description:	<Description, ,>
-- =============================================
CREATE FUNCTION [dbo].[Get_sueldo_usuario]
(
	@idUsuario UNIQUEIDENTIFIER
	,@Fecha DATE
)
RETURNS MONEY
AS
BEGIN

    DECLARE @Sueldo MONEY
    SELECT TOP 1
        @Sueldo=Sueldo
    FROM vUsuarios_sueldos
    WHERE idUsuario=@idUsuario AND Fecha<=@Fecha
    ORDER BY Fecha DESC
    -- Declare the return variable here

    RETURN @Sueldo

END
GO
