SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER VIEW [dbo].[vUsuarios_sueldos]
AS
    SELECT u_suel.idxid, u.idUsuario, u.NombreUsuarioLargo AS Usuario, u_suel.Sueldo, u_suel.Fecha, u_suel.Costo_hora, u2.idUsuario AS idUsuario_registro, u2.NombreUsuarioLargo AS Usuario_registro, u_suel.Fecha_registro
    FROM dbo.Usuarios_sueldos AS u_suel INNER JOIN
        dbo.Usuarios AS u ON u_suel.idUsuario = u.idUsuario INNER JOIN
        dbo.Usuarios AS u2 ON u_suel.idUsuario_registro = u2.idUsuario
GO
