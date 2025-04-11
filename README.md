# Documentación del Proyecto: Jornada Bitácora

## Descripción General

Este proyecto permite la gestión y consulta de jornadas laborales, sueldos, turnos y horas extras de los empleados. La aplicación está dividida en varios módulos accesibles a través de un menú de navegación.

## Módulos

### 1. Registro

Permite registrar las jornadas laborales de los empleados.

**Funcionalidades:**

- Usuario: Selección del usuario
- Tiquete: Selección del tiquete asociado
- Horas Tiquete: Visualización de las horas del tiquete
- Horas a Distribuir: Visualización de las horas a distribuir
- Centro de Trabajo: Selección del centro de trabajo
- Actividades: Selección de la actividad realizada
- Unidad de Negocio: Selección de la unidad de negocio
- Descripción: Descripción de la jornada
- Horas y Minutos: Distribución de horas y minutos trabajados

**Componentes:**

- _Tablas:_ Jornada_Bitacora, Jornada_Bitacora_Detalle, UsuariosDetalle, Destino, Actividades, Jornada_Bitacora_UnidadNegocio
- _Procedimientos:_ SAVE_JornadaBitacora
- _Vistas:_ vJornadaBitacora
- _Funciones:_ dbo.fnObtenerHorasTrabajadas

**Acciones:**

- Guardar: Guarda la jornada registrada
- Cancelar: Cancela la operación actual

### 2. Sueldo

Permite gestionar los sueldos de los empleados.

**Funcionalidades:**

- Usuario: Selección del usuario
- Sueldo: Introducción del sueldo del usuario
- Fecha Inicio: Selección de la fecha de inicio del sueldo

**Componentes:**

- _Tablas:_ Usuarios_sueldos, Usuarios
- _Procedimientos:_ SAVE_UsuarioSueldos, DELETE_Usuario_Sueldos
- _Vistas:_ vUsuarios_sueldos
- _Funciones:_ dbo.Get_sueldo_usuario

### 3. Consulta

Permite realizar consultas de las jornadas laborales por diferentes criterios.

#### 3.1 Por Mina

Permite consultar las jornadas laborales filtradas por mina.

**Funcionalidades:**

- Fecha Inicio y Fin: Selección del rango de fechas
- Unidad de Negocio: Selección de la unidad de negocio
- Usuario: Selección del usuario
- Centro de Trabajo: Selección del centro de trabajo

**Componentes:**

- _Tablas y Vistas:_ vJornadaBitacora, UsuariosDetalle, Destino, Actividades, Jornada_Bitacora_UnidadNegocio
- _Procedimientos:_ GET_AplicaFechaBitacora
- _Funciones:_ dbo.Get_horas_extras, dbo.Get_sueldo_usuario

**Acciones:**

- Buscar: Realiza la consulta basada en los criterios seleccionados

#### 3.2 Por Empresa

Permite consultar las jornadas laborales filtradas por empresa.

**Funcionalidades:**

- Fecha Inicio y Fin: Selección del rango de fechas
- Unidad de Negocio: Selección de la unidad de negocio
- Usuario: Selección del usuario
- Centro de Trabajo: Selección del centro de trabajo

**Componentes:**

- _Tablas y Vistas:_ vJornadaBitacora, UsuariosDetalle, Destino, Actividades, Jornada_Bitacora_UnidadNegocio
- _Procedimientos:_ GET_AplicaFechaBitacora
- _Funciones:_ dbo.Get_horas_extras, dbo.Get_sueldo_usuario

**Acciones:**

- Buscar: Realiza la consulta basada en los criterios seleccionados

#### 3.3 Por Actividad

Permite consultar las jornadas laborales filtradas por actividad.

**Funcionalidades:**

- Fecha Inicio y Fin: Selección del rango de fechas
- Unidad de Negocio: Selección de la unidad de negocio
- Usuario: Selección del usuario
- Actividad: Selección de la actividad
- Centro de Trabajo: Selección del centro de trabajo

**Componentes:**

- _Tablas y Vistas:_ vJornadaBitacora, UsuariosDetalle, Destino, Actividades, Jornada_Bitacora_UnidadNegocio
- _Procedimientos:_ GET_AplicaFechaBitacora
- _Funciones:_ dbo.Get_horas_extras, dbo.Get_sueldo_usuario

**Acciones:**

- Buscar: Realiza la consulta basada en los criterios seleccionados

#### 3.4 Por Reglas

Permite consultar las jornadas laborales filtradas por reglas específicas.

**Funcionalidades:**

- Fecha Inicio y Fin: Selección del rango de fechas
- Unidad de Negocio: Selección de la unidad de negocio
- Usuario: Selección del usuario
- Actividad: Selección de la actividad
- Centro de Trabajo: Selección del centro de trabajo

**Componentes:**

- _Tablas y Vistas:_ vJornadaBitacora, UsuariosDetalle, Destino, Actividades, Jornada_Bitacora_UnidadNegocio
- _Procedimientos:_ GET_AplicaFechaBitacora
- _Funciones:_ dbo.Get_horas_extras, dbo.Get_sueldo_usuario

**Acciones:**

- Buscar: Realiza la consulta basada en los criterios seleccionados

### 4. Horas Extras

Permite consultar las horas extras trabajadas.

**Componentes:**

- _Tablas:_ vJornadaBitacora, UsuariosDetalle
- _Procedimientos:_ GET_AplicaFechaBitacora
- _Funciones:_ dbo.Get_horas_extras

### 5. Turnos

Gestión de turnos de empleados.

**Submódulos:**

- Crear Turnos
- Asignar Turnos

**Componentes:**

- _Tablas:_ turnos_empleados, BitacoraTurnos, bitacora_horarios
- _Procedimientos:_ SAVE_bitacora_crear_turnos, SAVE_bitacora_asignar_turnos
- _Vistas:_ vBitacoraTurnos

## Tecnologías Utilizadas

### Scripts y Librerías

- jQuery
- Bootstrap
- AlertifyJS
- DataTables
- SweetAlert
- Multiple Select

### Requisitos

- PHP
- SQL Server
- Librerías mencionadas

## Instalación

1. Clonar repositorio
2. Configurar conexión DB
3. Instalar librerías
4. Ejecutar en servidor PHP

## Elementos del Sistema

### Base de Datos

**Tablas Principales:**

- UsuariosDetalle
- Usuarios
- Destino
- Jornada_Bitacora
- BitacoraTurnos

**Vistas:**

- vJornadaBitacora
- vBitacoraTurnos
- vUsuarios_sueldos

### SQL Mina Dinastia

**Tablas Específicas:**

- SubGrupos
- UsuarioGrupo
- UsuariosBiometrico
- DestinoBiometricos
- bitacoraV2

**Funciones:**

- dbo.fnObtenerHorasTrabajadas

**Vistas:**

- vDestinosUnidos

## Documentación de la Tabla y Procedimiento

### Tabla: bitacora

La tabla `bitacora` se utiliza para registrar los accesos de los usuarios. A continuación, se detallan sus columnas y su propósito:

- **access_date:** Fecha de acceso.
- **id:** Identificación del usuario.
- **date_time:** Fecha y hora del acceso.
- **access_time:** Hora del acceso.
- **nombres:** Nombres del usuario.
- **apellidos:** Apellidos del usuario.
- **Estado:** Estado del acceso.
- **dispositivo:** Dispositivo utilizado para el acceso.
- **año:** Año del acceso, calculado automáticamente.
- **consecutivo:** Número consecutivo del acceso.
- **Estado_real:** Estado real del acceso.
- **marcado:** Indicador de si el acceso ha sido marcado.
- **Tipo:** Tipo de acceso.
- **tipo_registro:** Tipo de registro (0 = automático, 1 = manual).

Además, la tabla tiene un trigger llamado `Consecutivos` que se activa después de una inserción para asignar un número consecutivo al acceso.

### Procedimiento: asig_consecutivo

El procedimiento `asig_consecutivo` se encarga de asignar un número consecutivo a los registros de acceso en la tabla `bitacora`. A continuación, se detalla su funcionamiento:

#### Parámetro de Entrada:

- **@id:** Identificación del usuario.

#### Variables Declaradas:

- **@consecutivo:** Número consecutivo.
- **@tiempo:** Fecha y hora del acceso.
- **@hora_difere:** Diferencia en horas entre dos accesos.
- **@hora_parcial:** Hora parcial del acceso.
- **@hora_ultima:** Última hora del acceso.
- **@estado:** Estado del acceso.
- **@tipoEmpl:** Tipo de empleado.
- **@marcado:** Indicador de si el acceso ha sido marcado.

#### Lógica del Procedimiento:

1. Se obtiene la última hora de acceso del usuario.
2. Se calcula la diferencia en horas entre dos accesos.
3. Se determina el tipo de empleado y se marca el acceso si es necesario.
4. Se asigna un número consecutivo al acceso.
5. Se actualiza el estado real del acceso y se marca como entrada o salida según corresponda.
6. Se registran los cambios en la tabla `logs` para auditoría.

### Tablas Utilizadas

- **bitacora:** Tabla principal donde se registran los accesos.
- **UsuariosEmpresa:** Tabla que relaciona a los usuarios con las empresas.
- **UsuariosDetalle:** Tabla que contiene los detalles de los usuarios.
- **logs:** Tabla utilizada para registrar logs de auditoría.

### Funciones y Procedimientos Utilizados

- **Procedimiento `asig_consecutivo`:** Asigna un número consecutivo a los registros de acceso.
- **Trigger `Consecutivos`:** Se activa después de una inserción en la tabla `bitacora` para ejecutar el procedimiento `asig_consecutivo`.

  ## Tareas a Realizar

  ### Primera Tarea: Gestión de Turnos

  - **Eliminar turno:**
    - El turno solo se podrá eliminar cuando no esté asignado a ningún empleado
  - **Actualizar turno:**
    - Solo se permitirá actualizar cuando el turno no esté asignado a ningún empleado

  ### Segunda Tarea: Gestión de Asignaciones

  - **Eliminar asignación de turnos:**
    - Solo se podrá eliminar cuando:
      - El empleado no cuente con un registro en la tabla bitácora que se encuentre dentro del rango de fechas con el que se programó la asignación de turnos
      - La fecha de inicio del rango de fechas con las que se programó la asignación de turnos sea mayor o igual a la fecha actual
  - **Modificar asignación de turnos:**
    - Solo se podrá modificar cuando:
      - La fecha de cierre de corte de novedades sea mayor o diferente a la fecha actual
      - El rango de fechas del turno a modificar este entre el inicio y cierre de novedades del mes actual
