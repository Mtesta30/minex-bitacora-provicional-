SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE PROCEDURE [dbo].[Save_bitacora_turnos]
    -- Add the parameters for the stored procedure here
    @idxid UNIQUEIDENTIFIER,
    @usuario UNIQUEIDENTIFIER,
    @horasTurno MONEY,
    @fechaInicio DATE

AS
BEGIN
    SET NOCOUNT ON;
    IF @idxid = '00000000-0000-0000-0000-000000000000' OR @idxid IS NULL
	BEGIN
        SET @idxid = NEWID()
        -- Insert statements for procedure here
        INSERT INTO BitacoraTurnos
            (idxid, idUsuario, HoraTurno, FechaInicio)
        VALUES
            (@idxid, @usuario, @horasTurno, @fechaInicio)

    END
	ELSE
	BEGIN
        UPDATE BitacoraTurnos SET  idUsuario = @usuario, 
		HoraTurno = @horasTurno, FechaInicio = @fechaInicio WHERE idxid = @idxid
    END

END
GO
