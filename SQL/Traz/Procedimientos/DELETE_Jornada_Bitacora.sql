SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE PROCEDURE [dbo].[DELETE_Jornada_Bitacora]
    -- Add the parameters for the stored procedure here
    @id_Bitacora UNIQUEIDENTIFIER
AS
BEGIN
    DELETE Jornada_Bitacora WHERE id_Bitacora=@id_Bitacora
END
GO
