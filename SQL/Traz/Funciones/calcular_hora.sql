SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Pedro Marciales>
-- Create date: <Create Date, ,2024-08-02>
-- Description:	<Description, ,Retorna las horas totales de un tiquete de personal>
-- =============================================
CREATE FUNCTION [dbo].[calcular_hora]
(	@hora_inic DATETIME,
	@hora_fin DATETIME
)
RETURNS DATETIME
AS
BEGIN
    DECLARE @tiempo AS DATETIME
    --SELECT @tiempo= DateDiff("n",@hora_inic,@hora_fin) 
    SELECT @tiempo= @hora_fin-@hora_inic


    RETURN @tiempo
END
GO
