SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bitacora]
(
    [access_date] [DATE] NULL,
    [id] [INT] NULL,
    [date_time] [DATETIME] NULL,
    [access_time] [TIME](7) NULL,
    [nombres] [VARCHAR](255) NULL,
    [apellidos] [VARCHAR](255) NULL,
    [Estado] [VARCHAR](255) NULL,
    [dispositivo] [VARCHAR](255) NULL,
    [año]  AS (datepart(year,getdate())),
    [consecutivo] [INT] NULL,
    [Estado_real] [VARCHAR](20) NULL,
    [marcado] [BIT] NULL,
    [Tipo] [VARCHAR](10) NULL,
    [tipo_registro] [BIT] NULL
) ON [PRIMARY]
GO
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE TRIGGER [dbo].[Consecutivos]
   ON  [dbo].[bitacora] 
   AFTER INSERT
AS 
BEGIN
    -- SET NOCOUNT ON added to prevent extra result sets from
    -- interfering with SELECT statements.
    SET NOCOUNT ON;
    DECLARE @IDENTIFICACION  INT
    SELECT @IDENTIFICACION =[id]
    FROM inserted
    BEGIN TRY
		EXEC [dbo].[asig_consecutivo] @id = @IDENTIFICACION
    -- Insert statements for trigger here
	end try
	BEGIN CATCH
		-- Manejo de errores si la ejecución de la sentencia SQL falla
		INSERT INTO traz.dbo.logs
        (text)
    SELECT
        concat(ERROR_MESSAGE(),ERROR_LINE())
 
	END CATCH;

END



GO
ALTER TABLE [dbo].[bitacora] DISABLE TRIGGER [Consecutivos]
GO
EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'0= automatico, 1= manual' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'bitacora', @level2type=N'COLUMN',@level2name=N'tipo_registro'
GO
