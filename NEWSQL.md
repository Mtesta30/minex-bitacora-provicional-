# Documentación del Sistema Biométrico

## Tablas

### UsuariosBiometrico

**Descripción**: Almacena información de los usuarios biométricos.

#### Columnas:

- `idUsuario`: Identificador único del usuario
- `TipoDocumento`: Tipo de documento del usuario
- `Identificacion`: Número de identificación del usuario
- `NombreCompleto`: Nombre completo del usuario
- `Identificador`: Identificador adicional del usuario

#### Índices:

- `IX_UsuariosBiometrico_Identificacion`: Índice no clusterizado para búsquedas por identificación

### Biometricos

### Biometricos

**Descripción**: Almacena información de los dispositivos biométricos.

#### Columnas:

- `idBiometrico`: Identificador único del dispositivo biométrico
- `idCentroTrabajo`: Identificador del centro de trabajo asociado
- `identificadorBiometrico`: Identificador del dispositivo biométrico
- `nombreDispositivo`: Nombre del dispositivo biométrico

#### Índices:

- `IX_Biometricos_identificadorBiometrico`: Índice no clusterizado para búsquedas por identificador biométrico

#### Llaves Foráneas:

- `FK_Biometricos_Destino`: Relación con la tabla Destino

### Bitacora

**Descripción**: Almacena los registros de marcaciones biométricas.

#### Columnas:

- `idBitacora`: Identificador único del registro
- `idBiometrico`: Identificador del dispositivo biométrico
- `Identificacion`: Número de identificación del usuario
- `NombreCompleto`: Nombre completo del usuario
- `FechaHora`: Fecha y hora de la marcación
- `tipoMarcacion`: Tipo de marcación (Entrada, Salida, etc.)
- `idProgramacionDetalle`: Identificador del detalle de programación
- `idTurno`: Identificador del turno
- `observacion`: Observaciones adicionales

### Turnos

**Descripción**: Almacena información de los turnos de trabajo.

#### Columnas:

- `idTurno`: Identificador único del turno
- `descripcion`: Descripción del turno
- `horaInicio`: Hora de inicio del turno
- `horaFin`: Hora de fin del turno
- `duracionHoras`: Duración del turno en horas
- `activo`: Indicador de si el turno está activo

### ProgramacionTurnos

**Descripción**: Almacena la programación de turnos de los usuarios.

#### Columnas:

- `idProgramacion`: Identificador único de la programación
- `idUsuario`: Identificador del usuario
- `idCentroTrabajo`: Identificador del centro de trabajo
- `fechaInicio`: Fecha de inicio de la programación
- `fechaFin`: Fecha de fin de la programación
- `fechaRegistro`: Fecha de registro de la programación
- `idUsuarioRegistra`: Identificador del usuario que registra la programación
- `activo`: Indicador de si la programación está activa

#### Llaves Foráneas:

- `FK_ProgramacionTurnos_UsuariosBiometrico`: Relación con la tabla UsuariosBiometrico
- `FK_ProgramacionTurnos_Destino`: Relación con la tabla Destino

### ProgramacionTurnosDetalle

**Descripción**: Almacena los detalles de la programación de turnos.

#### Columnas:

- `idProgramacionDetalle`: Identificador único del detalle de programación
- `idProgramacion`: Identificador de la programación
- `fecha`: Fecha del detalle de programación
- `idTurno`: Identificador del turno

#### Llaves Foráneas:

- `FK_ProgramacionTurnosDetalle_ProgramacionTurnos`: Relación con la tabla ProgramacionTurnos
- `FK_ProgramacionTurnosDetalle_Turnos`: Relación con la tabla Turnos

## Vistas

### vUsuariosAppBiometrico

**Descripción**: Vista que combina los usuarios de las tablas UsuariosBiometrico y UsuariosDetalle.

#### Columnas:

- `idUsuario`: Identificador del usuario
- `NombreCompleto`: Nombre completo del usuario
- `Identificacion`: Número de identificación del usuario
- `Origen`: Origen del usuario (Biométrico o Trazapp)

## Procedimientos Almacenados

### ValidarUsuarioBiometrico

**Descripción**: Valida la existencia de un usuario biométrico y lo crea si no existe.

#### Parámetros:

- `@identificacion`: Número de identificación del usuario
- `@nombreCompleto`: Nombre completo del usuario

### ValidarBiometrico

**Descripción**: Valida la existencia de un dispositivo biométrico y asigna un ID genérico si no existe.

#### Parámetros:

- `@identificadorBiometrico`: Identificador del dispositivo biométrico
- `@idBiometrico`: Identificador único del dispositivo biométrico (OUTPUT)

### ProcesarMarcacionBiometrica

**Descripción**: Procesa una marcación biométrica para determinar su tipo.

#### Parámetros:

- `@identificacion`: Número de identificación del usuario
- `@fechaHora`: Fecha y hora de la marcación
- `@idBiometrico`: Identificador del dispositivo biométrico
- `@tipoMarcacion`: Tipo de marcación (OUTPUT)
- `@observacion`: Observaciones adicionales (OUTPUT)

### AsociarMarcacionConTurno

**Descripción**: Asocia una marcación con el turno programado.

#### Parámetros:

- `@Identificacion`: Número de identificación del usuario
- `@FechaHora`: Fecha y hora de la marcación
- `@idProgramacionDetalle`: Identificador del detalle de programación (OUTPUT)
- `@idTurno`: Identificador del turno (OUTPUT)

### SAVE_Bitacora

**Descripción**: Procedimiento principal para guardar marcaciones en la bitácora.

#### Parámetros:

- `@identificadorBiometrico`: Identificador del dispositivo biométrico
- `@Identificacion`: Número de identificación del usuario
- `@NombreCompleto`: Nombre completo del usuario
- `@FechaHora`: Fecha y hora de la marcación

### SAVE_ProgramacionTurnos

**Descripción**: Guarda la programación de turnos para un usuario.

#### Parámetros:

- `@idUsuario`: Identificador del usuario
- `@idCentroTrabajo`: Identificador del centro de trabajo
- `@fechaInicio`: Fecha de inicio de la programación
- `@fechaFin`: Fecha de fin de la programación
- `@idUsuarioRegistra`: Identificador del usuario que registra
- `@idTurnoLunes`: Identificador del turno del lunes (opcional)
- `@idTurnoMartes`: Identificador del turno del martes (opcional)
- `@idTurnoMiercoles`: Identificador del turno del miércoles (opcional)
- `@idTurnoJueves`: Identificador del turno del jueves (opcional)
- `@idTurnoViernes`: Identificador del turno del viernes (opcional)
- `@idTurnoSabado`: Identificador del turno del sábado (opcional)
- `@idTurnoDomingo`: Identificador del turno del domingo (opcional)
- `@idTurnoDefault`: Identificador del turno por defecto (opcional)

### ActualizarMarcacionesSinTurno

**Descripción**: Actualiza las marcaciones sin turno asignado.

#### Parámetros:

- `@idUsuario`: Identificador del usuario
- `@fechaInicio`: Fecha de inicio del rango
- `@fechaFin`: Fecha de fin del rango

## Triggers

### TRG_ActualizarMarcacionesSinTurno

**Descripción**: Trigger que se ejecuta después de insertar en la tabla ProgramacionTurnos para actualizar las marcaciones sin turno asignado.

**Eventos**: AFTER INSERT
