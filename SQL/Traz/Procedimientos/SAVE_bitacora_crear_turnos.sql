SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Pedro Marciales>
-- Create date: <Create Date,,22 de agosto de 2024>
-- Description:	<Description,,Busca si que no exista un turno con los mismos horario y que no tenga actividades repetidas>
-- =============================================
CREATE PROCEDURE [dbo].[SAVE_bitacora_crear_turnos]
    @idturno UNIQUEIDENTIFIER,
    @fecha_ini TIME,
    @fecha_fin TIME,
    @idactividad UNIQUEIDENTIFIER,
    @idusuario UNIQUEIDENTIFIER
--@retorna UNIQUEIDENTIFIER output
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @f_ini VARCHAR(5)
    DECLARE @f_final VARCHAR(5)
    DECLARE @encontro_turno INT =0
    DECLARE @encontro INT =0
    DECLARE @fecha DATETIME = getdate()
    DECLARE @idxid UNIQUEIDENTIFIER
    SET @f_ini=  CONVERT(CHAR(5),  @fecha_ini, 108)
    SET @f_final=  CONVERT(CHAR(5),  @fecha_fin, 108)

    SELECT @encontro = count(*)
    FROM bitacora_horarios
    WHERE idactividad=@idactividad AND hora_inicio=@fecha_ini AND hora_fin = @fecha_fin AND id_turno= @idturno

    IF @encontro=0
	BEGIN
        SELECT @encontro_turno=count(*)
        FROM turnos_empleados
        WHERE id_turno = @idturno
        IF @encontro_turno = 0
		BEGIN
            INSERT INTO turnos_empleados
                (id_turno, descripcion)
            VALUES
                (@idturno, CONCAT('Turno (',@f_ini,' - ',@f_final,')'))
        END
        SET @idxid = NEWID()
        INSERT INTO bitacora_horarios
            (idxid, hora_inicio, hora_fin, id_turno,idactividad, fecharegistro, idusuario)
        VALUES
            (@idxid, @fecha_ini, @fecha_fin, @idturno, @idactividad, @fecha, @idusuario)
    END

END
GO
