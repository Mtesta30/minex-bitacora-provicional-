SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER VIEW [dbo].[vJornadaBitacora]
AS
    SELECT j.id_Bitacora, c.Descripcion AS CentroTrabajo, j.idCentroTrabajo, a.Descripcion AS Actividad, a.idActividad, u.NombreUsuarioLargo AS Usuario, u.idUsuario, j.FechaInicial, j.FechaFinal, j.Descripcion,
        us.NombreUsuarioLargo AS UsuarioRegistra, us.idUsuario AS idUsuarioRegistra, j.FechaRegistro, j.FechaFinal - j.FechaInicial AS Horas, dbo.Get_costoHora_usuario(u.idUsuario, j.FechaInicial) AS Costo_hora,
        CAST(CAST(DATEDIFF(MINUTE, j.FechaInicial, j.FechaFinal) AS DECIMAL(10, 2)) / 60 AS DECIMAL(10, 2)) * dbo.Get_costoHora_usuario(u.idUsuario, j.FechaInicial) AS Costo_actividad, un.idUnidadNegocio,
        un.Descripcion AS UnidadNegocio, dbo.Get_sueldo_usuario(u.idUsuario, j.FechaInicial) AS Sueldo, j.Tiquete_Registro, dbo.Get_Turno(u.idUsuario, j.FechaInicial) AS Turno, dbo.UsuariosDetalle.Cargo,
        dbo.UsuariosDetalle.Identificacion
    FROM dbo.Jornada_Bitacora AS j INNER JOIN
        dbo.Usuarios AS u ON u.idUsuario = j.idUsuario INNER JOIN
        dbo.Destino AS c ON c.idDestino = j.idCentroTrabajo INNER JOIN
        dbo.Actividades AS a ON a.idActividad = j.idActividad INNER JOIN
        dbo.Usuarios AS us ON us.idUsuario = j.idusuarioRegistra INNER JOIN
        dbo.UsuariosDetalle ON u.idUsuario = dbo.UsuariosDetalle.idUsuario LEFT OUTER JOIN
        dbo.Jornada_Bitacora_UnidadNegocio AS un ON j.idUnidadNegocio = un.idUnidadNegocio
    GROUP BY j.id_Bitacora, c.Descripcion, j.idCentroTrabajo, a.Descripcion, a.idActividad, u.NombreUsuarioLargo, u.idUsuario, j.FechaInicial, j.FechaFinal, j.Descripcion, us.NombreUsuarioLargo, us.idUsuario, j.FechaRegistro, 
                         CAST(CAST(DATEDIFF(MINUTE, j.FechaInicial, j.FechaFinal) AS DECIMAL(10, 2)) / 60 AS DECIMAL(10, 2)), dbo.Get_costoHora_usuario(u.idUsuario, j.FechaInicial), CAST(CAST(DATEDIFF(MINUTE, j.FechaInicial, j.FechaFinal) 
                         AS DECIMAL(10, 2)) / 60 AS DECIMAL(10, 2)) * dbo.Get_costoHora_usuario(u.idUsuario, j.FechaInicial), un.idUnidadNegocio, un.Descripcion, dbo.Get_sueldo_usuario(u.idUsuario, j.FechaInicial), j.Tiquete_Registro, 
                         dbo.Get_Turno(u.idUsuario, j.FechaInicial), dbo.UsuariosDetalle.Cargo, dbo.UsuariosDetalle.Identificacion
GO
