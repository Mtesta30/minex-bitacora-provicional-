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
