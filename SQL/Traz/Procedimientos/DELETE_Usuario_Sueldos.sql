SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE PROCEDURE [dbo].[DELETE_Usuario_Sueldos]
    -- Add the parameters for the stored procedure here
    @idxid UNIQUEIDENTIFIER
AS
BEGIN
    DELETE usuarios_sueldos WHERE idxid=@idxid
END
GO
