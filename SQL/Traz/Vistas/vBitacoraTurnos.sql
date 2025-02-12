SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER VIEW [dbo].[vBitacoraTurnos]
AS
    SELECT dbo.Usuarios.idUsuario, dbo.BitacoraTurnos.idxid, dbo.Usuarios.NombreUsuarioLargo, dbo.BitacoraTurnos.HoraTurno, dbo.BitacoraTurnos.FechaInicio
    FROM dbo.BitacoraTurnos INNER JOIN
        dbo.Usuarios ON dbo.BitacoraTurnos.idUsuario = dbo.Usuarios.idUsuario
GO
