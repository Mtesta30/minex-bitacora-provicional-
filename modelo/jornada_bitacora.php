<?php
// require_once '../../conectar.php';
require_once dirname(__DIR__) . '/conectartraz.php';
require_once dirname(__DIR__) . '/clase_encrip.php';

$funcionesExterna = dirname(dirname(__DIR__)) . '/flujos/funciones.php';
if (file_exists($funcionesExterna)) {
    require_once $funcionesExterna;
} elseif (file_exists(__DIR__ . '/funciones_local.php')) {
    require_once __DIR__ . '/funciones_local.php';
} elseif (!class_exists('FUNCIONES')) {
    class FUNCIONES
    {
        public static function BuscarpermisoDetalle($conn, $idUsuario, $permiso, $campo)
        {
            $consultas = array(
                "SELECT idDestino FROM dbo.Destino WHERE Habilitado = 1",
                "SELECT idDestino FROM dbo.Destino WHERE Activo = 1",
                "SELECT idDestino FROM dbo.Destino"
            );

            foreach ($consultas as $sql) {
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt === false) {
                    continue;
                }

                $destinos = array();
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    if (!empty($row['idDestino'])) {
                        $destinos[] = $row['idDestino'];
                    }
                }

                if (!empty($destinos)) {
                    return $destinos;
                }
            }

            return array();
        }
    }
}

$post_data = file_get_contents('php://input');
$list_record = json_decode($post_data, true);
$params = array();
$options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
$json = '';
$respuestaJson = isset($_GET['band']);
$jsonResponseSent = false;

if ($respuestaJson) {
    ob_start();
    ini_set('display_errors', '0');
    ini_set('html_errors', '0');
    header('Content-Type: application/json; charset=UTF-8');

    register_shutdown_function(function () {
        global $json, $respuestaJson, $jsonResponseSent;

        if (!$respuestaJson || $jsonResponseSent) {
            return;
        }

        $error = error_get_last();
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }

            echo json_encode(array(
                'success' => false,
                'message' => 'Error interno al procesar la solicitud'
            ));
            $jsonResponseSent = true;
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }

        echo ($json !== '' && $json !== false) ? $json : json_encode(array(
            'success' => false,
            'message' => 'No se pudo generar una respuesta valida'
        ));
        $jsonResponseSent = true;
    });
}

function log_debug($mensaje, $datos = null)
{
    $archivo_log = __DIR__ . '/debug_reportes.log';
    $tiempo = date('Y-m-d H:i:s');
    $log_mensaje = "[$tiempo] $mensaje";

    if ($datos !== null) {
        if (is_array($datos) || is_object($datos)) {
            $log_mensaje .= "\nDatos: " . print_r($datos, true);
        } else {
            $log_mensaje .= "\nDatos: $datos";
        }
    }

    $log_mensaje .= "\n" . str_repeat('-', 80) . "\n";
    file_put_contents($archivo_log, $log_mensaje, FILE_APPEND);
}

function respuestaExito($data, $message = 'Operación realizada correctamente')
{
    $flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
    return json_encode(array(
        'success' => true,
        'message' => $message,
        'data' => $data
    ), $flags);
}

function respuestaError($message, $errors = null)
{
    $response = array(
        'success' => false,
        'message' => $message
    );
    if ($errors) {
        $response['errors'] = $errors;
    }
    $flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
    return json_encode($response, $flags);
}

function convertirMinutosHora($time)
{
    if (!$time) {
        return 0;
    }
    $parts = explode(':', substr($time, 0, 5));
    $horas = isset($parts[0]) ? intval($parts[0]) : 0;
    $minutos = isset($parts[1]) ? intval($parts[1]) : 0;
    return ($horas * 60) + $minutos;
}

function construirIntervaloHoras($inicio, $fin)
{
    $start = convertirMinutosHora($inicio);
    $end = convertirMinutosHora($fin);
    if ($end <= $start) {
        $end += 24 * 60;
    }
    return array($start, $end);
}

function intervalosSolapan($intervaloA, $intervaloB)
{
    return max($intervaloA[0], $intervaloB[0]) < min($intervaloA[1], $intervaloB[1]);
}

function esGuidValido($valor)
{
    return is_string($valor) && preg_match('/^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$/', $valor);
}

function resolverIdentificadorEntrada($valor)
{
    if (esGuidValido($valor)) {
        return $valor;
    }

    $desencriptado = ENCR::descript($valor);
    if (esGuidValido($desencriptado)) {
        return $desencriptado;
    }

    return null;
}

function sanitizarTotal($valor)
{
    if ($valor === null) return null;
    // Si es un objeto DateTime (SQL time devuelto por sqlsrv), formatearlo
    if ($valor instanceof DateTime || $valor instanceof DateTimeImmutable) {
        return $valor->format('H:i:s');
    }
    // Si es numérico, rechazar negativos o valores absurdos (>24 hrs o >1440 min)
    if (is_numeric($valor)) {
        $num = floatval($valor);
        if ($num < 0 || $num > 1440) return null;
        return $num;
    }
    return $valor;
}

function normalizarTextoSalida($valor)
{
    if ($valor === null) {
        return '';
    }

    if (!is_string($valor)) {
        return $valor;
    }

    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($valor, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }

    if (function_exists('iconv')) {
        $convertido = @iconv('Windows-1252', 'UTF-8//IGNORE', $valor);
        if ($convertido !== false) {
            return $convertido;
        }
    }

    return $valor;
}

/**
Codigo hecho por mario
Funcion: existeObjetoSql valida si una tabla, vista o procedimiento existe en
la base local. Se usa para que el backend no falle cuando la instancia local
todavia no tiene todos los objetos del ambiente original.
**/
function existeObjetoSql($conn, $nombre, $tipo = null)
{
    $sql = "SELECT 1 AS existe WHERE OBJECT_ID(?, ?) IS NOT NULL";
    $params = array($nombre, $tipo);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return false;
    }

    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) !== null;
}

/**
Codigo hecho por mario
Funcion: resolverNombreObjetoSql busca el nombre real del objeto en la base
actual para priorizar siempre la estructura de produccion y evitar depender de
variantes locales de nombre.
**/
function resolverNombreObjetoSql($conn, array $candidatos, array $tipos = array())
{
    foreach ($candidatos as $nombre) {
        foreach ($tipos as $tipo) {
            if (existeObjetoSql($conn, $nombre, $tipo)) {
                return $nombre;
            }
        }
    }

    return null;
}

/**
Codigo hecho por mario
Funcion: obtenerCentrosTrabajoPermitidos intenta resolver los centros del
usuario por permisos reales y, si no existen en local, usa Destino como
fallback para no bloquear pruebas ni listados del modulo de turnos.
**/
function obtenerCentrosTrabajoPermitidos($conn, $idUsuario, $permiso = 'CONSULTA_POR_CENTRO_DE_TRABAJO', $campo = 'DespachadoDesde')
{
    $centrosTrabajo = FUNCIONES::BuscarpermisoDetalle($conn, $idUsuario, $permiso, $campo);
    if (!empty($centrosTrabajo)) {
        return $centrosTrabajo;
    }

    $consultas = array(
        "SELECT idDestino FROM dbo.Destino WHERE Habilitado = 1",
        "SELECT idDestino FROM dbo.Destino WHERE Activo = 1",
        "SELECT idDestino FROM dbo.Destino"
    );

    foreach ($consultas as $sql) {
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            continue;
        }

        $destinos = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!empty($row['idDestino'])) {
                $destinos[] = $row['idDestino'];
            }
        }

        if (!empty($destinos)) {
            return $destinos;
        }
    }

    return array();
}


/**
Codigo hecho por mario
Funcion: obtenerSqlFuenteUsuarios construye una fuente SQL compatible para
consultar usuarios desde vistas nuevas o desde tablas base, segun lo que exista
realmente en la base local.
**/
function obtenerSqlFuenteUsuarios($conn, $alias = 'U')
{
    $vistaUsuarios = resolverNombreObjetoSql($conn, array('dbo.vUsuariosAppBiometrico'), array('V'));
    if ($vistaUsuarios) {
        return "(SELECT idUsuario, NombreCompleto, Identificacion, Cargo FROM {$vistaUsuarios}) {$alias}";
    }

    $partes = array();

    if (existeObjetoSql($conn, 'dbo.UsuariosDetalle', 'U')) {
        if (existeObjetoSql($conn, 'dbo.Usuarios', 'U')) {
            $partes[] = "SELECT ud.idUsuario, ud.NombreCompleto, ud.Identificacion, ud.Cargo
                         FROM dbo.UsuariosDetalle ud
                         INNER JOIN dbo.Usuarios u ON ud.idUsuario = u.idUsuario
                         WHERE ISNULL(u.Habilitado, 1) = 1";
        } else {
            $partes[] = "SELECT ud.idUsuario, ud.NombreCompleto, ud.Identificacion, ud.Cargo
                         FROM dbo.UsuariosDetalle ud";
        }
    }

    $tablaUsuariosBiometrico = resolverNombreObjetoSql($conn, array('dbo.UsuariosBiometrico'), array('U'));
    if ($tablaUsuariosBiometrico) {
        $cargoExpr = existeColumnaSql($conn, $tablaUsuariosBiometrico, 'Cargo')
            ? 'ub.Cargo'
            : 'CAST(NULL AS VARCHAR(100))';
        $habilitadoExpr = existeColumnaSql($conn, $tablaUsuariosBiometrico, 'Habilitado')
            ? 'WHERE ISNULL(ub.Habilitado, 1) = 1'
            : '';

        $partes[] = "SELECT ub.idUsuario, ub.NombreCompleto, ub.Identificacion, {$cargoExpr} AS Cargo
                     FROM {$tablaUsuariosBiometrico} ub
                     {$habilitadoExpr}";
    }

    if (empty($partes)) {
        return "(SELECT CAST(NULL AS UNIQUEIDENTIFIER) AS idUsuario,
                        CAST(NULL AS VARCHAR(100)) AS NombreCompleto,
                        CAST(NULL AS VARCHAR(20)) AS Identificacion,
                        CAST(NULL AS VARCHAR(100)) AS Cargo
                 WHERE 1 = 0) {$alias}";
    }

    return '(' . implode(' UNION ', $partes) . ") {$alias}";
}


if (isset($_GET['band'])) {

    // $json = '';

    if ($_GET['band'] == 'get_ConsultaCentroTrabajo') {
        try {
            $centroTrabajo = isset($list_record['idCentroTrabajo']) ? resolverIdentificadorEntrada($list_record['idCentroTrabajo']) : null;
            $fechaInicial = $list_record['fechaInicial'];
            $fechaFinal = $list_record['fechaFinal'];
            $cargo = $list_record['cargo'];
            $usuario = isset($list_record['usuario']) ? ENCR::descript($list_record['usuario']) : null;
            $idUsuario = isset($list_record['idUsuario']) ? $list_record['idUsuario'] : '';
            $textoCentro = isset($list_record['texto_Centro']) ? trim($list_record['texto_Centro']) : '';

            // 2. Obtener centros de trabajo permitidos
            $centrosTrabajo = obtenerCentrosTrabajoPermitidos($conn, $idUsuario, 'CONSULTA_POR_CENTRO_DE_TRABAJO', 'DespachadoDesde');

            // 3. Validar si tiene centros asignados
            if (empty($centrosTrabajo)) {
                $json = respuestaError('Usuario no tiene centros de trabajo asignados');
                return;
            }

            // 4. Validar si el centro de trabajo seleccionado está entre los permitidos
            if (!is_null($centroTrabajo) && !empty($centroTrabajo)) {
                if (!in_array($centroTrabajo, $centrosTrabajo)) {
                    $json = respuestaError('No tiene permisos para consultar este centro de trabajo');
                    return;
                }
            }

            // si el centro de trabajo es dinastia ejecuto la siguiente estructura
            if ($centroTrabajo == 'BF23C474-C49A-498C-8BCE-9DB32D3562F0') {


                $sql = "SELECT * FROM biometrico.dbo.fnObtenerHorasTrabajadas(?, ?, 'bio_calle 13', ?)";
                $params = array($fechaInicial, $fechaFinal, $usuario);
                $res = sqlsrv_query($conn, $sql, $params);

                if ($res === false) {
                    $errors = sqlsrv_errors();
                    $json = respuestaError('Error al ejecutar la consulta: ' . $errors[0]['message']);
                    return;
                }

                // Procesar los resultados en formato simple
                $registros = array();
                while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                    // Adaptamos los campos según la estructura simple
                    $registro = array(
                        'id' => $row['ID'],
                        'nombre' => utf8_encode($row['Nombre']),
                        'apellido' => utf8_encode($row['Apellido']),
                        'fecha' => $row['Fecha']->format('Y-m-d'),
                        'horaEntrada' => $row['HoraEntrada']->format('H:i:s'),
                        'horaSalida' => $row['HoraSalida'] ? $row['HoraSalida']->format('H:i:s') : 'Sin Salida',
                        'horasTrabajadas' => $row['HorasTrabajadas']
                    );

                    $registros[] = $registro;
                }

                // Indicar que los datos están en formato simple
                $json = respuestaExito(
                    array(
                        'totalRegistros' => count($registros),
                        'registros' => $registros,
                        'formatoSimple' => true
                    ),
                    'Reporte de horas trabajadas obtenido correctamente'
                );
            } else {

                $sql = "EXEC [dbo].[GET_ReporteJornadasLaborales]
                @FechaInicio = ?,
                @FechaFin = ?,
                @IdCentroTrabajo = ?,
                @IdUsuario = ?,
                @Cargo = ?";
                $usuario = $usuario ?: null;
                $params = array(
                    $fechaInicial,
                    $fechaFinal,
                    array($centroTrabajo, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_UNIQUEIDENTIFIER),
                    array($usuario,       SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_UNIQUEIDENTIFIER),
                    $cargo
                );
                $res = sqlsrv_query($conn, $sql, $params);

                if ($res === false) {
                    $errors = sqlsrv_errors();
                    $json = respuestaError('Error al ejecutar la consulta: ' . $errors[0]['message']);
                    return;
                }

                // Procesar los resultados en formato JSON
                $registros = array();
                while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                    // Adaptamos los campos según la estructura esperada
                    $registro = array(
                        'fecha' => $row['Fecha']->format('Y-m-d'),
                        'diaSemana' => $row['DiaSemana'],
                        'cargo' => utf8_encode($row['Cargo']),
                        'nombreTrabajador' => utf8_encode($row['NombreTrabajador']),
                        'cedula' => $row['Cedula'],
                        'jornada' => $row['Jornada'],
                        'inicioJornada' => $row['1eraJornada.inicio']->format('H:i:s'),
                        'inicioReceso' => $row['1eraJornada.inicioreceso'] ? $row['1eraJornada.inicioreceso']->format('H:i:s') : null,
                        'totalPrimeraJornada' => sanitizarTotal($row['1eraJornada.total']),
                        'finReceso' => $row['2daJornada.finreceso'] ? $row['2daJornada.finreceso']->format('H:i:s') : null,
                        'finJornada' => $row['2daJornada.final']->format('H:i:s'),
                        'totalSegundaJornada' => sanitizarTotal($row['2daJornada.total']),
                        'tiempoTotalTrabajado' => sanitizarTotal($row['horas.TiempoTotalTrabajado']),
                        'sobretiempo' => sanitizarTotal($row['horas.Sobretiempo'])
                    );

                    $registros[] = $registro;
                }

                // Usar la función de respuesta exitosa
                $json = respuestaExito(
                    array(
                        'totalRegistros' => count($registros),
                        'registros' => $registros,
                        'formatoSimple' => false
                    ),
                    'Reporte de jornadas laborales obtenido correctamente'
                );
            }
        } catch (Exception $e) {
            $json = respuestaError('Error al procesar la consulta: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'get_Cargos') {
        $texto_Cargo = $list_record['texto_Cargo'];
        $sql = "SELECT idSubGrupo, nombreSubGrupo FROM SubGrupos";

        if (!empty($texto_Cargo)) {
            $sql .= " WHERE nombreSubGrupo LIKE '%$texto_Cargo%'";
        }

        $res = sqlsrv_query($conn, $sql);
        $data = [];

        while ($aa = sqlsrv_fetch_array($res)) {
            $idCargo = ENCR::encript($aa['idSubGrupo']);
            $Descripcion = utf8_encode($aa['nombreSubGrupo']);
            $registro = array('id' => $idCargo, 'name' => $Descripcion);
            array_push($data, $registro);
        }
        $json = json_encode($data);
    }

    if ($_GET['band'] == 'get_UsuariosMina') {
        $idSubGrupo = ENCR::descript($list_record['idSubGrupo']);
        $sql = "SELECT ub.idUsuario, NombreCompleto 
                FROM UsuariosBiometrico ub
                INNER JOIN UsuarioGrupo ug ON ub.idUsuario = ug.idUsuario 
                WHERE ug.idSubGrupo = ?";
        $params = array($idSubGrupo);
        $res = sqlsrv_query($conn, $sql, $params);
        $data = [];

        while ($aa = sqlsrv_fetch_array($res)) {
            $idUsuario = ENCR::encript($aa['idUsuario']);
            $Nombre = utf8_encode($aa['NombreCompleto']);
            $registro = array('id' => $idUsuario, 'name' => $Nombre);
            array_push($data, $registro);
        }
        $json = json_encode($data);
    }

    if ($_GET['band'] == 'get_Usuarios') {
        /**
        Codigo hecho por mario
        Funcion: get_Usuarios obtiene usuarios para asignacion de turnos usando
        la vista nueva cuando existe y aplicando fallback a tablas base cuando
        la base local aun no tiene vUsuariosCentroTrabajo.
        **/
        try {
            // 1. Obtener parámetros
            $idUsuario = isset($list_record['idUsuario']) ? $list_record['idUsuario'] : '';
            $texto_busqueda = isset($list_record['texto']) ? $list_record['texto'] : '';

            // 2. Obtener centros de trabajo permitidos
            $centrosTrabajo = obtenerCentrosTrabajoPermitidos($conn, $idUsuario, 'CONSULTA_POR_CENTRO_DE_TRABAJO', 'DespachadoDesde');

            // 3. Validar si tiene centros asignados
            if (empty($centrosTrabajo)) {
                $json = respuestaError('Usuario no tiene centros de trabajo asignados');
                return;
            }

            $vistaCentroTrabajo = resolverNombreObjetoSql($conn, array('dbo.vUsuariosCentroTrabajo'), array('V'));
            $tablaUsuariosEmpresa = resolverNombreObjetoSql($conn, array('dbo.UsuariosEmpresa', 'dbo.usuariosempresa'), array('U'));
            $tablaProveedores = resolverNombreObjetoSql($conn, array('dbo.Proveedores', 'dbo.proveedores'), array('U'));

            $usarVistaCentroTrabajo = !empty($vistaCentroTrabajo);
            $usarEmpresas = !empty($tablaUsuariosEmpresa) && !empty($tablaProveedores);

            if ($usarVistaCentroTrabajo) {
                $sql = "SELECT DISTINCT
                        v.idUsuario,
                        v.NombreCompleto,
                        v.Identificacion,
                        v.Cargo,";

                if ($usarEmpresas) {
                    $sql .= "
                        ISNULL(p.RazonSocial, '') AS Empresa
                    FROM {$vistaCentroTrabajo} v
                    LEFT JOIN (
                        SELECT idUsuario, MIN(idEmpresa) AS idEmpresa
                        FROM {$tablaUsuariosEmpresa}
                        GROUP BY idUsuario
                    ) ue ON v.idUsuario = ue.idUsuario
                    LEFT JOIN {$tablaProveedores} p ON ue.idEmpresa = p.idProveedor AND p.empresa = 1";
                } else {
                    $sql .= "
                        '' AS Empresa
                    FROM {$vistaCentroTrabajo} v";
                }

                $sql .= "
                    WHERE v.idCentroTrabajo IN (" . implode(',', array_fill(0, count($centrosTrabajo), '?')) . ")";
            } else {
                $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'u');
                $sql = "SELECT DISTINCT
                        u.idUsuario,
                        u.NombreCompleto,
                        u.Identificacion,
                        u.Cargo,";

                if ($usarEmpresas) {
                    $sql .= "
                        ISNULL(p.RazonSocial, '') AS Empresa
                    FROM {$fuenteUsuarios}
                    LEFT JOIN (
                        SELECT idUsuario, MIN(idEmpresa) AS idEmpresa
                        FROM {$tablaUsuariosEmpresa}
                        GROUP BY idUsuario
                    ) ue ON u.idUsuario = ue.idUsuario
                    LEFT JOIN {$tablaProveedores} p ON ue.idEmpresa = p.idProveedor AND p.empresa = 1
                    WHERE 1 = 1";
                } else {
                    $sql .= "
                        '' AS Empresa
                    FROM {$fuenteUsuarios}
                    WHERE 1 = 1";
                }
            }

            // 5. Agregar filtros de búsqueda si se proporciona texto
            if (!empty($texto_busqueda)) {
                $sql .= " AND (
                " . ($usarVistaCentroTrabajo ? "v" : "u") . ".NombreCompleto LIKE ? OR 
                " . ($usarVistaCentroTrabajo ? "v" : "u") . ".Identificacion LIKE ? OR 
                " . ($usarVistaCentroTrabajo ? "v" : "u") . ".Cargo LIKE ?
            )";
            }

            // 6. Ordenar resultados
            $sql .= $usarVistaCentroTrabajo ? " ORDER BY v.NombreCompleto" : " ORDER BY u.NombreCompleto";

            // 7. Preparar parámetros
            $params = $usarVistaCentroTrabajo ? $centrosTrabajo : array();
            if (!empty($texto_busqueda)) {
                $params = array_merge($params, [
                    '%' . $texto_busqueda . '%',
                    '%' . $texto_busqueda . '%',
                    '%' . $texto_busqueda . '%'
                ]);
            }

            // 8. Ejecutar consulta
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                throw new Exception('Error al ejecutar la consulta: ' . print_r(sqlsrv_errors(), true));
            }

            // 9. Procesar resultados
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $registro = array(
                    'id' => $row['idUsuario'],
                    'nombre' => normalizarTextoSalida($row['NombreCompleto']),
                    'cedula' => $row['Identificacion'],
                    'cargo' => $row['Cargo'],
                    'empresa' => normalizarTextoSalida($row['Empresa'] ?? '')
                );
                array_push($data, $registro);
            }

            // 10. Debug log
            // log_debug("Usuarios recuperados", array(
            //     'cantidad' => count($data),
            //     'centrosTrabajo' => count($centrosTrabajo),
            //     'filtro' => $texto_busqueda
            // ));

            // 11. Retornar respuesta
            $json = respuestaExito($data, 'Usuarios obtenidos correctamente');
        } catch (Exception $e) {
            // log_debug("Error en get_Usuarios", array('error' => $e->getMessage()));
            $json = respuestaError('Error al procesar la consulta: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'get_CentrosDeTrabajo') {
        try {
            $idUsuario = isset($list_record['idUsuario']) ? $list_record['idUsuario'] : '';

            // Obtener array de centros de trabajo permitidos
            $centrosTrabajo = obtenerCentrosTrabajoPermitidos($conn, $idUsuario, 'CONSULTA_POR_CENTRO_DE_TRABAJO', 'DespachadoDesde');

            // Si no hay centros de trabajo asignados, retornar error
            if (empty($centrosTrabajo)) {
                $json = respuestaError('Usuario no tiene centros de trabajo asignados');
                return;
            }

            // Modificar la consulta para incluir múltiples centros de trabajo
            $sql = "SELECT idDestino, Descripcion 
                FROM Destino
                WHERE idDestino IN (" . implode(',', array_fill(0, count($centrosTrabajo), '?')) . ")
                ";

            // Preparar los parámetros con los IDs de centros de trabajo
            if ($textoCentro !== '') {
                $sql .= " AND Descripcion LIKE ?";
            }
            $sql .= " ORDER BY Descripcion";

            $params = $centrosTrabajo;
            if ($textoCentro !== '') {
                $params[] = '%' . $textoCentro . '%';
            }
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                $json = respuestaError('Error al ejecutar la consulta: ' . $errors[0]['message']);
                return;
            }

            $data = [];
            while ($aa = sqlsrv_fetch_array($stmt)) {
                $idDestino = $aa['idDestino'];
                $Descripcion = normalizarTextoSalida($aa['Descripcion']);
                $registro = array('id' => $idDestino, 'name' => $Descripcion);
                array_push($data, $registro);
            }

            // Log para debugging
            /* log_debug("Centros de trabajo recuperados", array(
                'cantidad' => count($data),
                'centrosPermitidos' => count($centrosTrabajo)
            )); */

            $json = respuestaExito($data, 'Centros de trabajo obtenidos correctamente');
        } catch (Exception $e) {
            // log_debug("Error en get_CentrosDeTrabajo", array('error' => $e->getMessage()));
            $json = respuestaError('Error al procesar la consulta: ' . $e->getMessage());
        }
    }


    if ($_GET['band'] == 'save_turno') {
        // Obtener los datos básicos del turno
        $Nombre_turno = $list_record['Nombre_turno'];
        $FechaInicial_turno = $list_record['FechaInicial_turno'];
        $FechaFinal_turno = $list_record['FechaFinal_turno'];
        $Duracion_turno = $list_record['Duracion_turno'];
        $idusuario = $list_record['idusuario']; // ID del usuario que registra

        // Convertir duración de formato HH:MM a decimal para la base de datos
        $partes = explode(':', $Duracion_turno);
        $horas = intval($partes[0]);
        $minutos = intval($partes[1]);
        $duracionHoras = $horas + ($minutos / 60);
        $duracionHorasRedondeada = round($duracionHoras, 2);

        // Verificar datos de descanso
        $incluirDescanso = isset($list_record['incluirDescanso']) && $list_record['incluirDescanso'] ? true : false;
        $inicioDescanso = $incluirDescanso && isset($list_record['inicioDescanso']) ? $list_record['inicioDescanso'] : null;
        $finDescanso = $incluirDescanso && isset($list_record['finDescanso']) ? $list_record['finDescanso'] : null;
        $duracionDescansoMinutos = null;
        $descripcionDescanso = null;

        // Calcular duración del descanso en minutos si está incluido
        if ($incluirDescanso && isset($list_record['duracionDescanso']) && $list_record['duracionDescanso']) {
            $partesDescanso = explode(':', $list_record['duracionDescanso']);
            $horasDescanso = intval($partesDescanso[0]);
            $minutosDescanso = intval($partesDescanso[1]);
            $duracionDescansoMinutos = ($horasDescanso * 60) + $minutosDescanso;
            $descripcionDescanso = isset($list_record['descripcionDescanso']) ? $list_record['descripcionDescanso'] : null;
        }

        try {
            // Preparar y ejecutar el procedimiento almacenado con posibles parámetros adicionales
            if ($incluirDescanso) {
                $sql = "EXEC SAVE_Turnos 
                @descripcion = ?, 
                @horaInicio = ?, 
                @horaFin = ?, 
                @duracionHoras = ?,
                @idUsuario = ?,
                @inicioDescanso = ?,
                @finDescanso = ?,
                @duracionDescansoMinutos = ?,
                @descripcionDescanso = ?";

                $params = array(
                    $Nombre_turno,
                    $FechaInicial_turno,
                    $FechaFinal_turno,
                    $duracionHorasRedondeada,
                    $idusuario,
                    $inicioDescanso,
                    $finDescanso,
                    $duracionDescansoMinutos,
                    $descripcionDescanso
                );
            } else {
                $sql = "EXEC SAVE_Turnos 
                @descripcion = ?, 
                @horaInicio = ?, 
                @horaFin = ?, 
                @duracionHoras = ?,
                @idUsuario = ?";

                $params = array(
                    $Nombre_turno,
                    $FechaInicial_turno,
                    $FechaFinal_turno,
                    $duracionHorasRedondeada,
                    $idusuario
                );
            }

            $stmt = sqlsrv_prepare($conn, $sql, $params);

            if (sqlsrv_execute($stmt)) {
                // Éxito al ejecutar el procedimiento
                $json = respuestaExito(
                    array('idTurno' => sqlsrv_get_field($stmt, 0)),
                    'Turno creado correctamente'
                );
            } else {
                // Error al ejecutar el procedimiento
                $errors = sqlsrv_errors();
                $errorMessage = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error desconocido';

                // Códigos de error específicos según el mensaje
                $errorCode = 0;
                $codigo = 3; // Error general por defecto

                if (strpos($errorMessage, 'duración mínima') !== false) {
                    $errorCode = 1;
                    $codigo = 1; // Error de duración mínima
                } else if (strpos($errorMessage, 'duración proporcionada') !== false) {
                    $errorCode = 2;
                    $codigo = 2; // Error en cálculo de duración
                } else if (strpos($errorMessage, 'período de descanso debe estar dentro') !== false) {
                    $errorCode = 3;
                    $codigo = 3; // Error en período de descanso fuera de rango
                } else if (strpos($errorMessage, 'cruzan la medianoche') !== false) {
                    $errorCode = 4;
                    $codigo = 4; // Error con turno que cruza medianoche
                } else if (strpos($errorMessage, 'Ya existe un turno') !== false) {
                    $errorCode = 5;
                    $codigo = 5; // Error turno duplicado
                }

                if ($errorCode === 5) {
                    $json = respuestaError('Ya existe un turno con ese horario', array('code' => 5));
                } else {
                    $json = respuestaError(
                        'Error al crear turno: ' . $errorMessage,
                        array('code' => $errorCode)
                    );
                }
            }
        } catch (Exception $e) {
            // log_debug("Error en save_turno", array('error' => $e->getMessage()));
            $json = respuestaError('Error interno al procesar el turno: ' . $e->getMessage());
        }
    }

    /**
    Codigo hecho por mario
    Funcion: update_turno_definicion actualiza la definicion del turno y su
    descanso para que el frontend reciba una respuesta JSON valida al editar.
    **/
    if ($_GET['band'] == 'update_turno_definicion') {
        try {
            $idTurno = isset($list_record['idTurno']) ? $list_record['idTurno'] : null;
            $nombreTurno = isset($list_record['Nombre_turno']) ? trim($list_record['Nombre_turno']) : '';
            $horaInicio = isset($list_record['FechaInicial_turno']) ? $list_record['FechaInicial_turno'] : null;
            $horaFin = isset($list_record['FechaFinal_turno']) ? $list_record['FechaFinal_turno'] : null;
            $duracion = isset($list_record['Duracion_turno']) ? $list_record['Duracion_turno'] : '00:00';
            $incluirDescanso = !empty($list_record['incluirDescanso']);

            if (!$idTurno || !$nombreTurno || !$horaInicio || !$horaFin) {
                $json = respuestaError('Datos incompletos para actualizar el turno');
                return;
            }

            $partes = explode(':', $duracion);
            $horas = isset($partes[0]) ? intval($partes[0]) : 0;
            $minutos = isset($partes[1]) ? intval($partes[1]) : 0;
            $duracionHoras = round($horas + ($minutos / 60), 2);

            $sql = "UPDATE Turnos
                    SET descripcion = ?, horaInicio = ?, horaFin = ?, duracionHoras = ?
                    WHERE idTurno = ?";
            $stmt = sqlsrv_query($conn, $sql, array($nombreTurno, $horaInicio, $horaFin, $duracionHoras, $idTurno));

            if ($stmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            sqlsrv_query($conn, "DELETE FROM TurnosDescansos WHERE idTurno = ?", array($idTurno));

            if ($incluirDescanso) {
                $inicioDescanso = isset($list_record['inicioDescanso']) ? $list_record['inicioDescanso'] : null;
                $finDescanso = isset($list_record['finDescanso']) ? $list_record['finDescanso'] : null;
                $duracionDescanso = isset($list_record['duracionDescanso']) ? $list_record['duracionDescanso'] : '00:00';
                $descripcionDescanso = isset($list_record['descripcionDescanso']) ? $list_record['descripcionDescanso'] : null;

                $partesDescanso = explode(':', $duracionDescanso);
                $horasDescanso = isset($partesDescanso[0]) ? intval($partesDescanso[0]) : 0;
                $minutosDescanso = isset($partesDescanso[1]) ? intval($partesDescanso[1]) : 0;
                $duracionDescansoMinutos = ($horasDescanso * 60) + $minutosDescanso;

                $sqlDescanso = "INSERT INTO TurnosDescansos
                                (idTurnoDescanso, idTurno, horaInicio, horaFin, duracionMinutos, descripcion, activo)
                                VALUES (NEWID(), ?, ?, ?, ?, ?, 1)";
                $stmtDescanso = sqlsrv_query($conn, $sqlDescanso, array(
                    $idTurno,
                    $inicioDescanso,
                    $finDescanso,
                    $duracionDescansoMinutos,
                    $descripcionDescanso
                ));

                if ($stmtDescanso === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }
            }

            $json = respuestaExito(array('idTurno' => $idTurno), 'Turno actualizado correctamente');
        } catch (Exception $e) {
            $json = respuestaError('Error al actualizar el turno: ' . $e->getMessage());
        }
    }



    if ($_GET['band'] == 'cargar_turnos') {
        $sql = "SELECT * FROM  turnos";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $consecutivo = $aa['idTurno'];
            $Nombre = $aa['descripcion'];
            $horaInicio = substr(date_format($aa['horaInicio'], 'H:i:s'), 0, 5);
            $horaFin = substr(date_format($aa['horaFin'], 'H:i:s'), 0, 5);
            $duracion = sprintf('%02d:%02d', floor($aa['duracionHoras']), round(($aa['duracionHoras'] - floor($aa['duracionHoras'])) * 60));
            $registro = array('id' => $consecutivo, 'name' => $Nombre, 'horaInicio' => $horaInicio, 'horaFin' => $horaFin, 'duracion' => $duracion);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_TurnosDescansos') {
        try {
            // Consulta modificada para incluir información de los descansos
            $sql = "SELECT t.idTurno, t.descripcion, t.horaInicio, t.horaFin, t.duracionHoras, 
                  td.horaInicio as inicioDescanso, td.horaFin as finDescanso, 
                  td.duracionMinutos as duracionDescansoMinutos,
                  td.descripcion as descripcionDescanso
                FROM turnos t 
                LEFT JOIN turnosDescansos td ON t.idTurno = td.idTurno AND ISNULL(td.activo, 1) = 1
                WHERE ISNULL(t.activo, 1) = 1
                ORDER BY t.descripcion";

            $res = sqlsrv_query($conn, $sql);

            if ($res === false) {
                throw new Exception('Error al ejecutar la consulta: ' . print_r(sqlsrv_errors(), true));
            }

            $data = [];

            while ($aa = sqlsrv_fetch_array($res)) {
                $consecutivo = $aa['idTurno'];
                $Nombre = $aa['descripcion'];
                $horaInicio = substr(date_format($aa['horaInicio'], 'H:i:s'), 0, 5);
                $horaFin = substr(date_format($aa['horaFin'], 'H:i:s'), 0, 5);
                $duracion = sprintf('%02d:%02d', floor($aa['duracionHoras']), round(($aa['duracionHoras'] - floor($aa['duracionHoras'])) * 60));

                // Procesar información de descanso si existe
                $infoDescanso = null;
                if ($aa['inicioDescanso'] !== null) {
                    $inicioDescanso = substr(date_format($aa['inicioDescanso'], 'H:i:s'), 0, 5);
                    $finDescanso = substr(date_format($aa['finDescanso'], 'H:i:s'), 0, 5);
                    $duracionDescansoMinutos = $aa['duracionDescansoMinutos'];

                    // Convertir minutos a formato HH:MM
                    $horasDescanso = floor($duracionDescansoMinutos / 60);
                    $minutosDescanso = $duracionDescansoMinutos % 60;
                    $duracionDescansoFormateada = sprintf('%02d:%02d', $horasDescanso, $minutosDescanso);

                    $descripcionDescanso = $aa['descripcionDescanso'] ?: '';

                    $infoDescanso = [
                        'inicio' => $inicioDescanso,
                        'fin' => $finDescanso,
                        'duracion' => $duracionDescansoFormateada,
                        'descripcion' => $descripcionDescanso
                    ];
                }

                $registro = [
                    'id' => $consecutivo,
                    'name' => $Nombre,
                    'horaInicio' => $horaInicio,
                    'horaFin' => $horaFin,
                    'duracion' => $duracion,
                    'descanso' => $infoDescanso
                ];

                array_push($data, $registro);
            }

            $json = respuestaExito($data, 'Turnos recuperados correctamente');
        } catch (Exception $e) {
            log_debug("Error en cargar_turnos", array('error' => $e->getMessage()));
            $json = respuestaError('Error al cargar turnos: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'get_turnos_asignados') {
        /**
        Codigo hecho por mario
        Funcion: get_turnos_asignados lista las programaciones activas por centro
        de trabajo y usa una fuente flexible de usuarios para evitar errores por
        ausencia de vUsuariosAppBiometrico en local.
        **/
        try {
            $idUsuario = isset($list_record['idUsuario']) ? $list_record['idUsuario'] : '';
            $centrosTrabajo = obtenerCentrosTrabajoPermitidos($conn, $idUsuario, 'CONSULTA_POR_CENTRO_DE_TRABAJO', 'DespachadoDesde');

            if (empty($centrosTrabajo)) {
                $json = respuestaError('Usuario no tiene centros de trabajo asignados');
                return;
            }

            $placeholders = implode(',', array_fill(0, count($centrosTrabajo), '?'));
            $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'VU');
            $tablaUsuariosEmpresa = resolverNombreObjetoSql($conn, array('dbo.UsuariosEmpresa', 'dbo.usuariosempresa'), array('U'));
            $tablaProveedores = resolverNombreObjetoSql($conn, array('dbo.Proveedores', 'dbo.proveedores'), array('U'));
            $usarEmpresas = !empty($tablaUsuariosEmpresa) && !empty($tablaProveedores);

            if ($usarEmpresas) {
                $joinEmpresa = "LEFT JOIN (
                        SELECT idUsuario, MIN(idEmpresa) AS idEmpresa
                        FROM {$tablaUsuariosEmpresa}
                        GROUP BY idUsuario
                    ) UE ON PT.idUsuario = UE.idUsuario
                    LEFT JOIN {$tablaProveedores} PROV ON UE.idEmpresa = PROV.idProveedor AND PROV.empresa = 1";
                $selectEmpresa = "ISNULL(MAX(PROV.RazonSocial), '') AS empresa";
                $groupEmpresa = "";
            } else {
                $joinEmpresa = "";
                $selectEmpresa = "'' AS empresa";
                $groupEmpresa = "";
            }

            $sql = "SELECT
                    PT.idProgramacion,
                    VU.NombreCompleto AS nombre,
                    VU.Identificacion AS cedula,
                    VU.Cargo AS cargo,
                    {$selectEmpresa},
                    CONVERT(VARCHAR(10), MIN(PTD.fechaInicio), 23) AS fechaInicio,
                    CONVERT(VARCHAR(10), MAX(PTD.fechaFin), 23) AS fechaFin,
                    CONVERT(VARCHAR(5), T.horaInicio, 108) AS horaInicio,
                    CONVERT(VARCHAR(5), T.horaFin, 108) AS horaFin,
                    RIGHT('00' + CAST(FLOOR(T.duracionHoras) AS VARCHAR(10)), 2) + ':' +
                    RIGHT('00' + CAST(CAST(ROUND((T.duracionHoras - FLOOR(T.duracionHoras)) * 60, 0) AS INT) AS VARCHAR(10)), 2) AS duracion
                FROM ProgramacionTurnos PT
                INNER JOIN ProgramacionTurnosDetalle PTD ON PT.idProgramacion = PTD.idProgramacion
                INNER JOIN Turnos T ON PTD.idTurno = T.idTurno
                INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = VU.idUsuario
                {$joinEmpresa}
                WHERE PT.activo = 1
                  AND PT.idCentroTrabajo IN ($placeholders)
                GROUP BY
                    PT.idProgramacion,
                    VU.NombreCompleto,
                    VU.Identificacion,
                    VU.Cargo,
                    T.horaInicio,
                    T.horaFin,
                    T.duracionHoras
                ORDER BY VU.NombreCompleto, MIN(PTD.fechaInicio)";

            $stmt = sqlsrv_query($conn, $sql, $centrosTrabajo);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            $turnos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $turnos[] = array(
                    'nombre' => normalizarTextoSalida($row['nombre']),
                    'cedula' => $row['cedula'],
                    'cargo' => normalizarTextoSalida($row['cargo'] ?? ''),
                    'empresa' => normalizarTextoSalida($row['empresa'] ?? ''),
                    'fechaInicio' => $row['fechaInicio'],
                    'fechaFin' => $row['fechaFin'],
                    'horaInicio' => $row['horaInicio'],
                    'horaFin' => $row['horaFin'],
                    'duracion' => $row['duracion'],
                    'idProgramacion' => $row['idProgramacion']
                );
            }

            $json = respuestaExito($turnos, 'Turnos asignados obtenidos correctamente');
        } catch (Exception $e) {
            $json = respuestaError('Error al consultar los turnos asignados: ' . $e->getMessage());
        }
    }


    if ($_GET['band'] == 'save_programacion_turnos') {
        try {
            // Recibir los datos enviados en formato JSON y procesarlos usando list_record
            $idCentroTrabajo = isset($list_record['idCentroTrabajo']) ? resolverIdentificadorEntrada($list_record['idCentroTrabajo']) : null;
            $idTurno = $list_record['idTurno'];
            $fechaInicio = $list_record['fechaInicio'];
            $fechaFin = $list_record['fechaFin'];
            $diasLaborales = isset($list_record['diasLaborales']) ? $list_record['diasLaborales'] : array();
            $usuarios = $list_record['usuarios'];
            $idUsuarioRegistra = $list_record['idUsuario'];

            // Variables para almacenar resultados
            $resultados = array();
            $exitosos = 0;
            $fallidos = 0;

            // Configurar los días de la semana para el procedimiento almacenado
            $idTurnoLunes = null;
            $idTurnoMartes = null;
            $idTurnoMiercoles = null;
            $idTurnoJueves = null;
            $idTurnoViernes = null;
            $idTurnoSabado = null;
            $idTurnoDomingo = null;
            $idTurnoDefault = null;

            // Si no se especificaron días, usar el turno para todos los días (idTurnoDefault)
            if (empty($diasLaborales)) {
                $idTurnoDefault = $idTurno;
            } else {
                // Si se especificaron días, asignar el turno a esos días específicos
                foreach ($diasLaborales as $dia) {
                    switch ($dia) {
                        case 'lunes':
                            $idTurnoLunes = $idTurno;
                            break;
                        case 'martes':
                            $idTurnoMartes = $idTurno;
                            break;
                        case 'miércoles':
                            $idTurnoMiercoles = $idTurno;
                            break;
                        case 'jueves':
                            $idTurnoJueves = $idTurno;
                            break;
                        case 'viernes':
                            $idTurnoViernes = $idTurno;
                            break;
                        case 'sábado':
                            $idTurnoSabado = $idTurno;
                            break;
                        case 'domingo':
                            $idTurnoDomingo = $idTurno;
                            break;
                    }
                }
            }

            // Procesar cada usuario seleccionado
            foreach ($usuarios as $idUsuarioEncriptado) {
                try {
                    // Resolver el ID del usuario (acepta GUID directo o encriptado)
                    $idUsuario = resolverIdentificadorEntrada($idUsuarioEncriptado);

                    // Validar que el usuario no tenga ya este turno asignado activo
                    $sqlDuplicado = "SELECT COUNT(*) AS cnt
                        FROM ProgramacionTurnos PT
                        INNER JOIN ProgramacionTurnosDetalle PTD ON PT.idProgramacion = PTD.idProgramacion
                        WHERE PT.idUsuario = ? AND PT.activo = 1 AND PTD.idTurno = ?";
                    $stmtDuplicado = sqlsrv_query($conn, $sqlDuplicado, array($idUsuario, $idTurno));
                    if ($stmtDuplicado !== false) {
                        $rowDup = sqlsrv_fetch_array($stmtDuplicado, SQLSRV_FETCH_ASSOC);
                        if ($rowDup && $rowDup['cnt'] > 0) {
                            $resultados[] = array(
                                'idUsuario' => $idUsuarioEncriptado,
                                'success' => false,
                                'message' => 'El usuario ya tiene este turno asignado'
                            );
                            $fallidos++;
                            continue;
                        }
                    }

                    // Ejecutar el procedimiento almacenado para asignar el turno al usuario
                    $sql = "EXEC  SAVE_ProgramacionTurnos 
                    @idUsuario = ?, 
                    @idCentroTrabajo = ?, 
                    @fechaInicio = ?, 
                    @fechaFin = ?, 
                    @idUsuarioRegistra = ?, 
                    @idTurnoLunes = ?, 
                    @idTurnoMartes = ?, 
                    @idTurnoMiercoles = ?, 
                    @idTurnoJueves = ?, 
                    @idTurnoViernes = ?, 
                    @idTurnoSabado = ?, 
                    @idTurnoDomingo = ?, 
                    @idTurnoDefault = ?";

                    $params = array(
                        $idUsuario,
                        $idCentroTrabajo,
                        $fechaInicio,
                        $fechaFin,
                        $idUsuarioRegistra,
                        $idTurnoLunes,
                        $idTurnoMartes,
                        $idTurnoMiercoles,
                        $idTurnoJueves,
                        $idTurnoViernes,
                        $idTurnoSabado,
                        $idTurnoDomingo,
                        $idTurnoDefault
                    );

                    $stmt = sqlsrv_prepare($conn, $sql, $params);

                    if ($stmt === false) {
                        $errors = sqlsrv_errors();
                        $resultados[] = array(
                            'idUsuario' => $idUsuarioEncriptado,
                            'success' => false,
                            'message' => 'Error en la preparación: ' . $errors[0]['message']
                        );
                        $fallidos++;
                    } else {
                        $result = sqlsrv_execute($stmt);

                        if ($result === false) {
                            $errors = sqlsrv_errors();
                            $resultados[] = array(
                                'idUsuario' => $idUsuarioEncriptado,
                                'success' => false,
                                'message' => 'Error en la ejecución: ' . $errors[0]['message']
                            );
                            $fallidos++;
                        } else {
                            $resultados[] = array(
                                'idUsuario' => $idUsuarioEncriptado,
                                'success' => true,
                                'message' => 'Turno asignado correctamente'
                            );
                            $exitosos++;
                        }
                    }
                } catch (Exception $e) {
                    $resultados[] = array(
                        'idUsuario' => $idUsuarioEncriptado,
                        'success' => false,
                        'message' => 'Excepción: ' . $e->getMessage()
                    );
                    $fallidos++;
                }
            }

            // Preparar el mensaje de respuesta
            $mensaje = "Se han asignado turnos correctamente a $exitosos usuarios";
            if ($fallidos > 0) {
                $mensaje .= ", con $fallidos fallos.";
            } else {
                $mensaje .= ".";
            }

            // Usar la función de respuesta según el resultado
            if ($exitosos > 0) {
                $json = respuestaExito(
                    array(
                        'exitosos' => $exitosos,
                        'fallidos' => $fallidos,
                        'resultados' => $resultados
                    ),
                    $mensaje
                );
            } else {
                $json = respuestaError(
                    $mensaje,
                    array(
                        'exitosos' => $exitosos,
                        'fallidos' => $fallidos,
                        'resultados' => $resultados
                    )
                );
            }
        } catch (Exception $e) {
            // Capturar errores generales
            $json = respuestaError('Error al procesar la asignación de turnos: ' . $e->getMessage());
        }
    }


    if ($_GET['band'] == 'get_programacion_turno') {
        /**
        Codigo hecho por mario
        Funcion: get_programacion_turno recupera el detalle de una programacion
        de turnos para el modal de edicion con compatibilidad entre vistas nuevas
        y tablas base locales.
        **/
        try {
            // Obtener ID de la programación
            $idProgramacion = isset($list_record['idProgramacion']) ? resolverIdentificadorEntrada($list_record['idProgramacion']) : null;

            if (!$idProgramacion) {
                $json = respuestaError('ID de programación no proporcionado o inválido');
            } else {
                // Obtener datos completos de la programación
                $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'VU');
                $sql = "SELECT 
                PT.idProgramacion,
                PT.idUsuario,
                PT.idCentroTrabajo,
                PT.fechaInicio,
                PT.fechaFin,
                PT.activo,
                VU.NombreCompleto AS nombreUsuario,
                D.Descripcion AS centroDeTrabajo
            FROM 
                ProgramacionTurnos PT
                INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = VU.idUsuario
                INNER JOIN Destino D ON PT.idCentroTrabajo = D.idDestino
            WHERE 
                PT.idProgramacion = ?";

                $stmt = sqlsrv_query($conn, $sql, array($idProgramacion));

                if ($stmt === false) {
                    $errors = sqlsrv_errors();
                    $json = respuestaError('Error al consultar la programación: ' . $errors[0]['message']);
                } else {
                    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        // Obtener los turnos asignados por día
                        $sqlTurnos = "SELECT 
                        PTD.fechaInicio AS fecha, 
                        T.idTurno,
                        T.descripcion,
                        T.horaInicio,
                        T.horaFin,
                        T.duracionHoras
                    FROM 
                        ProgramacionTurnosDetalle PTD
                        INNER JOIN Turnos T ON PTD.idTurno = T.idTurno
                    WHERE 
                        PTD.idProgramacion = ?
                    ORDER BY 
                        PTD.fechaInicio";

                        $stmtTurnos = sqlsrv_query($conn, $sqlTurnos, array($idProgramacion));

                        if ($stmtTurnos === false) {
                            $errors = sqlsrv_errors();
                            $json = respuestaError('Error al consultar los turnos: ' . $errors[0]['message']);
                        } else {
                            $detallesTurno = array();
                            while ($rowTurno = sqlsrv_fetch_array($stmtTurnos, SQLSRV_FETCH_ASSOC)) {
                                $detallesTurno[] = array(
                                    'fecha' => $rowTurno['fecha']->format('Y-m-d'),
                                    'idTurnoRaw' => $rowTurno['idTurno'],
                                    'descripcion' => $rowTurno['descripcion'],
                                    'horaInicio' => $rowTurno['horaInicio']->format('H:i:s'),
                                    'horaFin' => $rowTurno['horaFin']->format('H:i:s'),
                                    'duracionHoras' => $rowTurno['duracionHoras']
                                );
                            }

                            $programacion = array(
                                'idProgramacion' => $row['idProgramacion'],
                                'idUsuario' => $row['idUsuario'],
                                'idCentroTrabajo' => $row['idCentroTrabajo'],
                                'fechaInicio' => $row['fechaInicio']->format('Y-m-d'),
                                'fechaFin' => $row['fechaFin']->format('Y-m-d'),
                                'activo' => $row['activo'],
                                'nombreUsuario' => $row['nombreUsuario'],
                                'centroDeTrabajo' => $row['centroDeTrabajo'],
                                'detallesTurno' => $detallesTurno
                            );

                            $json = respuestaExito(
                                $programacion,
                                'Datos de programación obtenidos correctamente'
                            );
                        }
                    } else {
                        $json = respuestaError('No se encontró la programación de turno especificada');
                    }
                }
            }
        } catch (Exception $e) {
            $json = respuestaError('Error al procesar la consulta: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'delete_programacion_turno') {
        try {
            $idProgramacion = isset($list_record['idProgramacion']) ? resolverIdentificadorEntrada($list_record['idProgramacion']) : null;

            if (!$idProgramacion) {
                $json = respuestaError('ID de programación no proporcionado o inválido');
                return;
            }

            $sqlProg = "SELECT fechaInicio, fechaFin FROM ProgramacionTurnos WHERE idProgramacion = ?";
            $stmtProg = sqlsrv_query($conn, $sqlProg, array($idProgramacion));
            if ($stmtProg === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            $fechaInicio = null;
            $fechaFin = null;
            if ($rowProg = sqlsrv_fetch_array($stmtProg, SQLSRV_FETCH_ASSOC)) {
                $fechaInicio = $rowProg['fechaInicio'];
                $fechaFin = $rowProg['fechaFin'];
            }

            $sqlBitacora = "SELECT COUNT(1) as total FROM Bitacora B INNER JOIN ProgramacionTurnosDetalle PD ON B.idProgramacionDetalle = PD.idProgramacionDetalle WHERE PD.idProgramacion = ?";
            $stmtBitacora = sqlsrv_query($conn, $sqlBitacora, array($idProgramacion));

            $sqlDetalles = "DELETE FROM ProgramacionTurnosDetalle WHERE idProgramacion = ?";
            $stmtDetalles = sqlsrv_query($conn, $sqlDetalles, array($idProgramacion));

            if ($stmtDetalles === false) {
                $errors = sqlsrv_errors();
                $json = respuestaError('Error al eliminar los detalles: ' . $errors[0]['message']);
                return;
            }

            $sqlProgramacion = "DELETE FROM ProgramacionTurnos WHERE idProgramacion = ?";
            $stmtProgramacion = sqlsrv_query($conn, $sqlProgramacion, array($idProgramacion));

            if ($stmtProgramacion === false) {
                $errors = sqlsrv_errors();
                $json = respuestaError('Error al eliminar la programación: ' . $errors[0]['message']);
                return;
            }

            $json = respuestaExito(
                array('idProgramacion' => ENCR::encript($idProgramacion)),
                'Programación de turno eliminada correctamente'
            );
        } catch (Exception $e) {
            $json = respuestaError('Error al procesar la eliminación: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'validate_delete_turno_definicion') {
        /**
        Codigo hecho por mario
        Funcion: validate_delete_turno_definicion revisa uso historico y
        asignaciones activas antes de eliminar o editar un turno definido.
        **/
        try {
            $idTurno = isset($list_record['idTurno']) ? $list_record['idTurno'] : null;

            if (!$idTurno) {
                $json = respuestaError('ID de turno inválido');
                return;
            }

            $sqlTurno = "SELECT descripcion FROM turnos WHERE idTurno = ? AND activo = 1";
            $stmtTurno = sqlsrv_query($conn, $sqlTurno, array($idTurno));
            if ($stmtTurno === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }
            if (!$turnoData = sqlsrv_fetch_array($stmtTurno, SQLSRV_FETCH_ASSOC)) {
                $json = respuestaError('Turno no encontrado o inactivo');
                return;
            }

            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $assignedUsers = array();
            $futureCoverage = array();
            $hasPastUsage = false;
            $warnings = array();
            $hardBlock = false;
            $hardMessage = '';

            $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'VU');
            $sqlAsignaciones = "SELECT DISTINCT
                    PT.idProgramacion,
                    PT.fechaInicio,
                    PT.fechaFin,
                    PT.activo,
                    PT.idCentroTrabajo,
                    D.Descripcion AS centroTrabajo,
                    VU.NombreCompleto
                FROM ProgramacionTurnosDetalle PD
                INNER JOIN ProgramacionTurnos PT ON PD.idProgramacion = PT.idProgramacion
                INNER JOIN Destino D ON PT.idCentroTrabajo = D.idDestino
                INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = VU.idUsuario
                WHERE PD.idTurno = ?";
            $stmtAsignaciones = sqlsrv_query($conn, $sqlAsignaciones, array($idTurno));
            if ($stmtAsignaciones === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            while ($rowAsignacion = sqlsrv_fetch_array($stmtAsignaciones, SQLSRV_FETCH_ASSOC)) {
                $nombre = normalizarTextoSalida($rowAsignacion['NombreCompleto']);
                if (!in_array($nombre, $assignedUsers)) {
                    $assignedUsers[] = $nombre;
                }

                $centroTrabajo = normalizarTextoSalida($rowAsignacion['centroTrabajo']);
                $fechaInicioObj = ($rowAsignacion['fechaInicio'] instanceof DateTime) ? clone $rowAsignacion['fechaInicio'] : new DateTime(date_format($rowAsignacion['fechaInicio'], 'Y-m-d'));
                $fechaFinObj = ($rowAsignacion['fechaFin'] instanceof DateTime) ? clone $rowAsignacion['fechaFin'] : new DateTime(date_format($rowAsignacion['fechaFin'], 'Y-m-d'));

                if ($rowAsignacion['activo'] == 1) {
                    $hardBlock = true;
                    $hardMessage = "Este turno no se puede eliminar porque está asignado a {$nombre} en {$centroTrabajo}.";
                }

                if ($fechaFinObj < $today) {
                    $hasPastUsage = true;
                }

                if ($fechaFinObj >= $today) {
                    $futureCoverage[] = "{$centroTrabajo} ({$fechaInicioObj->format('Y-m-d')} - {$fechaFinObj->format('Y-m-d')})";
                }
            }

            if (!empty($assignedUsers)) {
                $hardBlock = true;
                $hardMessage = 'Este turno no se puede eliminar porque tiene usuarios asignados.';
            }

            $sqlUsoBitacora = "SELECT TOP 1 1 AS existe
                FROM ProgramacionTurnosDetalle PD
                INNER JOIN Bitacora B ON PD.idProgramacionDetalle = B.idProgramacionDetalle
                WHERE PD.idTurno = ?";
            $stmtUsoBitacora = sqlsrv_query($conn, $sqlUsoBitacora, array($idTurno));
            if ($stmtUsoBitacora === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            if (!$hardBlock && sqlsrv_fetch_array($stmtUsoBitacora, SQLSRV_FETCH_ASSOC)) {
                $hardBlock = true;
                $hardMessage = 'Este turno no se puede eliminar porque está en uso.';
            }

            if (!$hardBlock && $hasPastUsage) {
                $warnings[] = 'Este turno tiene historial de uso en el pasado. Eliminarlo quitará su referencia histórica.';
            }

            if (!$hardBlock && !empty($futureCoverage)) {
                $warningCoverage = implode(', ', array_unique($futureCoverage));
                $warnings[] = "Eliminar este turno afectará la cobertura en: {$warningCoverage}.";
            }

            if (empty($warnings)) {
                $softMessage = 'No se detectaron advertencias adicionales.';
            } else {
                $softMessage = 'Se detectaron advertencias adicionales antes de eliminar.';
            }

            $json = respuestaExito(
                array(
                    'hardBlock' => $hardBlock,
                    'hardMessage' => $hardMessage,
                    'warnings' => $warnings,
                    'softMessage' => $softMessage,
                    'assignedUsers' => $assignedUsers,
                    'turnoDescripcion' => normalizarTextoSalida($turnoData['descripcion'])
                ),
                'Validación completada'
            );
        } catch (Exception $e) {
            $json = respuestaError('Error al validar la eliminación del turno: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'update_programacion_turno') {
        /**
        Codigo hecho por mario
        Funcion: update_programacion_turno actualiza fechas, centro y turno de
        una programacion, recalculando el detalle diario segun el esquema local.
        **/
        try {
            $idProgramacion = isset($list_record['idProgramacion']) ? resolverIdentificadorEntrada($list_record['idProgramacion']) : null;
            $idCentroTrabajo = isset($list_record['idCentroTrabajo']) ? resolverIdentificadorEntrada($list_record['idCentroTrabajo']) : null;
            $fechaInicio = isset($list_record['fechaInicio']) ? $list_record['fechaInicio'] : null;
            $fechaFin = isset($list_record['fechaFin']) ? $list_record['fechaFin'] : null;
            $idTurno = isset($list_record['idTurno']) ? $list_record['idTurno'] : null;
            $diasLaborales = isset($list_record['diasLaborales']) && is_array($list_record['diasLaborales']) ? $list_record['diasLaborales'] : array();
            $mapaDias = array('lunes' => 1, 'martes' => 2, 'miércoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sábado' => 6, 'domingo' => 7);
            $numeroDias = array();
            foreach ($diasLaborales as $d) {
                if (isset($mapaDias[$d])) {
                    $numeroDias[] = $mapaDias[$d];
                }
            }

            $fieldErrors = array();
            if (!$idProgramacion) {
                $fieldErrors['idProgramacion'] = 'Programación no válida';
            }
            if (!$idCentroTrabajo) {
                $fieldErrors['idCentroTrabajo'] = 'Seleccione un centro de trabajo válido';
            }
            if (!$fechaInicio) {
                $fieldErrors['fechaInicio'] = 'La fecha de inicio es obligatoria';
            }
            if (!$fechaFin) {
                $fieldErrors['fechaFin'] = 'La fecha de fin es obligatoria';
            }
            if (!$idTurno) {
                $fieldErrors['idTurno'] = 'Seleccione el turno a aplicar';
            }
            if (!$idProgramacion || !$idCentroTrabajo || !$fechaInicio || !$fechaFin || !$idTurno) {
                $json = respuestaError(
                    'Debe completar los campos obligatorios para actualizar la programación',
                    array('fieldErrors' => $fieldErrors)
                );
                return;
            }

            $fechaInicioObj = new DateTime($fechaInicio);
            $fechaFinObj = new DateTime($fechaFin);
            $today = new DateTime();
            $today->setTime(0, 0, 0);

            if ($fechaInicioObj > $fechaFinObj) {
                $json = respuestaError(
                    'La fecha de inicio no puede ser posterior a la fecha de fin',
                    array(
                        'fieldErrors' => array(
                            'fechaInicio' => 'La fecha de inicio debe ser igual o anterior a la fecha de fin',
                            'fechaFin' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio'
                        )
                    )
                );
                return;
            }

            if ($fechaInicioObj < $today) {
                $json = respuestaError(
                    'La fecha de inicio no puede ser anterior a la fecha actual',
                    array(
                        'fieldErrors' => array(
                            'fechaInicio' => 'Seleccione una fecha igual o posterior a hoy'
                        )
                    )
                );
                return;
            }

            $sqlCentro = "SELECT TOP 1 * FROM Destino WHERE idDestino = ?";
            $stmtCentro = sqlsrv_query($conn, $sqlCentro, array($idCentroTrabajo));
            if ($stmtCentro === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            $centroValido = false;
            if ($rowCentro = sqlsrv_fetch_array($stmtCentro, SQLSRV_FETCH_ASSOC)) {
                $activoCentro = isset($rowCentro['Activo']) ? $rowCentro['Activo'] : 1;
                $centroValido = ($activoCentro == 1 || $activoCentro === null);
            }
            if (!$centroValido) {
                $json = respuestaError(
                    'Centro de trabajo inexistente o inactivo',
                    array('fieldErrors' => array('idCentroTrabajo' => 'El centro de trabajo seleccionado no está activo'))
                );
                return;
            }

            $sqlTurno = "SELECT horaInicio, horaFin FROM turnos WHERE idTurno = ? AND activo = 1";
            $stmtTurno = sqlsrv_query($conn, $sqlTurno, array($idTurno));
            if ($stmtTurno === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }
            if (!$turnoRow = sqlsrv_fetch_array($stmtTurno, SQLSRV_FETCH_ASSOC)) {
                $json = respuestaError(
                    'Turno seleccionado no existe o está inactivo',
                    array('fieldErrors' => array('idTurno' => 'Seleccione un turno activo'))
                );
                return;
            }

            $intervaloNuevo = construirIntervaloHoras($turnoRow['horaInicio']->format('H:i:s'), $turnoRow['horaFin']->format('H:i:s'));
            $duracionMinutosNuevo = $intervaloNuevo[1] - $intervaloNuevo[0];
            if ($duracionMinutosNuevo > 24 * 60) {
                $json = respuestaError(
                    'La duración del turno no puede superar las 24 horas',
                    array('fieldErrors' => array('duracion' => 'El turno debe durar 24 horas como máximo'))
                );
                return;
            }

            $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'V');
            $sqlConflicto = "SELECT PTD.fechaInicio AS fecha, T.descripcion, T.horaInicio, T.horaFin, V.NombreCompleto
                FROM ProgramacionTurnosDetalle PTD
                INNER JOIN ProgramacionTurnos PT ON PTD.idProgramacion = PT.idProgramacion
                INNER JOIN Turnos T ON PTD.idTurno = T.idTurno
                INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = V.idUsuario
                WHERE PT.idCentroTrabajo = ? AND PT.activo = 1 AND PT.idProgramacion <> ? AND PTD.fechaInicio BETWEEN ? AND ?";
            $stmtConflicto = sqlsrv_query($conn, $sqlConflicto, array($idCentroTrabajo, $idProgramacion, $fechaInicioObj->format('Y-m-d'), $fechaFinObj->format('Y-m-d')));

            if ($stmtConflicto === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            while ($rowConflicto = sqlsrv_fetch_array($stmtConflicto, SQLSRV_FETCH_ASSOC)) {
                $intervaloExistente = construirIntervaloHoras($rowConflicto['horaInicio']->format('H:i:s'), $rowConflicto['horaFin']->format('H:i:s'));
                if (intervalosSolapan($intervaloNuevo, $intervaloExistente)) {
                    $fechaConflicto = $rowConflicto['fecha']->format('Y-m-d');
                    $mensaje = "Existe una programación en el mismo centro (" . $rowConflicto['NombreCompleto'] . ") para el " . $fechaConflicto . " que solapa con el nuevo horario.";
                    $json = respuestaError(
                        $mensaje,
                        array('fieldErrors' => array(
                            'fechaInicio' => $mensaje,
                            'fechaFin' => $mensaje
                        ))
                    );
                    return;
                }
            }

            $sqlUpdate = "UPDATE ProgramacionTurnos SET fechaInicio = ?, fechaFin = ?, idCentroTrabajo = ? WHERE idProgramacion = ?";
            $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, array($fechaInicioObj->format('Y-m-d'), $fechaFinObj->format('Y-m-d'), $idCentroTrabajo, $idProgramacion));
            if ($stmtUpdate === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            $sqlEliminarDetalles = "DELETE FROM ProgramacionTurnosDetalle WHERE idProgramacion = ?";
            $stmtEliminar = sqlsrv_query($conn, $sqlEliminarDetalles, array($idProgramacion));
            if ($stmtEliminar === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            $intervaloFecha = clone $fechaInicioObj;
            $horaInicioTurno = $turnoRow['horaInicio']->format('H:i:s');
            $horaFinTurno = $turnoRow['horaFin']->format('H:i:s');
            $cruzaMedianoche = strcmp($horaFinTurno, $horaInicioTurno) < 0;
            while ($intervaloFecha <= $fechaFinObj) {
                $diaSemana = intval($intervaloFecha->format('N')); // 1=lunes...7=domingo
                if (empty($numeroDias) || in_array($diaSemana, $numeroDias)) {
                    $fechaDetalleInicio = $intervaloFecha->format('Y-m-d');
                    $fechaDetalleFin = $cruzaMedianoche
                        ? (clone $intervaloFecha)->add(new DateInterval('P1D'))->format('Y-m-d')
                        : $fechaDetalleInicio;

                    $sqlInsertDetalle = "INSERT INTO ProgramacionTurnosDetalle (idProgramacionDetalle, idProgramacion, fechaInicio, fechaFin, idTurno) VALUES (NEWID(), ?, ?, ?, ?)";
                    $paramsInsert = array($idProgramacion, $fechaDetalleInicio, $fechaDetalleFin, $idTurno);
                    sqlsrv_query($conn, $sqlInsertDetalle, $paramsInsert);
                }
                $intervaloFecha->add(new DateInterval('P1D'));
            }

            $json = respuestaExito(
                array('idProgramacion' => ENCR::encript($idProgramacion)),
                'Programación de turno actualizada correctamente'
            );
        } catch (Exception $e) {
            log_debug('update_programacion_turno exception', $e->getMessage());
            $json = respuestaError('Error al procesar la actualización: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'validate_delete_programacion_turno') {
        /**
        Codigo hecho por mario
        Funcion: validate_delete_programacion_turno evalua bloqueos y advertencias
        antes de permitir la eliminacion de una programacion asignada.
        **/
        try {
            $idProgramacion = isset($list_record['idProgramacion']) ? resolverIdentificadorEntrada($list_record['idProgramacion']) : null;
            if (!$idProgramacion) {
                $json = respuestaError('ID de programación no válido');
                return;
            }

            $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'V');
            $sqlProg = "SELECT PT.fechaInicio, PT.fechaFin, PT.idCentroTrabajo, D.Descripcion AS centroDeTrabajo, V.NombreCompleto AS nombreUsuario
                        FROM ProgramacionTurnos PT
                        LEFT JOIN Destino D ON PT.idCentroTrabajo = D.idDestino
                        LEFT JOIN {$fuenteUsuarios} ON PT.idUsuario = V.idUsuario
                        WHERE PT.idProgramacion = ?";
            $stmtProg = sqlsrv_query($conn, $sqlProg, array($idProgramacion));
            if ($stmtProg === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            if (!$programacion = sqlsrv_fetch_array($stmtProg, SQLSRV_FETCH_ASSOC)) {
                $json = respuestaError('No se encontró la programación solicitada');
                return;
            }

            $fechaInicio = $programacion['fechaInicio'];
            $fechaFin = $programacion['fechaFin'];
            $idCentroTrabajo = $programacion['idCentroTrabajo'];
            $assignedUsers = [];
            if (!empty($programacion['nombreUsuario'])) {
                $assignedUsers[] = utf8_encode($programacion['nombreUsuario']);
            }

            $today = new DateTime();
            $today->setTime(0, 0, 0);

            $sqlBitacora = "SELECT COUNT(1) as total FROM Bitacora B INNER JOIN ProgramacionTurnosDetalle PD ON B.idProgramacionDetalle = PD.idProgramacionDetalle WHERE PD.idProgramacion = ?";
            $stmtBitacora = sqlsrv_query($conn, $sqlBitacora, array($idProgramacion));
            $bitacoraCount = 0;
            if ($stmtBitacora !== false && $rowBitacora = sqlsrv_fetch_array($stmtBitacora, SQLSRV_FETCH_ASSOC)) {
                $bitacoraCount = $rowBitacora['total'];
            }

            $hardBlock = false;
            $hardMessage = '';
            $warnings = [];

            if ($bitacoraCount > 0) {
                $warnings[] = 'La programación tiene marcaciones registradas y al eliminarla también se removerá su relación con esos registros.';
            } else {
                $fechaInicioObj = ($fechaInicio instanceof DateTime) ? clone $fechaInicio : new DateTime(date_format($fechaInicio, 'Y-m-d'));
                $fechaFinObj = ($fechaFin instanceof DateTime) ? clone $fechaFin : new DateTime(date_format($fechaFin, 'Y-m-d'));
                if ($fechaInicioObj <= $today && $today <= $fechaFinObj) {
                    $warnings[] = 'La programación está en curso o activa.';
                }
            }

            $softMessage = 'Confirme que desea eliminar la programación seleccionada.';
            if ($bitacoraCount === 0) {
                $fechaFinObj = ($fechaFin instanceof DateTime) ? clone $fechaFin : new DateTime(date_format($fechaFin, 'Y-m-d'));
                if ($fechaFinObj < $today) {
                    $warnings[] = 'El turno ya finalizó y no tiene registros asociados, considere conservar el historial.';
                }

                $fechaActual = ($fechaInicio instanceof DateTime) ? clone $fechaInicio : new DateTime(date_format($fechaInicio, 'Y-m-d'));
                while ($fechaActual <= $fechaFinObj) {
                    $fechaTexto = $fechaActual->format('Y-m-d');
                    $sqlCobertura = "SELECT COUNT(DISTINCT PT.idProgramacion) AS total FROM ProgramacionTurnos PT INNER JOIN ProgramacionTurnosDetalle PD ON PT.idProgramacion = PD.idProgramacion WHERE PT.idCentroTrabajo = ? AND PT.activo = 1 AND PT.idProgramacion <> ? AND PD.fechaInicio = ?";
                    $paramsCobertura = array($idCentroTrabajo, $idProgramacion, $fechaTexto);
                    $stmtCobertura = sqlsrv_query($conn, $sqlCobertura, $paramsCobertura);
                    if ($stmtCobertura !== false && $rowCobertura = sqlsrv_fetch_array($stmtCobertura, SQLSRV_FETCH_ASSOC)) {
                        if (intval($rowCobertura['total']) === 0) {
                            $warnings[] = "Eliminar esta programación dejará sin cobertura la fecha {$fechaTexto}.";
                        }
                    }
                    $fechaActual->add(new DateInterval('P1D'));
                }
            }

            if (!empty($warnings)) {
                $softMessage = 'Se detectaron advertencias antes de eliminar la programación.';
            }

            $json = respuestaExito(
                array(
                    'hardBlock' => $hardBlock,
                    'hardMessage' => $hardMessage,
                    'warnings' => $warnings,
                    'softMessage' => $softMessage,
                    'assignedUsers' => $assignedUsers,
                    'centroTrabajo' => utf8_encode($programacion['centroDeTrabajo']),
                    'idProgramacion' => ENCR::encript($idProgramacion)
                ),
                'Validación completada'
            );
        } catch (Exception $e) {
            $json = respuestaError('Error al validar la eliminación: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'delete_turno_definicion') {
        /**
        Codigo hecho por mario
        Funcion: delete_turno_definicion aplica borrado logico al turno y a sus
        descansos asociados, evitando eliminarlo si sigue asignado a usuarios.
        **/
        try {
            $idTurno = isset($list_record['idTurno']) ? $list_record['idTurno'] : null;

            if (!$idTurno) {
                $json = respuestaError('ID de turno inválido');
                return;
            }

            $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'V');
            $sqlAsignaciones = "SELECT TOP 3 V.NombreCompleto, PT.fechaInicio FROM ProgramacionTurnosDetalle PD INNER JOIN ProgramacionTurnos PT ON PD.idProgramacion = PT.idProgramacion INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = V.idUsuario WHERE PD.idTurno = ? AND PT.activo = 1 ORDER BY PT.fechaInicio DESC";
            $stmtAsignaciones = sqlsrv_query($conn, $sqlAsignaciones, array($idTurno));

            if ($stmtAsignaciones === false) {
                $errors = sqlsrv_errors();
                throw new Exception($errors[0]['message']);
            }

            $usuariosAsignados = array();
            while ($rowAsignacion = sqlsrv_fetch_array($stmtAsignaciones, SQLSRV_FETCH_ASSOC)) {
                $usuariosAsignados[] = utf8_encode($rowAsignacion['NombreCompleto']);
            }

            if (!empty($usuariosAsignados)) {
                $lista = implode(', ', array_slice($usuariosAsignados, 0, 3));
                $json = respuestaError("No se puede eliminar el turno porque está asignado a: {$lista}");
                return;
            }

            $sqlActualizarActivo = "UPDATE turnos SET activo = 0 WHERE idTurno = ?";
            sqlsrv_query($conn, $sqlActualizarActivo, array($idTurno));

            $sqlDescansos = "UPDATE turnosDescansos SET activo = 0 WHERE idTurno = ?";
            sqlsrv_query($conn, $sqlDescansos, array($idTurno));

            $json = respuestaExito(null, 'Turno eliminado correctamente');
        } catch (Exception $e) {
            $json = respuestaError('Error al eliminar el turno: ' . $e->getMessage());
        }
    }

    //❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇❇

    if ($_GET['band'] == 'get_asignar_turno') {
        $FechaInicial_turno = $list_record['FechaInicial_turno'];
        $FechaFinal_turno = $list_record['FechaFinal_turno'];
        $lista_turnos = $list_record['lista_turnos'];
        $dias_laborales = $list_record['dias_laborales'];
        $usuario = ENCR::descript($list_record['usuario']);
        $id_usuario_login = $list_record['id_usuario'];
        $idcentroTrabajo = resolverIdentificadorEntrada($list_record['idcentroTrabajo']);
        $bandera = 0;
        $array_dias = array();
        $cont = count($list_record["dias_laborales"]);
        for ($i = 0; $i < $cont; $i++) {
            $array_dias[$i] = $list_record["dias_laborales"][$i];
        }
        $dias_laborales = implode(",", $array_dias);
        if ($FechaFinal_turno == '') {
            $FechaFinal_turno = '1900-01-01';
        }

        $sql = "EXECUTE SAVE_bitacora_asignar_turnos @idturno='$lista_turnos', @fecha_ini='$FechaInicial_turno', @fecha_fin='$FechaFinal_turno', 
            @dias='$dias_laborales', @iduser_login='$id_usuario_login', @idusuario='$usuario' ,@centrotrabajo='$idcentroTrabajo'";
        $res = sqlsrv_query($conn, utf8_decode($sql));
        /*  if($res){
                $sql1="SELECT turnos_empleados.id_turno, descripcion from turnos_empleados  where id_turno ='$lista_turnos'";
                $res = sqlsrv_query($conn, $sql1);
                $data = [];
                while ($aa = sqlsrv_fetch_array($res)) {
                    $consecutivo = $aa['id_turno'];
                    $Nombre = $aa['descripcion'];
                    $registro = array('id' => $consecutivo, 'name' => $Nombre);
                    array_push($data, $registro);
                }
            }
            $json = (json_encode($data));*
        }else  */
        $json = $sql;
    }

    if ($_GET['band'] == 'get_crear_turno') {
        $FechaInicial_turno = $list_record['FechaInicial_turno'];
        $FechaFinal_turno = $list_record['FechaFinal_turno'];
        $lista_turnos = $list_record['lista_turnos'];
        $lista_actividades = $list_record['lista_actividades'];
        $idusuario = $list_record['idusuario'];
        /////////  falta el usuario conectado///////////////////////////////////////////
        $bandera = 0;
        if ($lista_turnos == '1') {
            $sqlid = 'SELECT Newid() as id';
            $res2 = sqlsrv_query($conn, $sqlid);
            while ($aa = sqlsrv_fetch_array($res2)) {
                $lista_turnos = $aa['id'];
            }
        } else {
            $sql = "SELECT * FROM bitacora_horarios where id_turno ='$lista_turnos' order by fecharegistro";
            $res = sqlsrv_query($conn, $sql);
            while ($aa = sqlsrv_fetch_array($res)) {
                $hora_inicio = date_format($aa['hora_inicio'], 'H:i:s');
                $hora_fin = date_format($aa['hora_fin'], 'H:i:s');
                $idActividad = $aa['idactividad'];
                if ($idActividad == '47C297B3-411E-445E-8357-797687831DC2') {
                    if ($FechaInicial_turno < $hora_inicio or $FechaFinal_turno > $hora_fin)
                        $bandera = 1;
                } else {
                    if (($FechaInicial_turno > $hora_inicio and $FechaInicial_turno < $hora_fin) or ($FechaFinal_turno > $hora_inicio and $FechaFinal_turno < $hora_fin) or
                        ($FechaInicial_turno < $hora_inicio and $FechaFinal_turno > $hora_fin)
                    ) {
                        $bandera = 1;
                    }
                }
            }
        }

        if ($bandera == 0) {
            $sql = "EXECUTE SAVE_bitacora_crear_turnos @idturno='$lista_turnos', @fecha_ini='$FechaInicial_turno', @fecha_fin='$FechaFinal_turno', @idactividad='$lista_actividades', @idusuario='$idusuario'";
            $res = sqlsrv_query($conn, $sql);
            if ($res) {
                $sql1 = "SELECT turnos_empleados.id_turno, descripcion from turnos_empleados  where id_turno ='$lista_turnos'";
                $res = sqlsrv_query($conn, $sql1);
                $data = [];
                while ($aa = sqlsrv_fetch_array($res)) {
                    $consecutivo = $aa['id_turno'];
                    $Nombre = $aa['descripcion'];
                    $registro = array('id' => $consecutivo, 'name' => $Nombre);
                    array_push($data, $registro);
                }
            }
            $json = (json_encode($data));
        } else
            $json = $bandera;
    }

    if ($_GET['band'] == 'list_UsuarioConsulta_actividad') {
        $sql = "SELECT u.NombreUsuarioLargo, u.idUsuario  from Jornada_Bitacora j inner join Usuarios u on j.idUsuario = u.idUsuario group by u.NombreUsuarioLargo, u.idUsuario";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idUsuario = ENCR::encript($aa['idUsuario']);
            $Nombre = utf8_encode($aa['NombreUsuarioLargo']);
            $registro = array('id' => $idUsuario, 'name' => $Nombre);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_Usuarios_info') {
        $texto_Usuario = $list_record['texto_Usuario'];
        $sql = "SELECT idUsuario,NombreUsuarioLargo FROM Usuarios WHERE habilitado=1";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idUsuario = ENCR::encript($aa['idUsuario']);
            $Nombre = utf8_encode($aa['NombreUsuarioLargo']);
            $registro = array('id' => $idUsuario, 'name' => $Nombre);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_Actividades') {
        $sql = "SELECT idActividad, Descripcion FROM Actividades  where idTipoActividad = '00000000-0000-0000-0000-000000000007' order by Descripcion";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idActividad = ENCR::encript($aa['idActividad']);
            $Descripcion = utf8_encode($aa['Descripcion']);
            $registro = array('id' => $idActividad, 'name' => $Descripcion);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_Negocio') {
        $sql = "SELECT idUnidadNegocio, Descripcion FROM Jornada_Bitacora_UnidadNegocio order by Descripcion";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idUnidadNegocio = ENCR::encript($aa['idUnidadNegocio']);
            $Descripcion = utf8_encode($aa['Descripcion']);
            $registro = array('id' => $idUnidadNegocio, 'name' => $Descripcion);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_Bitacora') {
        // $ocultarAcciones = true;
        $usuario = $list_record['usuario'];

        $id = ($list_record['id'] != '1' && $list_record['id'] != '') ? ENCR::descript($list_record['id']) : $list_record['id'];
        $condicional = '';
        if ($id != '' && $id != '1') {
            $condicional = "WHERE idUsuario = '$id' "; //and FechaInicial >= DATEADD(DAY,-2,getdate()) ";
        }
        // else if($id == ''){
        //     $condicional = 'WHERE FechaInicial >= DATEADD(DAY,-2,getdate())';
        // }
        $permiso_modificar = 0;
        $sql = "SELECT Permisos.idPermiso, UsuariosPermisos.Activo, Permisos.Descripcion, Permisos.Aplicacion, UsuariosPermisos.idUsuario, UsuariosPermisos.idEmpresa, Permisos.Permiso, UsuariosPermisos.Activo
        FROM UsuariosPermisos 
        RIGHT OUTER JOIN Permisos ON UsuariosPermisos.idPermiso = Permisos.idPermiso
        WHERE  UsuariosPermisos.idUsuario ='$usuario'  
            AND UsuariosPermisos.Activo='1' 
            AND permiso ='PERMISO MODIFICAR REGISTROS'";
        $res = sqlsrv_query($conn, $sql, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);

        if ($row_permiso > 0) {
            $permiso_modificar = 1;
        }

        $json = '';
        if ($condicional != '') {
            $sql = "SELECT * FROM vJornadaBitacora $condicional ORDER BY FechaFinal desc;";

            $res = sqlsrv_query($conn, $sql);
            $dataPermisos = ' <div class="table-responsive">
            <div id="div_tabla">
            <table id="idTable" class="table table-hover table-condensed table-bordered table-striped">
            <thead>
                <tr>              
                    <th class="text-center" data-column="acciones" style="vertical-align: middle">Acciones</th>              
                    <th class="text-center" data-column="Descripción">Descripción</th>
                    <th class="text-center" data-column="usuario">Usuario</th>
                    <th class="text-center" data-column="Activida">Actividad</th>
                    <th class="text-center" data-column="UnidadNegocio">Unidad de Negocio</th>
                    <th class="text-center" data-column="CentroTrabajo">Centro de Trabajo</th>
                    <th class="text-center" data-column="FechaFin" id="Tiquete">Tiquete</th>
                    <th class="text-center" data-column="FechaInicio" id="FechaInicioTabla">Fecha Inicial</th>
                    <th class="text-center" data-column="FechaFin" id="FechaFinTabla">Fecha Final</th>
                    <th class="text-center" data-column="Horas" id="Horas">Horas</th>
                </tr></thead><tbody>';

            $dataSinPermisos = ' <div class="table-responsive">
                <div id="div_tabla">
                <table id="idTable" class="table table-hover table-condensed table-bordered table-striped">
                <thead>
                    <tr>
                        <th class="text-center" data-column="Descripción">Descripción</th>
                        <th class="text-center" data-column="usuario">Usuario</th>
                        <th class="text-center" data-column="Activida">Actividad</th>
                        <th class="text-center" data-column="UnidadNegocio">Unidad de Negocio</th>
                        <th class="text-center" data-column="CentroTrabajo">Centro de Trabajo</th>
                        <th class="text-center" data-column="FechaFin" id="Tiquete">Tiquete</th>
                        <th class="text-center" data-column="FechaInicio" id="FechaInicioTabla">Fecha Inicial</th>
                        <th class="text-center" data-column="FechaFin" id="FechaFinTabla">Fecha Final</th>
                        <th class="text-center" data-column="Horas" id="Horas">Horas</th>
                    </tr></thead><tbody>';

            $permisos = 0;
            $data = '';

            while ($aa = sqlsrv_fetch_array($res)) {
                $Descripcion = utf8_encode($aa['Descripcion']);
                $idUsuario = utf8_encode($aa['Usuario']);
                $idActividad = utf8_encode($aa['Actividad']);
                $idUnidadNegocio = utf8_encode($aa['UnidadNegocio']);
                $idCentroTrabajo = utf8_encode($aa['CentroTrabajo']);
                $usuarioRegistra = utf8_encode($aa['UsuarioRegistra']);
                $FechaInicial = date_format($aa['FechaInicial'], 'Y-m-d H:i:s');
                $FechaFinal = date_format($aa['FechaFinal'], 'Y-m-d H:i:s');
                $idBitacora = ENCR::encript($aa['id_Bitacora']);
                $FechaRegistro = ($aa['FechaRegistro'] <> NULL) ? date_format($aa['FechaRegistro'], 'Y-m-d H:i:s') : 'N/A';
                $tiquete = number_format($aa['Tiquete_Registro']);
                $Horas =  substr(date_format($aa['Horas'], 'Y-m-d H:i:s'), 11, 5);
                $registro = array(
                    'Descripcion' => $Descripcion,
                    'idUsuario' => $idUsuario,
                    'idActividad' => $idActividad,
                    'idUnidadNegocio' => $idUnidadNegocio,
                    'idCentroTrabajo' => $idCentroTrabajo,
                    'usuarioRegistra' => $usuarioRegistra,
                    'FechaInicial' => (date_format($aa['FechaInicial'], 'Y-m-d') . 'T' . date_format($aa['FechaInicial'], 'H:i:s')),
                    'FechaFinal' => (date_format($aa['FechaFinal'], 'Y-m-d') . 'T' . date_format($aa['FechaFinal'], 'H:i:s')),
                    'idBitacora' => ENCR::descript($idBitacora),
                    'idTiquete' => $tiquete,
                    'Horas' => $Horas
                );

                if ($usuario == $aa['idUsuarioRegistra'] || $permiso_modificar == 1) {
                    $permisos = 1;
                    $data .= '<tr onclick(\'' . ENCR::descript($idBitacora) . '\')>';
                    $data .= '<td class="text-center"><button class="btn btn-warning" onclick=\'edit_button(' . json_encode($registro) . ');\'><span class="glyphicon glyphicon-pencil"></span></button>';
                    $data .= ' ';
                    $data .= '<button class="btn btn-danger" onclick=\'delete_button("' . $registro['idBitacora'] . '");\'><span class="glyphicon glyphicon-trash"></span></button></td>';
                }

                $data .= '<td><p title="Usuario: ' . $usuarioRegistra . ' - Fecha: ' . $FechaRegistro . '">' . $Descripcion . '</p></td>';
                $data .= '<td>' . $idUsuario . '</td>';
                $data .= '<td>' . $idActividad . '</td>';
                $data .= '<td>' . $idUnidadNegocio . '</td>';
                $data .= '<td>' . $idCentroTrabajo . '</td>';
                $data .= '<td>' . $tiquete . '</td>';
                $data .= '<td>' . $FechaInicial . '</td>';
                $data .= '<td>' . $FechaFinal . '</td>';
                $data .= '<td>' . $Horas . '</td>';
                $data .= '</tr>';
            }
            if ($permisos == 1 || $permiso_modificar == 1)
                $json = $dataPermisos . $data;
            else
                $json = $dataSinPermisos . $data;
            // $data = ''; 

            $json .= '</tbody></table></div></div>';

            // if ($permisos == 0){
            //     $json .=  $dataPermisos . $data; 
            // }else{
            //     $json .=  $dataSinPermisos . $data; 
            // }

        } else {
            $json = '';
        }
    }

    if ($_GET['band'] == 'delete_Bitacora') {
        $idBitacora = $list_record['idBitacora'];
        $sql = "EXEC DELETE_Jornada_Bitacora @id_Bitacora='{$idBitacora}'";
        $res = sqlsrv_query($conn, $sql);
        if ($res)
            $json = 1;
    }

    if ($_GET['band'] == 'delete_Usuario') {
        $idxid = ENCR::descript($list_record['idxid']);
        $sql = "EXEC DELETE_Usuario_Sueldos @idxid='{$idxid}'";
        $res = sqlsrv_query($conn, $sql);
        if ($res)
            $json = 1;
    }

    if ($_GET['band'] == 'generar_excel') {
        $id_Usuario = $_GET['idUsuario'];
        $fecha_Inicial = date_format($_GET['FechaInicial'], 'Y-m-d');
        $fecha_Final = date_format($_GET['FechaFinal'], 'Y-m-d');
        $sql = "SELECT * FROM vJornadaBitacora WHERE idUsuario='$id_Usuario' and FechaInicial >='$fecha_Inicial' and FechaFinal<='$fecha_Final' ;";
        $res = sqlsrv_query($conn, $sql);
        $data = ' <div class="table-responsive">
        <div id="div_tabla">
            <table id="tb_jornadaBitacora" class="table table-hover table-condensed table-bordered table-striped">
            <thead>
            <tr>      
            <th>Fecha Inicial</th>
            <th>Fecha Final</th>
            <th>Descripción</th>
            <th>Nombre de Usuario</th>
            <th>Activida</th>
            <th>Centro de Trabajo</th>
            <th>Usuario que Registra</th>
            </tr></thead><tbody>';
        while ($aa = sqlsrv_fetch_array($res)) {
            $idBitacora = ENCR::encript($aa['id_Bitacora']);
            $fechaInicial = date_format($aa['FechaInicial'], 'Y-m-d');
            $fechaFinal = date_format($aa['FechaFinal'], 'Y-m-d');
            $descripcion = $aa['Descripcion'];
            $nombreUsuario = $aa['NombreUsuarioLargo'];
            $actividad = $aa['Actividad'];
            $centroTrabajo = $aa['CentroTrabajo'];
            $usuarioRegistra = $aa['UsuarioRegistra'];
            $data .= '<tr>';
            $data .= '<td>' . $fechaInicial . '</td>';
            $data .= '<td>' . $fechaFinal . '</td>';
            $data .= '<td>' . $descripcion . '</td>';
            $data .= '<td>' . $nombreUsuario . '</td>';
            $data .= '<td>' . $actividad . '</td>';
            $data .= '<td>' . $centroTrabajo . '</td>';
            $data .= '<td>' . $usuarioRegistra . '</td>';
            $data .= '</tr>';
            $json .= $data;
            $data = '';
        }
        $json .= '</tbody></table></div></div>';
    }

    if ($_GET['band'] == 'save_Bitacora') {
        $message_error = "";
        $fecha2 = date('Y-m-d');
        $idusuarioRegistra = $list_record['idusuarioRegistra'];
        $descripcion = 'PARAMETRIZACION BITACORA';
        $nombre_usuario = '';
        $cantidad = 0;
        $id = '';
        $ruta = '';
        $observacion = $descripcion;
        $titulo = $descripcion;
        $tabla = '';
        $json_respons = 0;
        $json = "";
        $idBitacora = ($list_record['id_Bitacora'] == '') ? '00000000-0000-0000-0000-000000000000' : $list_record['id_Bitacora'];
        $idUsuario = ENCR::descript($list_record['idUsuario']);
        $idcentroTrabajo = resolverIdentificadorEntrada($list_record['idCentroTrabajo']);
        $idActividad = ENCR::descript($list_record['idActividad']);
        $idUnidadNegocio = ENCR::descript($list_record['idUnidadNegocio']);
        $horas_distribuir = $list_record['horas_distribuir'];
        $minutos_distribuir = $list_record['minutos_distribuir'];
        $Hora_pendientes = $list_record['Hora_pendientes'];

        // $idTiquete = $list_record['idTiquete'];
        $Descripcion = $list_record['Descripcion'];
        $idusuarioRegistra = $list_record['idusuarioRegistra'];
        $tiquete = $list_record['idTiquete'];

        /* $sql_user ="SELECT Identificacion FROM UsuariosDetalle WHERE idUsuario='$idUsuario'";
        $res= sqlsrv_query($conn,$sql_user);
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        $Identificacion = $row['Identificacion'];
        
        $sqlresult = "SELECT dbo.GET_AplicaFechaBitacora('$fechaInicial', '$fechaFinal', '$idUsuario', '$idBitacora')";
        echo $sqlresult;
        $result = sqlsrv_query($conn, $sqlresult);
        if (sqlsrv_fetch($result) === true) {
            $valorRetorno = sqlsrv_get_field($result, 0); // El índice 0 corresponde al primer y único valor retornado
        }
        $valorRetorno = utf8_encode($valorRetorno);
        if ($valorRetorno == '') { */
        /*     $sql = "EXECUTE SAVE_JornadaBitacora @idBitacora='$idBitacora', @idUsuario='$idUsuario', @idCentroTrabajo='$idcentroTrabajo', @idActividad='$idActividad', 
               @idUnidadNegocio='$idUnidadNegocio',@fechaInicio='$fechaInicial',@fechaFinal='$fechaFinal',@descripcion='$Descripcion',@idUsuario_Registra='$idusuarioRegistra',
               @tiquete='$tiquete', @horas:$horas_distribuir;";
        */
        $sql = "EXECUTE SAVE_JornadaBitacora @idBitacora='$idBitacora', @idUsuario='$idUsuario', @idCentroTrabajo='$idcentroTrabajo', @idActividad='$idActividad', 
              @descripcion='$Descripcion',@idUsuario_Registra='$idusuarioRegistra', @idUnidadNegocio='$idUnidadNegocio',
              @tiquete='$tiquete', @horas='$horas_distribuir', @minutos='$minutos_distribuir';";
        //     $res = sqlsrv_query($conn, $sql);
        $registro = array(
            'response' => 1,
            'message_error' => $message_error
        );

        /*   } else {
            $message_error = $valorRetorno;
            $registro = array(
                'response' => 0,
                'message_error' => $message_error
            );
        } */
        $json = json_encode($registro);
    }

    if ($_GET['band'] == 'save_Sueldo') {
        $idxid = ($list_record['idxid'] == '') ? '00000000-0000-0000-0000-000000000000' : ENCR::descript($list_record['idxid']);
        $idUsuario = ENCR::descript($list_record['idUsuario']);
        $idusuarioRegistra = $list_record['idusuarioRegistra'];
        $sueldo = $list_record['Sueldo'];
        $fecha = $list_record['Fecha'];
        $json = 0;
        $sqlresult = "SELECT dbo.GET_AplicaFechaSueldo('$fecha', '$idUsuario', '$idxid')";
        $result = sqlsrv_query($conn, $sqlresult);
        if (sqlsrv_fetch($result) === true) {
            $valorRetorno = sqlsrv_get_field($result, 0);
        }
        $valorRetorno = utf8_encode($valorRetorno);
        if ($valorRetorno == '') {
            $sql = "EXECUTE SAVE_UsuarioSueldos
            @idxid='$idxid',
            @idUsuario='$idUsuario',
            @sueldo='$sueldo',
            @fecha='$fecha',
            @usuarioRegistra='$idusuarioRegistra';";
            $res = sqlsrv_query($conn, $sql);
            $registro = array(
                'response' => 1
            );
        } else {
            $message_error = $valorRetorno;
            $registro = array(
                'response' => 0,
                'message_error' => $message_error
            );
        }
        $json = json_encode($registro);
    }

    if ($_GET['band'] == 'table_usuarioSueldo') {
        $json = '';
        $id = ($list_record['id'] != '1' && $list_record['id'] != '') ? ENCR::descript($list_record['id']) : $id = $list_record['id'];
        $condicional = '';
        if ($id != '' && $id != '1') {
            $condicional = "WHERE idUsuario = '$id'";
        } else if ($id == '') {
            $condicional = '';
        }
        $json = '';
        if ($condicional != '' || $id != '1') {
            $id = ENCR::descript($list_record['id']);
            $sql = "SELECT * FROM vUsuarios_sueldos $condicional ORDER BY Fecha DESC, Usuario DESC";
            $res = sqlsrv_query($conn, $sql);
            $json = '<br>
            <table id="idTable_usuarioSueldo" class="table table-hover table-condensed table-bordered table-striped">
            <thead>
            <tr>
            <th style="vertical-align: middle;">Acciones</th>
            <th>Usuario</th>
            <th>Sueldo</th>
            <th>Fecha</th>
            </tr></thead><tbody>';
            while ($aa = sqlsrv_fetch_array($res)) {
                $NombreUsuarioLargo = utf8_encode($aa['Usuario']);
                $NombreUsuarioRegistro = utf8_encode($aa['Usuario_registro']);
                $FechaRegistro = date_format($aa['Fecha_registro'], 'Y-m-d H:i:s');
                $Sueldo = number_format($aa['Sueldo'], 0, '', ',');
                $Fecha = date_format($aa['Fecha'], 'Y-m-d');
                $idxid = ENCR::encript($aa['idxid']);
                $idUsuario = ENCR::encript($aa['idUsuario']);
                $registro = array(
                    'Sueldo' => $aa['Sueldo'],
                    'FechaSueldo' => $Fecha,
                    'idxid' => $idxid,
                    'NombreUsuarioLargo' => $NombreUsuarioLargo,
                    'idUsuarioSueldo' => $idUsuario
                );
                $json .= '<tr onclick(\'' . ENCR::descript($idxid) . '\')>';
                $json .= '<td class="text-center"><button class="btn btn-warning" onclick=\'edit_button_usuario(' . json_encode($registro) . ');\'><span class="glyphicon glyphicon-pencil"></span></button>';
                $json .= ' ';
                $json .= '<button class="btn btn-danger" onclick=\'delete_usuario_button("' . $registro['idxid'] . '");\'><span class="glyphicon glyphicon-trash"></span></button></td>';
                $json .= '<td><p title="Usuario: ' . $NombreUsuarioRegistro . ' - Fecha: ' . $FechaRegistro . '">' . $NombreUsuarioLargo . '</p></td>';
                $json .= '<td>' . $Sueldo . '</td>';
                $json .= '<td>' . $Fecha . '</td>';
                $json .= '</tr>';
            }
            $json .= '</tbody></table></div></div>';
        } else {
            $json = 2;
        }
    }

    if ($_GET['band'] == 'get_CentrosDeTrabajoConsulta') {
        $text = $list_record['texto_CentroConsulta'];
        if (count($list_record) === 1 && isset($list_record['texto_CentroConsulta'])) {
            $where = "WHERE CentroTrabajo like '%$text%'";
        } else {
            $where = "WHERE CentroTrabajo like '%$text%' AND ";
            $clausulas = [];
            foreach ($list_record as $clave => $valor) {
                if ($clave != 'texto_CentroConsulta') {
                    $clausulas[] = "$clave = '" . ENCR::descript($valor) . "'";
                }
            }
            if (!empty($clausulas)) {
                $where .= implode(" AND ", $clausulas);
            }
        }
        $sql = "SELECT idCentroTrabajo,CentroTrabajo FROM vjornadabitacora $where";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idCentroTrabajo = ENCR::encript($aa['idCentroTrabajo']);
            $CentroTrabajo = utf8_encode($aa['CentroTrabajo']);
            $registro = array('id' => $idCentroTrabajo, 'name' => $CentroTrabajo);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_NegocioConsulta') {
        $text = $list_record['texto_NegocioConsulta'];
        if (count($list_record) === 1 && isset($list_record['texto_NegocioConsulta'])) {
            $where = "WHERE UnidadNegocio like '%$text%'";
        } else {
            $where = "WHERE UnidadNegocio like '%$text%' AND ";
            $clausulas = [];
            foreach ($list_record as $clave => $valor) {
                if ($clave != 'texto_NegocioConsulta') {
                    $clausulas[] = "$clave = '" . ENCR::descript($valor) . "'";
                }
            }

            if (!empty($clausulas)) {
                $where .= implode(" AND ", $clausulas);
            }
        }
        // $clausulas = 'WHERE' + $clausulas;
        $sql = "SELECT idUnidadNegocio,UnidadNegocio FROM vjornadabitacora $where";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idUnidadNegocio = ENCR::encript($aa['idUnidadNegocio']);
            $UnidadNegocio = utf8_encode($aa['UnidadNegocio']);
            $registro = array('id' => $idUnidadNegocio, 'name' => $UnidadNegocio);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_Empresa') {
        $text = $list_record['texto_empresa'];
        if (count($list_record) === 1 && isset($list_record['texto_empresa'])) {
            $where = "WHERE RazonSocial like '%$text%'";
        } else {
            $where = "WHERE RazonSocial like '%$text%' AND ";
            $clausulas = [];
            foreach ($list_record as $clave => $valor) {
                if ($clave != 'texto_empresa') {
                    $clausulas[] = "$clave = '" . ENCR::descript($valor) . "'";
                }
            }

            if (!empty($clausulas)) {
                $where .= implode(" AND ", $clausulas);
            }
        }
        // $clausulas = 'WHERE' + $clausulas;
        $sql = "SELECT idProveedor, RazonSocial FROM traz.dbo.proveedores $where  and empresa=1";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idUnidadNegocio = ENCR::encript($aa['idProveedor']);
            $UnidadNegocio = utf8_encode($aa['RazonSocial']);
            $registro = array('id' => $idUnidadNegocio, 'name' => $UnidadNegocio);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_ActividadesConsulta') {
        $text = $list_record['texto_ActividadConsulta'];
        if (count($list_record) === 1 && isset($list_record['texto_ActividadConsulta'])) {
            $where = "WHERE Actividad like '%$text%'";
        } else {
            $where = "WHERE Actividad like '%$text%' AND ";
            $clausulas = [];
            foreach ($list_record as $clave => $valor) {
                if ($clave != 'texto_ActividadConsulta') {
                    $clausulas[] = "$clave = '" . ENCR::descript($valor) . "'";
                }
            }
            if (!empty($clausulas)) {
                $where .= implode(" AND ", $clausulas);
            }
        }
        // $clausulas = 'WHERE' + $clausulas;
        $sql = "SELECT idActividad,Actividad FROM vjornadabitacora $where";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idActividad = ENCR::encript($aa['idActividad']);
            $Actividad = utf8_encode($aa['Actividad']);
            $registro = array('id' => $idActividad, 'name' => $Actividad);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_Consulta') {
        $FechaInicial = $list_record['FechaInicial'];
        $FechaFinal = $list_record['FechaFinal'];
        $usuario = $list_record['idMiUsuario'];
        $json = '';
        $whereFin = "WHERE FechaInicial BETWEEN '$FechaInicial' AND  '$FechaFinal'  ";
        $where = '';
        $sql = "SELECT Permisos.idPermiso, UsuariosPermisos.Activo, Permisos.Descripcion, Permisos.Aplicacion, UsuariosPermisos.idUsuario, UsuariosPermisos.idEmpresa, Permisos.Permiso, UsuariosPermisos.Activo
            FROM UsuariosPermisos 
            RIGHT OUTER JOIN  Permisos ON UsuariosPermisos.idPermiso = Permisos.idPermiso
            WHERE  UsuariosPermisos.idUsuario ='$usuario'  
            AND UsuariosPermisos.Activo='1' 
            AND permiso ='CONSULTAR TODOS'";

        $res = sqlsrv_query($conn, $sql, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);

        if ($row_permiso == 0) {
            $whereFin .= " AND  vJornadaBitacora.idUsuario = '$usuario'  ";
        }

        $clausulas = [];
        foreach ($list_record as $clave => $valor) {
            $clave . '  -';
            if (($clave != 'FechaInicial') && ($clave != 'FechaFinal') && ($clave != 'idMiUsuario')) {
                $clausulas[] = "vJornadaBitacora.$clave = '" . ENCR::descript($valor) . "'";
            }
        }

        if (!empty($clausulas)) {
            $where .= " AND " . implode(" AND ", $clausulas);
            // $where = 'AND ' . $where;
        }

        $whereFin .= ' ' . $where;
        $sql = "SELECT UnidadNegocio,Departamentos.Descripcion AS Zona,UsuariosDetalle.Identificacion,Usuario,UsuariosDetalle.Cargo,Actividad,CentroTrabajo, DATEADD(SECOND, DATEDIFF(SECOND, '1900-01-01', Horas) +  DATEDIFF(SECOND, '1900-01-01', Horas), 
                '1900-01-01') as Horas,
            vJornadaBitacora.Costo_hora,sum(Costo_actividad) as Costo_actividad,Sueldo
            FROM vJornadaBitacora
            INNER JOIN Destino ON vJornadaBitacora.idCentroTrabajo=Destino.idDestino
            INNER JOIN Departamentos ON Destino.idDepartamento=Departamentos.idDepartamento
            INNER JOIN UsuariosDetalle ON vJornadaBitacora.idUsuario=UsuariosDetalle.idUsuario
            $whereFin 
            GROUP BY UnidadNegocio,Departamentos.Descripcion,UsuariosDetalle.Identificacion,Usuario,UsuariosDetalle.Cargo,Actividad,CentroTrabajo,vJornadaBitacora.Costo_hora,Sueldo, horas
            ORDER BY UnidadNegocio,Departamentos.Descripcion,Usuario,CentroTrabajo";
        $res = sqlsrv_query($conn, $sql);
        $data = ' <div class="table-responsive">
            <div id="div_tablaConsulta">
            <table id="idTableConsulta" class="table table-hover table-condensed table-bordered table-striped">
            <thead>
            <tr>';
        $data .= '<th class="text-center" data-column="Nombre">Nombre</th>';
        $data .= '<th class="text-center" data-column="Localizacion">Centro Trabajo</th>';
        $data .= '<th class="text-center" data-column="Cargo">Cargo</th>';
        $data .= '<th class="text-center" data-column="Fechai">Hora</th>';
        $data .= '</tr></thead>';
        $data .= '<tbody><tr>';
        while ($aa = sqlsrv_fetch_array($res)) {
            $UnidadNegocio = utf8_encode($aa['UnidadNegocio']);
            $Zona = utf8_encode($aa['Zona']);
            $Identificacion = $aa['Identificacion'];
            $Usuario = utf8_encode($aa['Usuario']);
            $Cargo = utf8_encode($aa['Cargo']);
            $Actividad = utf8_encode($aa['Actividad']);
            $CentroTrabajo = utf8_encode($aa['CentroTrabajo']);
            //$Horas = utf8_encode($aa['Horas']);
            $Horas = substr(date_format($aa['Horas'], 'Y-m-d H:i:s'), 11, 5);
            $Costo_hora = utf8_encode(number_format($aa['Costo_hora'], 2));
            $Costo_actividad = utf8_encode(number_format($aa['Costo_actividad'], 2));
            $Sueldo = utf8_encode(number_format($aa['Sueldo'], 2));
            $registro = array(
                'Usuario' => $Usuario,
                'CentroTrabajo' => $CentroTrabajo,
                'Cargo' => $Cargo,
                'Horas' => $Horas,
            );
            foreach ($registro as $clave => $valor) {
                $data .= '<td>' . (empty($valor) ? '' : htmlspecialchars($valor)) . '</td>';
            }
            $data .= '</tr>';

            $json .= $data;
            $data = '';
        }
        $json .= '</tbody></table></div></div>';
    }

    if ($_GET['band'] == 'get_dispositivos') {
        $sql = "SELECT dispositivo from biometrico.dbo.bitacora group by dispositivo";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $dispositivo = ENCR::encript($aa['dispositivo']);
            $dispositivo = utf8_encode($aa['dispositivo']);
            $registro = array('id' => $dispositivo, 'name' => $dispositivo);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'get_Consulta_gral') {
        $FechaInicial = $list_record['FechaInicial'];
        $FechaFinal = $list_record['FechaFinal'];
        $list_empresa = ENCR::descript($list_record['list_empresa']);
        $list_CentroConsulta = $list_record['list_CentroConsulta'];
        $list_UsuarioConsulta = ENCR::descript($list_record['list_UsuarioConsulta']);
        if ($list_CentroConsulta <> '')
            $centro = "'" . $list_CentroConsulta . "'";
        else
            $centro = 'null';

        if ($list_UsuarioConsulta <> '')
            $user = "'" . $list_UsuarioConsulta . "'";
        else
            $user = 'null';
        $json = '';
        $sql = "SELECT * from traz.dbo.lista_bitacora ('$FechaInicial','$FechaFinal','$list_empresa'," . $centro . "," . $user . ")";

        $res = sqlsrv_query($conn, $sql, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);

        $data = ' <div class="table-responsive">
            <div id="div_tablaConsulta">
            <table id="idTableConsulta" class="table table-hover table-condensed table-bordered table-striped">
            <thead>
            <tr>';
        $data .= '<th class="text-center" data-column="Nombre">Nombre</th>';
        $data .= '<th class="text-center" data-column="dis">Dispositivo</th>';
        $data .= '<th class="text-center" data-column="Tiquete">Tiquete</th>';
        $data .= '<th class="text-center" data-column="HoraEntrada">Hora Entrada</th>';
        $data .= '<th class="text-center" data-column="HoraSalida">Hora Salida</th>';
        $data .= '<th class="text-center" data-column="Fechai">Hora</th>';

        $data .= '</tr></thead>';
        $data .= '<tbody>';
        while ($aa = sqlsrv_fetch_array($res)) {
            $id = utf8_encode($aa['id']);
            $Nombre = utf8_encode($aa['nombre']);
            $consecutivo = $aa['consecutivo'];
            $año = $aa['ann'];
            $hora_entrada = date_format($aa['hora_entrada'], 'Y-m-d H:i:s');
            $hora_salida = date_format($aa['hora_salida'], 'Y-m-d H:i:s');
            $horas = substr(date_format($aa['horas'], 'Y-m-d H:i:s'), 11, 5);
            $dispositivo = utf8_encode($aa['dispositivo']);
            $tarde = utf8_encode($aa['tarde']);
            $data .= '<tr onclick=\'mostrar_detalle("' . $año . '","' . $consecutivo . '","' . $list_empresa . '","' . $id . '","' . $Nombre . '");\'>';
            $tiquete = $año . '-' . $consecutivo;
            $registro = array(
                'Nombre' => $Nombre,
                'dispositivo' => $dispositivo,
                'tiquete' => $tiquete,
                'hora_entrada' => $hora_entrada,
                'Actividad' => $hora_salida,
                'Horas' => $horas,
            );
            foreach ($registro as $clave => $valor) {
                if ($tarde == 0)
                    $data .= '<td>' . (empty($valor) ? '' : htmlspecialchars($valor)) . '</td>';
                else
                    $data .= '<td style="background-color:#f9e3a9;">' . (empty($valor) ? '' : htmlspecialchars($valor)) . '</td>';
            }
            $data .= '</tr>';
            $json .= $data;
            $data = '';
        }
        $json .= '</tbody></table></div></div>';
    }

    if ($_GET['band'] == 'mostrar_detalle') {
        $año = $list_record['año'];
        $tiquete = $list_record['tiquete'];
        $cedula = $list_record['cedula'];
        $empresa = $list_record['empresa'];
        $data = '';
        $sql1 = "SELECT * FROM biometrico.dbo.bitacora where consecutivo = $tiquete and año=$año order by date_time "; // AND MARCADO =0 
        $res = sqlsrv_query($conn, utf8_decode($sql1));
        $data = ' <div class="table-responsive">
            <div id="div_tablaConsulta_biometrico">
            <table id="idTableConsulta_biometrico" class="table table-hover table-condensed table-bordered table-striped">
            <thead>
                <tr>
                    <th class="text-center" data-column="Dispositivo">Dispositivo</th>
                    <th class="text-center" data-column="Fecha">Fecha</th>
                    <th class="text-center" data-column="Hora">Hora</th>
                    <th class="text-center" data-column="Tipo_mov">Tipo Movimiento</th>
                    <th class="text-center" data-column="acciones">Accion</th>
                </tr>
            </thead>';
        $data .= '<tbody>';
        while ($aa = sqlsrv_fetch_array($res)) {
            //  $consecutivo = $aa['consecutivo'];
            $access_date = date_format($aa['access_date'], 'Y-m-d');
            $access_time = date_format($aa['access_time'], 'H:i:s');
            $DateTime = date_format($aa['date_time'], 'Y-m-d H:i:s');
            $Estado_real = strtoupper($aa['Estado_real']);
            $dispositivo = $aa['dispositivo'];
            $data . '<tr>';
            $registro = array(
                'dispositivo' => $dispositivo,
                'access_date' => $access_date,
                'access_time' => $access_time,
                'Estado_real' => $Estado_real,
            );
            //  array_push($data, $registro);
            foreach ($registro as $clave => $valor) {
                $data .= '<td>' . (empty($valor) ? '' : htmlspecialchars($valor)) . '</td>';
            }
            if ($Estado_real == 'PENDIENTE') {
                $data .= '<td><center><button class="btn btn-success" onclick=\'Modificar_Turnos_asignado("' . $DateTime . '");\'><span class="glyphicon glyphicon-pencil"></span></button></center></td>';
            }
            $data .= '</tr>';
            $json .= $data;
            $data = '';
        }
        $sql_usuario = "SELECT idUsuario from UsuariosDetalle where Identificacion='$cedula'";
        $res_user = sqlsrv_query($conn, $sql_usuario);
        while ($au = sqlsrv_fetch_array($res_user)) {
            $id = $au['idUsuario'];
        }
        $sql_turno = "SELECT  top(1)Descripcion FROM turnos_empleados inner join BitacoraTurnos on turnos_empleados.id_turno= BitacoraTurnos.idTurno and '$access_date' between FechaInicio and FechaFin 
            and idUsuario='$id'";
        $res_user = sqlsrv_query($conn, $sql_turno, $params, $options);
        $filas = sqlsrv_num_rows($res_user);
        if ($filas > 0) {
            while ($aa = sqlsrv_fetch_array($res_user)) {
                $descripcion = $aa['Descripcion'];
            }
        } else
            $descripcion = 'No tiene Turno Asignado';

        $json .= '</tbody></table></div></div>';
        $json = $json . '||' . $descripcion;
    }

    if ($_GET['band'] == 'grabar_correccion') {
        $fecha_detalle = $list_record['fecha_detalle'];
        $hora_detalle = $list_record['hora_detalle'];
        $salida = $list_record['salida'];
        $turno = $list_record['turno'];
        $user_tiquete = $list_record['user_tiquete'];
        $usuario = $list_record['usuario'];
        $datetime = $fecha_detalle . ' ' . $hora_detalle;
        $user = "SELECT NombreCompleto from UsuariosDetalle  WHERE idUsuario='$usuario'";
        $res = sqlsrv_query($conn, utf8_decode($user));
        while ($aa = sqlsrv_fetch_array($res)) {
            $nombre = utf8_encode($aa['NombreCompleto']);
        }
        echo $sql = "UPDATE biometrico.dbo.bitacora set access_date='$fecha_detalle', date_time='$datetime', access_time='$hora_detalle', Estado_real='$salida'  where date_time='$turno' AND Estado_real='Pendiente'";
        $res = sqlsrv_query($conn, utf8_decode($sql));
        $sql = "INSERT INTO logs (text) values('Se cambio la fecha=$fecha_detalle, Hora=$hora_detalle,  y Estado real= Salida, del tiquete=$user_tiquete  modificado por $nombre')";
        $res = sqlsrv_query($conn, utf8_decode($sql));
    }

    if ($_GET['band'] == 'obtener_tiquete') {
        $idUsuario = ENCR::descript($_GET['idUsuario']);
        $sql1 = "SELECT Identificacion FROM TRAZ.DBO.UsuariosDetalle  WHERE  idUsuario ='$idUsuario'";
        $res = sqlsrv_query($conn, $sql1);
        while ($aa = sqlsrv_fetch_array($res)) {
            $Identificacion = $aa['Identificacion'];
        }

        $sql = "SELECT CONCAT(año,'-',consecutivo) AS consecutivo, CONCAT(año,'-',consecutivo) AS Nombre FROM biometrico.dbo.bitacora WHERE id =$Identificacion 
            AND MARCADO =0 and consecutivo is not null group by año,consecutivo ORDER BY consecutivo";
        $res = sqlsrv_query($conn, utf8_decode($sql));
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $consecutivo = $aa['consecutivo'];
            $Nombre = $aa['Nombre'];
            $registro = array('id' => $consecutivo, 'name' => $Nombre);
            array_push($data, $registro);
        }
        $json = (json_encode($data));
    }

    if ($_GET['band'] == 'buscar_horas') {
        $idTiquete = $list_record['idTiquete'];
        $usuario = ENCR::descript($list_record['usuario']);
        $sql = "SELECT traz.dbo.buscar_horas ('$idTiquete', '$usuario') AS tiempo";
        $res = sqlsrv_query($conn, utf8_decode($sql));
        while ($aa = sqlsrv_fetch_array($res)) {
            $tiempo = substr(date_format($aa['tiempo'], 'Y-m-d H:i:s'), 11, 5);
        }
        $json = $tiempo;
    }

    if ($_GET['band'] == 'buscar_horas_pendientes') {
        $idTiquete = $list_record['idTiquete'];
        $usuario = ENCR::descript($list_record['usuario']);
        $sql = "SELECT traz.dbo.Buscar_horas_pendientes ('$idTiquete', '$usuario') AS tiempo";
        $res = sqlsrv_query($conn, utf8_decode($sql));
        while ($aa = sqlsrv_fetch_array($res)) {
            $tiempo = substr(date_format($aa['tiempo'], 'Y-m-d H:i:s'), 11, 5);
        }
        $json = $tiempo;
    }

    if ($_GET['band'] == 'buscar_detalle') {
        $data = '';
        $tiquete = $list_record['tiquete'];
        $año = $list_record['año'];
        $usuario = ENCR::descript($list_record['usuario']);
        $sql1 = "SELECT Identificacion FROM TRAZ.DBO.UsuariosDetalle  WHERE  idUsuario ='$usuario'";
        $res = sqlsrv_query($conn, $sql1);

        while ($aa = sqlsrv_fetch_array($res)) {
            $Identificacion = $aa['Identificacion'];
        }
        $sql = "SELECT * FROM biometrico.dbo.bitacora WHERE id='$Identificacion' and consecutivo=$tiquete and año=$año order by date_time ";
        $res = sqlsrv_query($conn, utf8_decode($sql), $params, $options);
        $row_permiso = sqlsrv_num_rows($res);
        if ($row_permiso > 0) {
            $data .= '<div class="table-responsive">
                    <div id="div_tabla_detalles">
                        <table id="idTabledetalle" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data .= '<th class="text-center" data-column="entrada">Hora Entrada</th>
                    <th class="text-center" data-column="salida">Hora Salida</th>
                    <th class="text-center" data-column="hora">Total Horas</th>
                    <th class="text-center" data-column="dis">Dispositivo</th>
                    </tr></thead>
                    <tbody>
                <tr>';

            $hora_parcial = '';
            $bandera = 0;
            while ($aa = sqlsrv_fetch_array($res)) {
                $Estado_real = $aa['Estado_real'];
                $Dispositivo = $aa['dispositivo'];
                $hora = date_format($aa['date_time'], "Y-m-d H:i:s");
                if ($Estado_real == 'Entrada') {
                    $hora_parcial = $hora;
                    $data .= '<td>' . $hora_parcial . '</td>';
                    $bandera++;
                } else if ($Estado_real == 'Salida') {
                    $sqll = "SELECT traz.dbo.calcular_hora ('$hora_parcial', '$hora') AS tiempo";
                    $ress = sqlsrv_query($conn, utf8_decode($sqll));
                    while ($ah = sqlsrv_fetch_array($ress)) {
                        $tiempo = substr(date_format($ah['tiempo'], "Y-m-d H:i:s"), 11, 5);
                    }
                    $data .= '<td>' . $hora . '</td>';
                    $data .= '<td>' . $tiempo . '</td>';
                    $data .= '<td>' . $Dispositivo . '</td>';
                    if ($bandera <> $row_permiso)
                        $data .= '<tr> ';
                    $bandera++;
                } else {
                    $data .= '<td>1900-01-01</td>';
                    $data .= '<td>0</td>';
                    $data .= '<td>' . $Dispositivo . '</td>';
                    $data .= '<td>No marco Salida</td> </tr>';
                    if ($bandera <> $row_permiso)
                        $data .= '<tr> ';
                    $bandera++;
                }
            }
            $data .= '</tbody></table></div></div>';
        }
        $json = $data;
    }

    if ($_GET['band'] == 'buscar_detalle_tiquete') {
        $idTiquete = $list_record['idTiquete'];
        $usuario = ENCR::descript($list_record['usuario']);

        $sql1 = "SELECT d.Descripcion centro_trabajo, a.Descripcion actividad, u.Descripcion unidadNeg,
            fechafinal-fechainicial as tiempo
        FROM Jornada_Bitacora J 
        inner join Destino D on j.idCentroTrabajo = D.idDestino
        inner join Actividades A on j.idActividad=a.idActividad 
        inner join Jornada_Bitacora_UnidadNegocio U on j.idUnidadNegocio = U.idUnidadNegocio
        WHERE J.idUsuario='$usuario' and Tiquete_Registro='$idTiquete' order by Tiquete_Registro, j.FechaInicial";
        $res = sqlsrv_query($conn, $sql1, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);
        if ($row_permiso > 0) {
            $data = '<div class="table-responsive">
                    <div id="div_tabla_detalles">
                        <table id="idTabledetalle" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data .= '<th class="text-center" data-column="centro">Centro Trabajo</th>
                    <th class="text-center" data-column="actividad">Actividad</th>
                    <th class="text-center" data-column="negocio">Negocio</th>
                    <th class="text-center" data-column="tiempo">Tiempo</th>
                    </tr></thead>
                    <tbody>';

            while ($aa = sqlsrv_fetch_array($res)) {
                $centro_trabajo = utf8_encode($aa['centro_trabajo']);
                $actividad = utf8_encode($aa['actividad']);
                $unidadNeg = utf8_encode($aa['unidadNeg']);
                $tiempo = substr(date_format($aa['tiempo'], "Y-m-d H:i:s"), 11, 5);
                $data .= '<tr><td>' . $centro_trabajo . '</td>';
                $data .= '<td>' . $actividad . '</td>';
                $data .= '<td>' . $unidadNeg . '</td>';
                $data .= '<td>' . $tiempo . '</td></tr>';
            }
            $data .= '</tbody></table></div></div>';
        } else
            $data = '';
        $json = $data;
    }

    if ($_GET['band'] == 'buscar_detalle_t') {
        $turno = $list_record['turno'];
        $op = $list_record['op'];
        $count = 0;
        if ($op == 1) {
            $sql = "SELECT * from bitacora_horarios left join BitacoraTurnos on bitacora_horarios.id_turno = BitacoraTurnos.idturno 
                inner join turnos_empleados on bitacora_horarios.id_turno = turnos_empleados.id_turno where BitacoraTurnos.idxid is not null and idTurno='$turno'";
            $res = sqlsrv_query($conn, $sql, $params, $options);
            $count = sqlsrv_num_rows($res);
            if ($count > 0)
                $count = 1;
        }

        // $usuario = ENCR::descript($list_record['usuario']);
        $usuario = '22954799-f18d-4b80-aae2-6868cf053354';
        $sql1 = "SELECT * FROM bitacora_horarios inner join Actividades on Bitacora_horarios.idactividad = Actividades.idActividad  WHERE id_turno ='$turno' ORDER BY FechaRegistro";
        $res = sqlsrv_query($conn, $sql1, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);
        $data = '';
        if ($row_permiso > 0) {
            $data .= '<div class="table-responsive">
                    <div id="div_tabla_detalle">
                        <table id="idTabledetalle_detalle" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data .= '<th class="text-center" data-column="hora">Turno</th>
                        <th class="text-center" data-column="entrada">Hora Entrada</th>
                        <th class="text-center" data-column="salida">Hora Salida</th>
                    </tr></thead>
                    <tbody>
                <tr>';
            while ($aa = sqlsrv_fetch_array($res)) {
                $descripcion = utf8_encode($aa['Descripcion']);
                $hora_inicio = date_format($aa['hora_inicio'], 'H:i:s');
                $hora_fin = date_format($aa['hora_fin'], 'H:i:s');
                $data .= '<td>' . $descripcion . '</td>';
                $data .= '<td>' . $hora_inicio . '</td>';
                $data .= '<td>' . $hora_fin . '</td>
                    </tr>';
            }
            $data .= '</tbody></table></div></div>';
        }
        $json = $data . '||' . $count;
    }

    if ($_GET['band'] == 'get_turnos_user_activo') {
        //  $turno = $list_record['texto_Usuario'];
        $usuario = ENCR::descript($list_record['usuario']);
        $sql = "SELECT BitacoraTurnos.idxid,BitacoraTurnos.idusuario, Bitacora_horarios.id_turno,turnos_empleados.descripcion,FechaInicio, FechaFin, dias, destino.Descripcion as centro from bitacora_horarios 
            inner join turnos_empleados on bitacora_horarios.id_turno = turnos_empleados.id_turno 
            inner join UsuariosDetalle on bitacora_horarios.idUsuario = UsuariosDetalle.idUsuario
            inner join BitacoraTurnos on Bitacora_horarios.id_turno = BitacoraTurnos.idTurno
            left join destino on BitacoraTurnos.idCentroTrabajo = Destino.idDestino
            WHERE BitacoraTurnos.idusuario='$usuario' and idactividad='47C297B3-411E-445E-8357-797687831DC2'
                and (FechaFin>=cast(GETDATE() as date) or FechaFin='1900-01-01')";
        $res = sqlsrv_query($conn, $sql, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);
        $data = '';
        if ($row_permiso > 0) {
            $data .= '<div class="table-responsive">
                    <div id="div_tabla_detalle">
                        <table id="idTabledetalle" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data .= '<th data-column="centro">Centro Trabajo</th>
                    <th data-column="hora">Turno</th>
                    <th data-column="fi">Fecha Inicio</th>
                    <th data-column="ff">Fecha Fin</th>
                    <th class="text-center" data-column="salida">Dias Asignados</th>
                    <th class="text-center" data-column="salida">opcion</th>
                </tr></thead>
                <tbody>';
            $id = '';
            $idxid_temp = '';
            $dias_semana = '';
            $nombre_tem = '';
            $destino_tem = '';
            $fechaI_tem = '';
            $fechaF_tem = '';
            while ($aa = sqlsrv_fetch_array($res)) {
                $id_turno = $aa['id_turno'];
                $idxid = $aa['idxid'];
                $nombre = utf8_encode($aa['descripcion']);
                $centro = utf8_encode($aa['centro']);
                $FechaInicio = date_format($aa['FechaInicio'], 'Y-m-d');
                $FechaFin = date_format($aa['FechaFin'], 'Y-m-d');
                $dias = utf8_encode($aa['dias']);
                if ($id == '') {
                    $nombre_tem = $nombre;
                    $destino_tem = $centro;
                    $fechaI_tem = $FechaInicio;
                    $fechaF_tem = $FechaFin;
                    $dias_semana = $dias;
                    $id = $id_turno;
                    $idxid_temp = $idxid;
                } elseif (($id == $id_turno) && ($fechaI_tem == $FechaInicio) && ($fechaF_tem == $FechaFin)) {
                    $dias_semana = $dias_semana . ', ' . $dias;
                    $idxid_temp .= ',' . $idxid;
                } else {
                    $data .= ' <tr><td>' . $destino_tem . '</td>';
                    $data .= '<td>' . $nombre_tem . '</td>';
                    $data .= '<td>' . $fechaI_tem . '</td>';
                    $data .= '<td>' . $fechaF_tem . '</td>';
                    $data .= '<td>' . $dias_semana . '</td>';
                    $data .= '<td><center><button class="btn btn-danger" onclick="delete_Turnos_asignado("' . $idxid_temp . '");"><span class="glyphicon glyphicon-trash"></span></button></center></td>';
                    $data .= '</tr>';
                    $nombre_tem = $nombre;
                    $destino_tem = $centro;
                    $dias_semana = $dias;
                    $fechaI_tem = $FechaInicio;
                    $fechaF_tem = $FechaFin;
                    $id = $id_turno;
                    $idxid_temp = $idxid;
                }
            }
            $data .= '<td>' . $destino_tem . '</td>';
            $data .= '<td>' . $nombre_tem . '</td>';
            $data .= '<td>' . $fechaI_tem . '</td>';
            $data .= '<td>' . $fechaF_tem . '</td>';
            $data .= '<td>' . $dias_semana . '</td>';
            $data .= '<td><center><button class="btn btn-danger" onclick="delete_Turnos_asignado(\'' . $idxid_temp . '\');"><span class="glyphicon glyphicon-trash"></span></button></center></td>
                        </tr>';
            $data .= '</tbody></table></div></div>';
        }
        $json = $data;
    }

    if ($_GET['band'] == 'delete_Turnos_asignado') {
        $id = $list_record['id'];
        $iduser = ENCR::descript($list_record['iduser']);
        $nueva_cadena = str_replace(",", "','", $id);

        $sql = "DELETE BitacoraTurnos where idxid in ('$nueva_cadena') and idUsuario='$iduser'";
        $res = sqlsrv_query($conn, $sql);
        $json = '';
        if ($res)
            $json = 1;
    }

    if ($_GET['band'] == 'buscar_asignados') {
        $turno = $list_record['turno'];
        $texto = $list_record['texto'];
        // $usuario = ENCR::descript($list_record['usuario']);
        $usuario = '22954799-f18d-4b80-aae2-6868cf053354';
        $hoy = date('Y-m-d');
        $sql = "SELECT BitacoraTurnos.idusuario, UsuariosDetalle.NombreCompleto,turnos_empleados.descripcion,FechaInicio, FechaFin, dias, destino.Descripcion as centro  
            FROM bitacora_horarios
            inner join turnos_empleados on bitacora_horarios.id_turno = turnos_empleados.id_turno 
            inner join BitacoraTurnos on Bitacora_horarios.id_turno = BitacoraTurnos.idTurno
            inner join UsuariosDetalle on BitacoraTurnos.idUsuario = UsuariosDetalle.idUsuario
            left join destino on BitacoraTurnos.idCentroTrabajo = Destino.idDestino
            WHERE bitacora_horarios.id_Turno='$turno' and idactividad='47C297B3-411E-445E-8357-797687831DC2' 
                and (fechafin>='$hoy' or FechaFin='1900-01-01') order by NombreCompleto, FechaInicio";
        $res = sqlsrv_query($conn, $sql, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);
        $data = '';
        if ($row_permiso > 0) {
            $data .= '<div class="table-responsive">
                    <div id="div_tabla_detalle"><h2> Detalle Empleados  ' . $texto . '</h2> 
                        <table id="idTabledetalle_asignados" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data .= '   <th data-column="hora">Nombre Empleado</th>
                        <th class="text-center" data-column="centro">Centro Trabajo</th>
                        <th class="text-center" data-column="inicio">Fecha inicio</th>
                        <th class="text-center" data-column="salida">Fecha Fin</th>
                        <th class="text-center" data-column="dias">Dias laborales</th>
                    </tr></thead>
                    <tbody>
                <tr>';
            $id = '';
            $dias_semana = '';
            $nombre_tem = '';
            $centro_tem = '';
            $fechai_tem = '';
            $fechaf_tem = '';
            while ($aa = sqlsrv_fetch_array($res)) {
                $idusuario = $aa['idusuario'];
                $nombre = utf8_encode($aa['NombreCompleto']);
                $centro = utf8_encode($aa['centro']);
                $dias = utf8_encode($aa['dias']);
                $hora_inicio = date_format($aa['FechaInicio'], 'Y-m-d');
                $hora_fin = date_format($aa['FechaFin'], 'Y-m-d');
                if ($id == '') {
                    $nombre_tem = $nombre;
                    $centro_tem = $centro;
                    $fechai_tem = $hora_inicio;
                    $fechaf_tem = $hora_fin;
                    $dias_semana = $dias;
                    $id = $idusuario;
                } elseif ($id == $idusuario) {
                    if ($fechai_tem == $hora_inicio)
                        $dias_semana = $dias_semana . ', ' . $dias;
                    else {
                        $data .= '<td>' . $nombre_tem . '</td>';
                        $data .= '<td>' . $centro_tem . '</td>';
                        $data .= '<td>' . $fechai_tem . '</td>';
                        $data .= '<td>' . $fechaf_tem . '</td>
                                 <td>' . $dias_semana . '</td>
                            </tr>';
                        $nombre_tem = $nombre;
                        $centro_tem = $centro;
                        $fechai_tem = $hora_inicio;
                        $fechaf_tem = $hora_fin;
                        $dias_semana = $dias;
                        $id = $idusuario;
                    }
                } else {
                    $data .= '<td>' . $nombre_tem . '</td>';
                    $data .= '<td>' . $centro_tem . '</td>';
                    $data .= '<td>' . $fechai_tem . '</td>';
                    $data .= '<td>' . $fechaf_tem . '</td>
                             <td>' . $dias_semana . '</td>
                        </tr>';
                    $nombre_tem = $nombre;
                    $centro_tem = $centro;
                    $fechai_tem = $hora_inicio;
                    $fechaf_tem = $hora_fin;
                    $dias_semana = $dias;
                    $id = $idusuario;
                }
            }
            $data .= '<td>' . $nombre_tem . '</td>';
            $data .= '<td>' . $centro_tem . '</td>';
            $data .= '<td>' . $fechai_tem . '</td>';
            $data .= '<td>' . $fechaf_tem . '</td>
                             <td>' . $dias_semana . '</td>
                        </tr>';
            $data .= '</tbody></table></div></div>';
        }
        $json = $data;
    }

    if ($_GET['band'] == 'get_horasExtras') {
        $FechaInicial = $list_record['FechaInicial'];
        $FechaFinal = $list_record['FechaFinal'];
        $usuario = $list_record['usuario'] != '' ? ENCR::descript($list_record['usuario']) : '';
        $json = '';
        $where = "WHERE FechaInicial BETWEEN '$FechaInicial' AND  '$FechaFinal'";
        $clausulas = [];
        foreach ($list_record as $clave => $valor) {
            if (($clave != 'FechaInicial') && ($clave != 'FechaFinal')) {
                $clausulas[] = "vJornadaBitacora.$clave = '" . ENCR::descript($valor) . "'";
            }
        }

        if (!empty($usuario)) {
            $where .= " AND  vJornadaBitacora.idUsuario = '$usuario'";
        }
        $sql = "SELECT a.*,dbo.Get_sueldo_usuario(idUsuario, fecha) as Sueldo from 
                 (
                    SELECT vJornadaBitacora.idUsuario,Usuario,Tiquete_Registro,/* DATEADD(SECOND, DATEDIFF(SECOND, '1900-01-01', Horas) + DATEDIFF(SECOND, '1900-01-01', Horas), '1900-01-01') as horas,*/--vJornadaBitacora.Costo_hora,--Sueldo,
                    dbo.Get_horas_extras(vJornadaBitacora.idUsuario,vJornadaBitacora.Tiquete_Registro) as horas_extras,
                    UsuariosDetalle.Identificacion,
                    UsuariosDetalle.Cargo,
                    cast(vJornadaBitacora.FechaFinal as date) as fecha
                    FROM vJornadaBitacora
                    INNER JOIN UsuariosDetalle ON vJornadaBitacora.idUsuario=UsuariosDetalle.idUsuario 
                    $where 
                    GROUP BY vJornadaBitacora.idUsuario,Usuario,Tiquete_Registro,UsuariosDetalle.Identificacion,UsuariosDetalle.Cargo,
                   /* DATEADD(SECOND, DATEDIFF(SECOND, '1900-01-01', Horas) + DATEDIFF(SECOND, '1900-01-01', Horas), '1900-01-01'),*/cast(vJornadaBitacora.FechaFinal as date)
                 ) as a";
        $res = sqlsrv_query($conn, $sql);
        if ($res === false) {
            die(print_r(sqlsrv_errors(), true)); // Imprime detalles del error
        }
        $data = ' <div class="table-responsive">
                <div id="div_tablaHoras">
                <table id="idTableHorasExtras" class="table table-hover table-condensed table-bordered table-striped">
                <thead>
                <tr>';
        $data .= '<th class="text-center" data-column="Cedula">Cedula</th>';
        $data .= '<th class="text-center" data-column="Nombre">Nombre</th>';
        $data .= '<th class="text-center" data-column="Cargo">Cargo</th>';
        $data .= '<th class="text-center" data-column="Fechai">Hora</th>';
        $data .= '<th class="text-center" data-column="Tiquete">Tiquete</th>';
        $data .= '<th class="text-center" data-column="Tiquete">Fecha</th>';
        // $data .= '<th class="text-center" data-column="Nomina">Sueldo</th>';
        $data .= '<th class="text-center" data-column="Horas_Extras">Horas Extras</th>';
        $data .= '</tr></thead>';
        $data .= '<tbody><tr>';

        while ($aa = sqlsrv_fetch_array($res)) {
            $Identificacion = $aa['Identificacion'];
            $Usuario = utf8_encode($aa['Usuario']);
            $Cargo = utf8_encode($aa['Cargo']);
            // $Actividad = utf8_encode($aa['Actividad']);
            // $CentroTrabajo= utf8_encode($aa['CentroTrabajo']);
            $Horas = utf8_encode($aa['horas']);
            $Tiquete = utf8_encode($aa['Tiquete_Registro']);
            // $Costo_actividad= utf8_encode($aa['Costo_actividad']);
            $Sueldo = number_format($aa['Sueldo'], 2);
            $horas_extras = number_format($aa['horas_extras'], 2);
            $Fecha = date_format($aa['fecha'], 'Y-m-d');

            $registro = array(
                'Identificacion' => $Identificacion,
                'Usuario' => $Usuario,
                'Cargo' => $Cargo,
                'Horas' => $Horas,
                'Tiquete_Registro' => $Tiquete,
                'Fecha' => $Fecha,
                // 'Sueldo' => $Sueldo,
                'Horas_extras' => $horas_extras,
            );
            foreach ($registro as $clave => $valor) {
                $data .= '<td>' . (empty($valor) ? '' : htmlspecialchars($valor)) . '</td>';
            }

            $data .= '</tr>';
            $json .= $data;
            $data = '';
        }
        $json .= '</tbody></table></div></div>';
    } elseif ($_GET['band'] == 'get_usuario') {
        $idusuario = $list_record['usuario'];
        $sql = "SELECT NombreUsuarioLargo from Usuarios where idUsuario = '$idusuario' and habilitado=1 ";
        $res = sqlsrv_query($conn, $sql);
        while ($aa = sqlsrv_fetch_array($res)) {
            $nombre = utf8_encode($aa['NombreUsuarioLargo']);
        }
        $json = $nombre;
    }

    if ($_GET['band'] == 'save_Turnos') {
        $idxid = ($list_record['idxid'] == '') ? '00000000-0000-0000-0000-000000000000' : ENCR::descript($list_record['idxid']);
        $usuario = ENCR::descript($list_record['usuario']);
        $fechaInicio = $list_record['fechaInicio'];
        $h_Turno = $list_record['horaTurno'];

        if ($idxid == '00000000-0000-0000-0000-000000000000') {
            $sql = "SELECT count(*) as c from vBitacoraTurnos 
                WHERE idUsuario='$usuario' 
                AND FechaInicio = '$fechaInicio' ";
            $res = sqlsrv_query($conn, $sql);
            $fechas = array();
            while ($aa = sqlsrv_fetch_array($res)) {
                $c = $aa['c'];
            }

            if ($c == 0) {
                $sql = "EXECUTE SAVE_bitacora_turnos @idxid='$idxid', @usuario='$usuario', @horasTurno='$h_Turno', @fechaInicio='$fechaInicio';";
                $res = sqlsrv_query($conn, $sql);
                $estado = 'ok';
                $mensaje = 'Registro exitoso';
            } else {
                $estado = 'error';
                $mensaje = 'Ya existe un registro con esta fecha';
            }
        } else {
            $sql = "EXECUTE SAVE_bitacora_turnos @idxid='$idxid', @usuario='$usuario', @horasTurno='$h_Turno', @fechaInicio='$fechaInicio';";
            $res = sqlsrv_query($conn, $sql);
            $estado = 'ok';
            $mensaje = 'Actualización exítosa';
        }
        $registro = array('estado' => $estado, 'mensaje' => $mensaje);
        $json = json_encode($registro);
    }

    if ($_GET['band'] == 'editarReglaTiquete') {
        // $idusuarioRegistra= $list_record['idUsuario'];
        $json = '';
        $idxid = ENCR::descript($_GET['idxid']);
        $idRegla = ENCR::descript($_GET['idRegla']);
        $Valor = number_format($_GET['Valor'], 2);
        $FechaInicial = new DateTime($_GET['FechaInicial']);
        $FechaInicialFormatted = $FechaInicial->format('Y-m-d H:i:s');
        $FechaFinal = new DateTime($_GET['FechaFinal']);
        $FechaFinalFormatted = $FechaFinal->format('Y-m-d H:i:s');
        // $FechaFinal=date_format($_GET['FechaFinal'],'Y-m-d H:i:s');

        $Sql = "UPDATE Jornada_Bitacora_Detalle SET FechaInicial = '$FechaInicialFormatted', FechaFinal = '$FechaFinalFormatted', idRegla ='$idRegla' ,Valor='$Valor'
            where idxid ='$idxid'";
        $res = sqlsrv_query($conn, $Sql);
    }

    if ($_GET['band'] == 'obtener_Regla') {
        $sql = 'SELECT idRegla, Regla FROM jornadabitacorareglas';
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idRegla = ENCR::encript($aa['idRegla']);
            $Regla = utf8_encode($aa['Regla']);

            $registro = array('id' => $idRegla, 'name' => $Regla);
            array_push($data, $registro);
        }
        $json = json_encode($data);
    }

    if ($_GET['band'] == 'table_usuarioTurnos') {
        $json = '';
        $id = ($list_record['id'] != '1' && $list_record['id'] != '') ? ENCR::descript($list_record['id']) : $id = $list_record['id'];
        $condicional = '';
        if ($id != '' && $id != '1') {
            $condicional = "WHERE idUsuario = '$id'";
        } else if ($id == '') {
            $condicional = '';
        }
        $json = '';
        if ($condicional != '' || $id != '1') {
            $id = ENCR::descript($list_record['id']);
            $sql = "SELECT * FROM vBitacoraTurnos $condicional ORDER BY FechaInicio DESC, NombreUsuarioLargo DESC";
            $res = sqlsrv_query($conn, $sql);
            $json = '<br>
                    <table id="idTable_turnos" class="table table-hover table-condensed table-bordered table-striped">
                    <thead>
                    <tr>
                    <th style="vertical-align: middle;">Acciones</th>
                    <th>Usuario</th>
                    <th>HoraTurno</th>
                    <th>FechaInicio</th>
                    </tr></thead><tbody>';
            while ($aa = sqlsrv_fetch_array($res)) {
                $NombreUsuarioLargo = utf8_encode($aa['NombreUsuarioLargo']);
                $FechaInicio = date_format($aa['FechaInicio'], 'Y-m-d');
                $h_Turno = number_format($aa['HoraTurno'], 0, '', ',');
                // $Fecha = date_format($aa['Fecha'],'Y-m-d');
                $idxid = ENCR::encript($aa['idxid']);
                $idUsuario = ENCR::encript($aa['idUsuario']);
                $registro = array(
                    'Hora' => $h_Turno,
                    'FechaInicio' => $FechaInicio,
                    'idxid' => $idxid,
                    'NombreUsuarioLargo' => $NombreUsuarioLargo,
                    'idUsuarioTurno' => $idUsuario
                );
                $json .= '<tr onclick(\'' . ENCR::descript($idxid) . '\')>';
                $json .= '<td class="text-center"><button class="btn btn-warning" onclick=\'edit_button_usuario_Turnos(' . json_encode($registro) . ');\'><span class="glyphicon glyphicon-pencil"></span></button>';
                $json .= ' ';
                $json .= '<button class="btn btn-danger" onclick=\'delete_usuario_button_Turnos("' . $registro['idxid'] . '");\'><span class="glyphicon glyphicon-trash"></span></button></td>';
                $json .= '<td><p title="Usuario: ' . $NombreUsuarioLargo . ' - Fecha: ' . $FechaInicio . '">' . $NombreUsuarioLargo . '</p></td>';
                $json .= '<td>' . $h_Turno . '</td>';
                $json .= '<td>' . $FechaInicio . '</td>';
                $json .= '</tr>';
            }
            $json .= '</tbody></table></div></div>';
        } else {
            $json = 2;
        }
    }

    if ($_GET['band'] == 'delete_Turnos') {
        $idxid = ENCR::descript($list_record['idxid']);
        $sql = "EXEC DELETE_Bitacora_Turno @idxid='{$idxid}'";
        $res = sqlsrv_query($conn, $sql);
        if ($res)
            $json = 1;
        $json = '';
    }

    if ($_GET['band'] == 'get_ConsultaReglas') {
        $FechaInicial = $list_record['FechaInicial'];
        $FechaFinal = $list_record['FechaFinal'];
        $json = '';
        $where = "WHERE a.FechaInicial BETWEEN '$FechaInicial' AND  '$FechaFinal'";
        $clausulas = [];
        foreach ($list_record as $clave => $valor) {
            if (($clave != 'FechaInicial') && ($clave != 'FechaFinal')) {
                $clausulas[] = "vJornadaBitacora.$clave = '" . ENCR::descript($valor) . "'";
            }
        }

        if (!empty($clausulas)) {
            $where .= " AND " . implode(" AND ", $clausulas);
            // $where = 'AND ' . $where;
        }
        $sql = "SELECT a.id_Bitacora,Usuario, Identificacion, Cargo, Actividad, CentroTrabajo, sum(Horas) as Horas,
            Tiquete_Registro, Turno,Regla, b.Valor, b.FechaInicial  ,b.FechaFinal, b.idxid, b.idRegla
            FROM vJornadaBitacora a
            LEFT JOIN Jornada_Bitacora_Detalle b ON a.id_Bitacora = b.id_Bitacora
            LEFT JOIN jornadabitacorareglas r ON b.idRegla = r.idRegla
            $where
            GROUP BY a.id_Bitacora,Usuario, Identificacion, Cargo, Actividad, CentroTrabajo, Tiquete_Registro, Turno, Regla, b.Valor,b.FechaFinal,b.FechaInicial, b.idxid,b.idRegla
            ORDER BY Usuario, Tiquete_Registro, FechaInicial";
        $res = sqlsrv_query($conn, $sql);

        if ($res === false) {
            die(print_r(sqlsrv_errors(), true)); // Muestra detalles del error
        }

        $data = '<div class="table-responsive">
        <div id="div_tablaConsultaReglas">
        <table id="idTableConsultaReglas" class="table table-hover table-condensed table-bordered table-striped">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Cedula</th>
                <th>Cargo</th>
                <th>Localiazación</th>
                <th>Horas</th>
                <th>Tiquete</th>
                <th>Turno</th>
            </tr>
        </thead>';

        $data .= ' <tbody> ';
        $filaTemporal = '';
        $contadorFila = 1;
        $prevTiquete = null;
        $prevUsuario = null;
        $parte1 = '';
        $parte2 = '';
        $detalle = '';

        while ($aa = sqlsrv_fetch_array($res)) {
            $idRegla = ENCR::encript($aa['idRegla']);
            $Usuario = utf8_encode($aa['Usuario']);
            $Cedula = $aa['Identificacion'];
            $Cargo = utf8_encode($aa['Cargo']);
            $Actividad = utf8_encode($aa['Actividad']);
            $Localizacion = utf8_encode($aa['CentroTrabajo']);
            $Horas = utf8_encode($aa['Horas']);
            $Tiquete = utf8_encode($aa['Tiquete_Registro']);
            $HoraTurno = utf8_encode($aa['Turno']);
            if ($aa['FechaInicial'] != '') {
                $FechaInicio = date_format($aa['FechaInicial'], 'Y-m-d H:i:s');
            }

            if ($aa['FechaFinal'] != '') {
                // $FechaFin = date_format($aa['FechaFinal'],'Y-m-d ');
                $FechaFin = date_format($aa['FechaFinal'], 'Y-m-d H:i:s');
            }

            $Regla = utf8_encode($aa['Regla']);
            $Valor = number_format($aa['Valor'], 2);
            $idxid = ENCR::encript($aa['idxid']);


            if ($prevTiquete != $Tiquete) {

                $contadorFila++;
                if ($prevTiquete !== null) {
                    // Cerrar la fila actual si existe
                    $data .= $parte1 . $contadorFila . $parte2 . $detalle;
                    $detalle = '';
                }
                // $data .= $parte1.$contadorFila.$parte2.$detalle ;   
                // $detalle='';            

                $parte1 = '<tr><td rowspan="';
                $parte2 = '" style="text-align: center; vertical-align: middle;">' . $Usuario . '</td>';
                $parte2 .= '<td>' . $Cedula . '</td>';
                $parte2 .= '<td>' . $Cargo . '</td>';
                $parte2 .= '<td>' . $Localizacion . '</td>';
                $parte2 .= '<td>' . $Horas . '</td>';
                $parte2 .= '<td>' . $Tiquete . '</td>';
                $parte2 .= '<td>' . $HoraTurno . '</td></tr>';

                $prevTiquete = $Tiquete;
                $contadorFila = 0;
            }


            if ($aa['FechaInicial'] == '' && $aa['FechaFinal'] == '') {

                if ($Usuario != $prevUsuario) {
                    $detalle .= '<tr><th colspan="3" scope="rowgroup">Este tiquete no tiene Detalles</th> </tr>';
                    $prevUsuario = $Usuario;
                    $contadorFila++;
                    //   $detalle .='</tbody>';             
                }

                //  $detalle .='</tbody>';

            } else {

                if ($Usuario != $prevUsuario) {
                    $detalle .= '<tr>
                <th colspan="1" scope="rowgroup">FechaInicial</th>
                <th colspan="1" scope="rowgroup">FechaFinal</th>
                <th colspan="1" scope="rowgroup">Regla</th>
                <th colspan="1" scope="rowgroup">Actividad</th>
                <th colspan="1" scope="rowgroup" >Valor</th> 
                <th colspan="1" scope="rowgroup" >Editar</th>     
            </tr>';
                    $prevUsuario = $Usuario;
                    $contadorFila++;
                }

                // $data .= '<td rowspan="' . $contadorFila . '" style="text-align: center; vertical-align: middle;">' . $prevUsuario . '</td>';
                $detalle .= '<tr id="mantenerFoco' . $idxid . '">';
                $detalle .= '<td>' . $FechaInicio . '</td>';
                $detalle .= '<td>' . $FechaFin . '</td>';
                $detalle .= '<td>' . $Regla . '</td>';
                $detalle .= '<td>' . $Actividad . '</td>';
                $detalle .= '<td>' . $Valor . '</td>';

                $detalle .= '<td><button class="btn btn-warning" id="Reglasboton' . $idRegla . '"onclick="edit_button_Reglas(\'' . $idxid . '\',\'' . $idRegla . '\',\'' . $FechaInicio . '\',\'' . $FechaFin . '\',\'' . $Regla . '\',\'' . $Valor . '\');"><span class="glyphicon glyphicon-pencil"></span></button></td></tr>';
                $contadorFila++;
            }

            $json .= $data;
            $data = '';
        }

        $json .= '</tbody></table></div></div>';
    }

    if ($_GET['band'] == 'dias') {
        $diasEnEspanol = array(
            'Monday'    => 'lunes',
            'Tuesday'   => 'martes',
            'Wednesday' => 'miércoles',
            'Thursday'  => 'jueves',
            'Friday'    => 'viernes',
            'Saturday'  => 'sábado',
            'Sunday'    => 'domingo'
        );
        //setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');
        $fechaInicio = $list_record['fecha_ini'];
        $fechaFin = $list_record['fecha_fin'];

        $fechaI = new DateTime($fechaInicio);
        $fechaF = new DateTime($fechaFin);
        if ($fechaFin == '') {
            $fecha = new DateTime($fechaInicio);
            $intervalo = new DateInterval('P10D');
            $fecha->add($intervalo);
            $fechaF = $fecha;
        }

        // Convertir las fechas a objetos DateTime



        // Crear un array para almacenar los nombres de los días únicos
        $nombresDias = array();
        $data = [];
        // Iterar a través del rango de fechas
        while ($fechaI <= $fechaF) {
            // Obtener el nombre del día (por ejemplo: "Sunday", "Monday", etc.)
            //$nombreDia = strftime('%A', $fechaInicio->getTimestamp());
            $nombreDia = $fechaI->format('l');
            $nombreDiaEnEspanol = $diasEnEspanol[$nombreDia];
            // Agregar el nombre del día al array si no está ya presente
            if (!in_array($nombreDiaEnEspanol, $nombresDias)) {
                $nombresDias[] = $nombreDiaEnEspanol;
                $registro = array('id' => $nombreDiaEnEspanol, 'name' => $nombreDiaEnEspanol);
                array_push($data, $registro);
            }
            // Avanzar al siguiente día
            $fechaI->modify('+1 day');
        }
        $json = (json_encode($data));
        //$json = $fechaFin;
    }

    if ($_GET['band'] == 'get_CargosCentroTrabajo') {
        try {
            $idCentroTrabajo = isset($list_record['idCentroTrabajo']) ? resolverIdentificadorEntrada($list_record['idCentroTrabajo']) : null;
            if (!$idCentroTrabajo) {
                $json = respuestaExito(array(), 'Sin filtro de centro');
            } else {
                $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'VU');
                $sql = "SELECT DISTINCT VU.Cargo
                        FROM ProgramacionTurnos PT
                        INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = VU.idUsuario
                        WHERE PT.idCentroTrabajo = ? AND PT.activo = 1
                        AND VU.Cargo IS NOT NULL AND VU.Cargo <> ''
                        ORDER BY VU.Cargo";
                $res = sqlsrv_query($conn, $sql, array($idCentroTrabajo));
                $data = array();
                if ($res) {
                    while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                        $cargo = normalizarTextoSalida($row['Cargo']);
                        $data[] = array('id' => $cargo, 'name' => $cargo);
                    }
                }
                $json = respuestaExito($data, 'Cargos obtenidos');
            }
        } catch (Exception $e) {
            $json = respuestaError('Error: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'get_UsuariosPorCentro') {
        try {
            $idCentroTrabajo = isset($list_record['idCentroTrabajo']) ? resolverIdentificadorEntrada($list_record['idCentroTrabajo']) : null;
            $cargo = isset($list_record['cargo']) && !empty($list_record['cargo']) ? $list_record['cargo'] : null;
            if (!$idCentroTrabajo) {
                $json = respuestaExito(array(), 'Sin filtro de centro');
            } else {
                $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'VU');
                $sql = "SELECT DISTINCT VU.idUsuario, VU.NombreCompleto, VU.Identificacion, VU.Cargo
                        FROM ProgramacionTurnos PT
                        INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = VU.idUsuario
                        WHERE PT.idCentroTrabajo = ? AND PT.activo = 1";
                $params = array($idCentroTrabajo);
                if ($cargo) {
                    $sql .= " AND VU.Cargo = ?";
                    $params[] = $cargo;
                }
                $sql .= " ORDER BY VU.NombreCompleto";
                $res = sqlsrv_query($conn, $sql, $params);
                $data = array();
                if ($res) {
                    while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                        $data[] = array(
                            'id' => $row['idUsuario'],
                            'name' => normalizarTextoSalida($row['NombreCompleto']),
                            'cedula' => $row['Identificacion'],
                            'cargo' => normalizarTextoSalida($row['Cargo'])
                        );
                    }
                }
                $json = respuestaExito($data, 'Usuarios obtenidos');
            }
        } catch (Exception $e) {
            $json = respuestaError('Error: ' . $e->getMessage());
        }
    }

    if ($_GET['band'] == 'get_datos_reporte_asistencia') {
        try {
            $idCentroTrabajo = isset($list_record['idCentroTrabajo']) ? resolverIdentificadorEntrada($list_record['idCentroTrabajo']) : null;
            $fechaInicial = isset($list_record['fechaInicial']) ? $list_record['fechaInicial'] : null;
            $fechaFinal   = isset($list_record['fechaFinal'])   ? $list_record['fechaFinal']   : null;

            if (!$idCentroTrabajo || !$fechaInicial || !$fechaFinal) {
                $json = respuestaError('Centro de trabajo y rango de fechas son requeridos');
            } else {
                $fuenteUsuarios = obtenerSqlFuenteUsuarios($conn, 'VU');

                // 1. RAW DATA via SP
                $rawData = array();
                $resSP = sqlsrv_query($conn,
                    "EXEC [dbo].[GET_ReporteJornadasLaborales]
                        @FechaInicio = ?,
                        @FechaFin = ?,
                        @IdCentroTrabajo = ?,
                        @IdUsuario = ?,
                        @Cargo = ?",
                    array(
                        $fechaInicial,
                        $fechaFinal,
                        array($idCentroTrabajo, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_UNIQUEIDENTIFIER),
                        array(null,             SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_UNIQUEIDENTIFIER),
                        null
                    )
                );
                $spError = null;
                if ($resSP === false) {
                    $spErrors = sqlsrv_errors();
                    $spError = isset($spErrors[0]['message']) ? $spErrors[0]['message'] : 'Error desconocido al ejecutar SP';
                } else {
                    while ($row = sqlsrv_fetch_array($resSP, SQLSRV_FETCH_ASSOC)) {
                        $rawData[] = array(
                            'fecha'         => $row['Fecha']->format('Y-m-d'),
                            'diaSemana'     => normalizarTextoSalida($row['DiaSemana']),
                            'cargo'         => normalizarTextoSalida($row['Cargo']),
                            'nombre'        => normalizarTextoSalida($row['NombreTrabajador']),
                            'cedula'        => $row['Cedula'],
                            'jornada'       => normalizarTextoSalida($row['Jornada']),
                            'inicioJornada' => isset($row['1eraJornada.inicio']) && $row['1eraJornada.inicio'] ? $row['1eraJornada.inicio']->format('H:i:s') : '00:00:00',
                            'inicioReceso'  => isset($row['1eraJornada.inicioreceso']) && $row['1eraJornada.inicioreceso'] ? $row['1eraJornada.inicioreceso']->format('H:i:s') : null,
                            'total1ra'      => sanitizarTotal($row['1eraJornada.total']),
                            'finReceso'     => isset($row['2daJornada.finreceso']) && $row['2daJornada.finreceso'] ? $row['2daJornada.finreceso']->format('H:i:s') : null,
                            'finJornada'    => isset($row['2daJornada.final']) && $row['2daJornada.final'] ? $row['2daJornada.final']->format('H:i:s') : '23:59:59',
                            'total2da'      => sanitizarTotal($row['2daJornada.total']),
                            'tiempoTotal'   => sanitizarTotal($row['horas.TiempoTotalTrabajado']),
                            'sobretiempo'   => sanitizarTotal($row['horas.Sobretiempo'])
                        );
                    }
                }

                // 2. LISTADO empleados programados en el centro para el rango
                $listado = array();
                $resListado = sqlsrv_query($conn,
                    "SELECT DISTINCT
                        VU.Identificacion, VU.NombreCompleto, VU.Cargo,
                        T.descripcion AS Jornada,
                        D.Descripcion AS Area,
                        ISNULL(P.RazonSocial, '') AS Director,
                        ISNULL(UD.emailCorporativo, '') AS CorreoCorporativo
                    FROM ProgramacionTurnos PT
                    INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = VU.idUsuario
                    INNER JOIN ProgramacionTurnosDetalle PTD ON PT.idProgramacion = PTD.idProgramacion
                    INNER JOIN Turnos T ON PTD.idTurno = T.idTurno
                    INNER JOIN Destino D ON PT.idCentroTrabajo = D.idDestino
                    LEFT JOIN Proveedores P ON D.idProveedor = P.idProveedor
                    LEFT JOIN UsuariosDetalle UD ON VU.Identificacion = UD.Identificacion
                    WHERE PT.idCentroTrabajo = ?
                    AND PT.activo = 1
                    AND PTD.fechaInicio <= ? AND PTD.fechaFin >= ?",
                    array($idCentroTrabajo, $fechaFinal, $fechaInicial)
                );
                if ($resListado) {
                    while ($row = sqlsrv_fetch_array($resListado, SQLSRV_FETCH_ASSOC)) {
                        $listado[] = array(
                            'cedula'   => $row['Identificacion'],
                            'nombre'   => normalizarTextoSalida($row['NombreCompleto']),
                            'cargo'    => normalizarTextoSalida($row['Cargo']),
                            'jornada'  => normalizarTextoSalida($row['Jornada']),
                            'area'     => normalizarTextoSalida($row['Area']),
                            'director' => normalizarTextoSalida($row['Director']),
                            'correo'   => normalizarTextoSalida($row['CorreoCorporativo'])
                        );
                    }
                }

                // 3. PROGRAMACION por empleado y fecha (para calcular tardanza)
                $programacion = array();
                $resProg = sqlsrv_query($conn,
                    "SELECT
                        VU.Identificacion AS Cedula,
                        PTD.fechaInicio AS FechaInicio,
                        PTD.fechaFin AS FechaFin,
                        T.descripcion AS DescripcionTurno,
                        FORMAT(CAST(T.horaInicio AS DATETIME), 'HH:mm:ss') AS HoraInicio,
                        FORMAT(CAST(T.horaFin AS DATETIME), 'HH:mm:ss') AS HoraFin,
                        FORMAT(CAST(TD.horaInicio AS DATETIME), 'HH:mm:ss') AS HoraInicioDescanso,
                        FORMAT(CAST(TD.horaFin AS DATETIME), 'HH:mm:ss') AS HoraFinDescanso,
                        CASE WHEN T.horaFin < T.horaInicio THEN 1 ELSE 0 END AS Nocturno
                    FROM ProgramacionTurnosDetalle PTD
                    INNER JOIN ProgramacionTurnos PT ON PTD.idProgramacion = PT.idProgramacion
                    INNER JOIN Turnos T ON PTD.idTurno = T.idTurno
                    INNER JOIN {$fuenteUsuarios} ON PT.idUsuario = VU.idUsuario
                    LEFT JOIN TurnosDescansos TD ON T.idTurno = TD.idTurno AND TD.activo = 1
                    WHERE PT.idCentroTrabajo = ?
                    AND PTD.fechaInicio <= ? AND PTD.fechaFin >= ?
                    AND PT.activo = 1",
                    array($idCentroTrabajo, $fechaFinal, $fechaInicial)
                );
                if ($resProg) {
                    while ($row = sqlsrv_fetch_array($resProg, SQLSRV_FETCH_ASSOC)) {
                        $programacion[] = array(
                            'cedula'            => $row['Cedula'],
                            'fechaInicio'       => $row['FechaInicio']->format('Y-m-d'),
                            'fechaFin'          => $row['FechaFin']->format('Y-m-d'),
                            'jornada'           => normalizarTextoSalida($row['DescripcionTurno']),
                            'horaInicio'        => $row['HoraInicio'],
                            'horaFin'           => $row['HoraFin'],
                            'horaInicioDescanso'=> $row['HoraInicioDescanso'],
                            'horaFinDescanso'   => $row['HoraFinDescanso'],
                            'nocturno'          => $row['Nocturno']
                        );
                    }
                }

                // 4. Info del centro de trabajo
                $centroInfo = array('nombre' => '', 'empresa' => '');
                $resCentro = sqlsrv_query($conn,
                    "SELECT D.Descripcion, ISNULL(P.RazonSocial, '') AS Empresa
                     FROM Destino D LEFT JOIN Proveedores P ON D.idProveedor = P.idProveedor
                     WHERE D.idDestino = ?",
                    array($idCentroTrabajo)
                );
                if ($resCentro && $rowC = sqlsrv_fetch_array($resCentro, SQLSRV_FETCH_ASSOC)) {
                    $centroInfo = array(
                        'nombre'  => normalizarTextoSalida($rowC['Descripcion']),
                        'empresa' => normalizarTextoSalida($rowC['Empresa'])
                    );
                }

                $json = respuestaExito(array(
                    'rawData'     => $rawData,
                    'listado'     => $listado,
                    'programacion'=> $programacion,
                    'centroInfo'  => $centroInfo,
                    'fechaInicial'=> $fechaInicial,
                    'fechaFinal'  => $fechaFinal,
                    'spError'     => $spError
                ), 'Datos de reporte obtenidos correctamente');
            }
        } catch (Exception $e) {
            $json = respuestaError('Error al obtener datos: ' . $e->getMessage());
        }
    }

    if ($respuestaJson) {
        $salidaBuffer = '';
        while (ob_get_level() > 0) {
            $salidaBuffer .= ob_get_clean();
        }

        echo $json !== '' ? $json : json_encode(array(
            'success' => false,
            'message' => 'No se pudo generar una respuesta válida'
        ));
        $jsonResponseSent = true;
    } else {
        echo $json;
    }
}


// <th class="text-center" style="vertical-align: middle;">Acciones</th>