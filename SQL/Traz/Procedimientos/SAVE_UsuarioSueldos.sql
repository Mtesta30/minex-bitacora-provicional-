SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE PROCEDURE  [dbo].[SAVE_UsuarioSueldos]
    @idxid UNIQUEIDENTIFIER = NULL,
    @idUsuario UNIQUEIDENTIFIER,
    @sueldo  MONEY,
    @fecha DATE,
    @usuarioRegistra UNIQUEIDENTIFIER
AS
BEGIN

    SET NOCOUNT ON;
    IF @idxid = '00000000-0000-0000-0000-000000000000' OR @idxid IS NULL
	BEGIN
        SET @idxid = NEWID()

        INSERT INTO Usuarios_sueldos
            (idxid,idUsuario,Sueldo,Fecha,idUsuario_Registro,Fecha_Registro)
        VALUES(@idxid,
                @idUsuario,
                @sueldo,
                @fecha,
                @usuarioRegistra,
                GETDATE())
    END

    ELSE 
	BEGIN
        UPDATE [dbo].[Usuarios_sueldos]
	SET [idxid]=@idxid,
	[idUsuario]=@idUsuario,
	[Sueldo]=@sueldo,
	[Fecha]=@fecha,
	[idUsuario_registro]=@usuarioRegistra
	WHERE idxid= @idxid

    END

END
GO
