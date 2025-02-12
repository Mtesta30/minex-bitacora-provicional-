SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[Jornada_Bitacora]
(
    [id_Bitacora] [UNIQUEIDENTIFIER] NOT NULL,
    [idUsuario] [UNIQUEIDENTIFIER] NOT NULL,
    [idCentroTrabajo] [UNIQUEIDENTIFIER] NOT NULL,
    [idActividad] [UNIQUEIDENTIFIER] NOT NULL,
    [FechaInicial] [DATETIME] NOT NULL,
    [FechaFinal] [DATETIME] NOT NULL,
    [Descripcion] [VARCHAR](max) NULL,
    [idusuarioRegistra] [UNIQUEIDENTIFIER] NULL,
    [FechaRegistro] [DATETIME] NULL,
    [idUnidadNegocio] [UNIQUEIDENTIFIER] NULL,
    [Tiquete_Registro] [NVARCHAR](12) NULL,
    CONSTRAINT [PK_Jornada_Bitacora] PRIMARY KEY CLUSTERED 
(
	[id_Bitacora] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
ALTER TABLE [dbo].[Jornada_Bitacora]  WITH CHECK ADD  CONSTRAINT [FK_Jornada_Bitacora_Actividades] FOREIGN KEY([idActividad])
REFERENCES [dbo].[Actividades] ([idActividad])
GO
ALTER TABLE [dbo].[Jornada_Bitacora] CHECK CONSTRAINT [FK_Jornada_Bitacora_Actividades]
GO
ALTER TABLE [dbo].[Jornada_Bitacora]  WITH CHECK ADD  CONSTRAINT [FK_Jornada_Bitacora_Destino] FOREIGN KEY([idCentroTrabajo])
REFERENCES [dbo].[Destino] ([idDestino])
GO
ALTER TABLE [dbo].[Jornada_Bitacora] CHECK CONSTRAINT [FK_Jornada_Bitacora_Destino]
GO
ALTER TABLE [dbo].[Jornada_Bitacora]  WITH CHECK ADD  CONSTRAINT [FK_Jornada_Bitacora_Usuarios] FOREIGN KEY([idUsuario])
REFERENCES [dbo].[Usuarios] ([idUsuario])
GO
ALTER TABLE [dbo].[Jornada_Bitacora] CHECK CONSTRAINT [FK_Jornada_Bitacora_Usuarios]
GO
ALTER TABLE [dbo].[Jornada_Bitacora]  WITH CHECK ADD  CONSTRAINT [FK_Jornada_Bitacora_Usuarios1] FOREIGN KEY([idusuarioRegistra])
REFERENCES [dbo].[Usuarios] ([idUsuario])
GO
ALTER TABLE [dbo].[Jornada_Bitacora] CHECK CONSTRAINT [FK_Jornada_Bitacora_Usuarios1]
GO
