SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Pedro Marciales>
-- Create date: <Create Date,,04 de Septiembre de 2024>
-- Description:	<Description,,Agregar un turno con en un centro de trabajo con un usuarios y dias
-- =============================================
CREATE PROCEDURE [dbo].[SAVE_bitacora_asignar_turnos]
    @idturno UNIQUEIDENTIFIER,
    @fecha_ini DATE,
    @fecha_fin DATE,
    @dias AS VARCHAR(100),
    @iduser_login UNIQUEIDENTIFIER,
    @idusuario UNIQUEIDENTIFIER,
    @centrotrabajo UNIQUEIDENTIFIER
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @diario VARCHAR(60)
    DECLARE @encontro INT =0
    DECLARE @fecha_temp_fin DATE
    DECLARE @fecha_temp_ini DATE
    DECLARE @idxid UNIQUEIDENTIFIER

    DECLARE TB CURSOR FOR
		SELECT tvalue
    FROM string_split_to_table(@dias, ',')

    OPEN TB
    FETCH NEXT
	FROM TB 
	INTO @diario

    WHILE @@FETCH_STATUS = 0
	BEGIN
        SELECT @encontro = count(*)
        FROM BitacoraTurnos
        WHERE idUsuario=@idusuario AND dias= @diario AND (((FechaFin>=cast(GETDATE() AS DATE) OR FechaFin='1900-01-01')))
        --idUsuario=@idusuario and dias= @diario and FechaInicio <= @fecha_ini and ((FechaFin<=@fecha_fin) OR (FechaFin>=@fecha_fin))
        IF @encontro>0
		BEGIN
            SELECT @fecha_temp_ini=FechaInicio, @fecha_temp_fin=FechaFin, @idxid=idxid
            FROM BitacoraTurnos
            WHERE idUsuario=@idusuario AND dias=@diario AND (((FechaFin>=cast(GETDATE() AS DATE) OR FechaFin='1900-01-01')))
            IF @fecha_temp_ini<>@fecha_ini
			BEGIN
                IF @fecha_temp_fin='1900-01-01' OR @fecha_ini<=@fecha_temp_fin
				BEGIN
                    UPDATE BitacoraTurnos SET FechaFin = DATEADD(DAY,-1,@fecha_ini) WHERE idxid= @idxid
                --insert into BitacoraTurnos (idxid,idUsuario,FechaInicio, FechaFin, idTurno, dias, idCentroTrabajo)
                --	values(NEWID(), @idusuario,@fecha_ini, @fecha_fin,@idturno,@diario,@centrotrabajo)
                END
                /*else if @fecha_ini<=@fecha_temp_fin
					update BitacoraTurnos set FechaFin = DATEADD(DAY,-1,@fecha_ini) where idxid= @idxid   */

                INSERT INTO BitacoraTurnos
                    (idxid,idUsuario,FechaInicio, FechaFin, idTurno, dias, idCentroTrabajo)
                VALUES(NEWID(), @idusuario, @fecha_ini, @fecha_fin, @idturno, @diario, @centrotrabajo)
            END
        END 
		ELSE
			INSERT INTO BitacoraTurnos
            (idxid,idUsuario,FechaInicio, FechaFin, idTurno, dias, idCentroTrabajo)
        VALUES(NEWID(), @idusuario, @fecha_ini, @fecha_fin, @idturno, @diario, @centrotrabajo)

        FETCH NEXT
		FROM TB
		INTO @diario
    END

    CLOSE TB
    DEALLOCATE TB

END

GO
