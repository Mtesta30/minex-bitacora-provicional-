SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[BitacoraTurnos]
(
    [idxid] [UNIQUEIDENTIFIER] NOT NULL,
    [idUsuario] [UNIQUEIDENTIFIER] NOT NULL,
    [HoraTurno] [MONEY] NULL,
    [FechaInicio] [DATE] NULL,
    [FechaFin] [DATE] NULL,
    [idTurno] [UNIQUEIDENTIFIER] NULL,
    [dias] [VARCHAR](15) NULL,
    [idCentroTrabajo] [UNIQUEIDENTIFIER] NULL,
    CONSTRAINT [PK_BitacoraTurnos] PRIMARY KEY CLUSTERED 
(
	[idxid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
ALTER TABLE [dbo].[BitacoraTurnos]  WITH CHECK ADD  CONSTRAINT [FK_BitacoraTurnos_turnos_empleados] FOREIGN KEY([idTurno])
REFERENCES [dbo].[turnos_empleados] ([id_turno])
GO
ALTER TABLE [dbo].[BitacoraTurnos] CHECK CONSTRAINT [FK_BitacoraTurnos_turnos_empleados]
GO
