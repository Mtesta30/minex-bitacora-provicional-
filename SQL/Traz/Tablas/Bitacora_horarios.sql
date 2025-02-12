SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[Bitacora_horarios]
(
    [idxid] [UNIQUEIDENTIFIER] NOT NULL,
    [hora_inicio] [TIME](7) NULL,
    [hora_fin] [TIME](7) NULL,
    [id_turno] [UNIQUEIDENTIFIER] NULL,
    [idactividad] [UNIQUEIDENTIFIER] NULL,
    [fecharegistro] [DATETIME] NULL,
    [idusuario] [UNIQUEIDENTIFIER] NULL,
    CONSTRAINT [PK_Bitacora_horarios] PRIMARY KEY CLUSTERED 
(
	[idxid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
ALTER TABLE [dbo].[Bitacora_horarios]  WITH CHECK ADD  CONSTRAINT [FK_Bitacora_horarios_turnos_empleados] FOREIGN KEY([id_turno])
REFERENCES [dbo].[turnos_empleados] ([id_turno])
GO
ALTER TABLE [dbo].[Bitacora_horarios] CHECK CONSTRAINT [FK_Bitacora_horarios_turnos_empleados]
GO
