SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[Jornada_Bitacora_Detalle]
(
    [id_Bitacora] [UNIQUEIDENTIFIER] NULL,
    [FechaInicial] [DATETIME] NULL,
    [FechaFinal] [DATETIME] NULL,
    [idRegla] [UNIQUEIDENTIFIER] NULL,
    [valor] [MONEY] NULL,
    [idxid] [UNIQUEIDENTIFIER] NULL
) ON [PRIMARY]
GO
