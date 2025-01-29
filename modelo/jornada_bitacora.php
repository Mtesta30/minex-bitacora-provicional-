<?php
require_once '../../conectar.php';
include("../../clase_encrip.php");
// include("../../flujos/funciones.php");

$post_data = file_get_contents('php://input');
$list_record = json_decode($post_data, true);
$params = array();
$options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
if (isset($_GET['band'])) {

    if ($_GET['band'] == 'get_Usuarios') {
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

    if ($_GET['band'] == 'get_CentrosDeTrabajo') {
        $sql = "SELECT idDestino, Descripcion FROM Destino order by Descripcion";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $idDestino = ENCR::encript($aa['idDestino']);
            $Descripcion = utf8_encode($aa['Descripcion']);
            $registro = array('id' => $idDestino, 'name' => $Descripcion);
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
                $Horas =  substr(date_format($aa['Horas'],'Y-m-d H:i:s'),11,5); 
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
        $idcentroTrabajo = ENCR::descript($list_record['idCentroTrabajo']);
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
*/      $sql = "EXECUTE SAVE_JornadaBitacora @idBitacora='$idBitacora', @idUsuario='$idUsuario', @idCentroTrabajo='$idcentroTrabajo', @idActividad='$idActividad', 
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

    if ($_GET['band'] == 'cargar_turnos') {
        $sql="SELECT * FROM turnos_empleados";
        $res = sqlsrv_query($conn, $sql);
        $data = [];
        while ($aa = sqlsrv_fetch_array($res)) {
            $consecutivo = $aa['id_turno'];
            $Nombre = $aa['descripcion'];
            $registro = array('id' => $consecutivo, 'name' => $Nombre);
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
             $clave.'  -';
            if (($clave != 'FechaInicial') && ($clave != 'FechaFinal') && ($clave != 'idMiUsuario')) {
                $clausulas[] = "vJornadaBitacora.$clave = '".ENCR::descript($valor)."'";
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
             $Horas = substr(date_format($aa['Horas'],'Y-m-d H:i:s'),11,5);
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
        $sql="SELECT dispositivo from biometrico.dbo.bitacora group by dispositivo";
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
        $list_CentroConsulta =$list_record['list_CentroConsulta'];
        $list_UsuarioConsulta = ENCR::descript($list_record['list_UsuarioConsulta']);
        if ($list_CentroConsulta<>'')
             $centro= "'".$list_CentroConsulta."'"; 
        else
            $centro='null';

        if ($list_UsuarioConsulta<>'')
            $user= "'".$list_UsuarioConsulta."'";  
        else
            $user= 'null';
        $json = '';
        $sql = "SELECT * from traz.dbo.lista_bitacora ('$FechaInicial','$FechaFinal','$list_empresa',".$centro.",".$user.")";

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
            $hora_entrada = date_format ($aa['hora_entrada'],'Y-m-d H:i:s');
            $hora_salida = date_format($aa['hora_salida'],'Y-m-d H:i:s');
            $horas = substr(date_format($aa['horas'],'Y-m-d H:i:s'),11,5);
            $dispositivo = utf8_encode($aa['dispositivo']);
            $tarde = utf8_encode($aa['tarde']);
            $data.='<tr onclick=\'mostrar_detalle("'.$año.'","'.$consecutivo.'","'.$list_empresa.'","'.$id .'","'.$Nombre.'");\'>'; 
            $tiquete=$año.'-'.$consecutivo;
            $registro = array(   
                'Nombre' => $Nombre,
                'dispositivo'=> $dispositivo, 
                'tiquete'=> $tiquete,                
                'hora_entrada' => $hora_entrada,
                'Actividad' => $hora_salida,                
                'Horas' => $horas,                
            );
            foreach ($registro as $clave => $valor) {
                if($tarde==0)
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
        $data='';
        $sql1 ="SELECT * FROM biometrico.dbo.bitacora where consecutivo = $tiquete and año=$año order by date_time ";// AND MARCADO =0 
        $res = sqlsrv_query($conn, utf8_decode($sql1));       
        $data=' <div class="table-responsive">
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
            $access_date = date_format($aa['access_date'],'Y-m-d');
            $access_time = date_format($aa['access_time'],'H:i:s') ;
            $DateTime = date_format($aa['date_time'],'Y-m-d H:i:s') ;
            $Estado_real = strtoupper($aa['Estado_real']);
            $dispositivo = $aa['dispositivo'];
            $data.'<tr>';
            $registro = array('dispositivo' => $dispositivo, 
                'access_date' => $access_date, 
                'access_time' => $access_time, 
                'Estado_real' => $Estado_real,
            );
          //  array_push($data, $registro);
            foreach ($registro as $clave => $valor) {
                    $data .= '<td>' . (empty($valor) ? '' : htmlspecialchars($valor)) . '</td>';
            }
            if ($Estado_real=='PENDIENTE'){
                $data .= '<td><center><button class="btn btn-success" onclick=\'Modificar_Turnos_asignado("'.$DateTime.'");\'><span class="glyphicon glyphicon-pencil"></span></button></center></td>';
            }
            $data .= '</tr>';
            $json .= $data;
            $data = '';
        }
        $sql_usuario ="SELECT idUsuario from UsuariosDetalle where Identificacion='$cedula'";
        $res_user = sqlsrv_query($conn, $sql_usuario); 
        while ($au = sqlsrv_fetch_array($res_user)) {
            $id = $au['idUsuario'];
        }
        $sql_turno ="SELECT  top(1)Descripcion FROM turnos_empleados inner join BitacoraTurnos on turnos_empleados.id_turno= BitacoraTurnos.idTurno and '$access_date' between FechaInicio and FechaFin 
            and idUsuario='$id'";
        $res_user = sqlsrv_query($conn, $sql_turno,$params,$options); 
        $filas= sqlsrv_num_rows($res_user);
        if ($filas>0){
            while ($aa = sqlsrv_fetch_array($res_user)) {
                $descripcion = $aa['Descripcion'];
            }
        }else
            $descripcion='No tiene Turno Asignado';

        $json .= '</tbody></table></div></div>'; 
        $json=$json.'||'.$descripcion;
    }

    if ($_GET['band'] == 'grabar_correccion') {
        $fecha_detalle = $list_record['fecha_detalle'];
        $hora_detalle = $list_record['hora_detalle'];
        $salida = $list_record['salida'];
        $turno = $list_record['turno'];
        $user_tiquete = $list_record['user_tiquete'];
        $usuario = $list_record['usuario'];
        $datetime=$fecha_detalle.' '.$hora_detalle;
        $user = "SELECT NombreCompleto from UsuariosDetalle  WHERE idUsuario='$usuario'";
        $res = sqlsrv_query($conn, utf8_decode($user));
        while ($aa = sqlsrv_fetch_array($res)) {
            $nombre=utf8_encode($aa['NombreCompleto']);
        }
       echo $sql="UPDATE biometrico.dbo.bitacora set access_date='$fecha_detalle', date_time='$datetime', access_time='$hora_detalle', Estado_real='$salida'  where date_time='$turno' AND Estado_real='Pendiente'";
        $res = sqlsrv_query($conn, utf8_decode($sql));
        $sql="INSERT INTO logs (text) values('Se cambio la fecha=$fecha_detalle, Hora=$hora_detalle,  y Estado real= Salida, del tiquete=$user_tiquete  modificado por $nombre')";
        $res = sqlsrv_query($conn, utf8_decode($sql));
    }

    if ($_GET['band'] == 'obtener_tiquete') {
        $idUsuario = ENCR::descript($_GET['idUsuario']);
        $sql1 ="SELECT Identificacion FROM TRAZ.DBO.UsuariosDetalle  WHERE  idUsuario ='$idUsuario'";
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
        $sql="SELECT traz.dbo.buscar_horas ('$idTiquete', '$usuario') AS tiempo";
        $res = sqlsrv_query($conn, utf8_decode($sql));
        while ($aa = sqlsrv_fetch_array($res)) {
            $tiempo = substr(date_format($aa['tiempo'],'Y-m-d H:i:s'),11,5); 
        }
        $json = $tiempo;
    }

    if ($_GET['band'] == 'buscar_horas_pendientes') {
        $idTiquete = $list_record['idTiquete'];
        $usuario = ENCR::descript($list_record['usuario']);
        $sql="SELECT traz.dbo.Buscar_horas_pendientes ('$idTiquete', '$usuario') AS tiempo";
        $res = sqlsrv_query($conn, utf8_decode($sql));
        while ($aa = sqlsrv_fetch_array($res)) {
            $tiempo = substr(date_format($aa['tiempo'],'Y-m-d H:i:s'),11,5); 
        }
        $json = $tiempo;
    }

    if ($_GET['band'] == 'buscar_detalle') {
        $tiquete = $list_record['tiquete'];
        $año = $list_record['año'];
        $usuario = ENCR::descript($list_record['usuario']);
        $sql1 ="SELECT Identificacion FROM TRAZ.DBO.UsuariosDetalle  WHERE  idUsuario ='$usuario'";
        $res = sqlsrv_query($conn, $sql1);

        while ($aa = sqlsrv_fetch_array($res)) {
            $Identificacion = $aa['Identificacion'];           
        }
        $sql="SELECT * FROM biometrico.dbo.bitacora WHERE id='$Identificacion' and consecutivo=$tiquete and año=$año order by date_time ";
        $res = sqlsrv_query($conn, utf8_decode($sql),$params,$options);
        $row_permiso = sqlsrv_num_rows($res);
        if($row_permiso>0){
            $data.='<div class="table-responsive">
                    <div id="div_tabla_detalles">
                        <table id="idTabledetalle" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data.= '<th class="text-center" data-column="entrada">Hora Entrada</th>
                    <th class="text-center" data-column="salida">Hora Salida</th>
                    <th class="text-center" data-column="hora">Total Horas</th>
                    <th class="text-center" data-column="dis">Dispositivo</th>
                    </tr></thead>
                    <tbody>
                <tr>';
           
            $hora_parcial ='';
            $bandera=0;
            while ($aa = sqlsrv_fetch_array($res)) {
                $Estado_real = $aa['Estado_real'];
                $Dispositivo = $aa['dispositivo']; 
                $hora=date_format($aa['date_time'],"Y-m-d H:i:s");
                if ($Estado_real=='Entrada'){
                    $hora_parcial=$hora;
                    $data.= '<td>'.$hora_parcial.'</td>';
                    $bandera++;
                }else if ($Estado_real=='Salida'){                
                    $sqll="SELECT traz.dbo.calcular_hora ('$hora_parcial', '$hora') AS tiempo";
                    $ress = sqlsrv_query($conn, utf8_decode($sqll));
                    while ($ah = sqlsrv_fetch_array($ress)) {
                        $tiempo = substr(date_format($ah['tiempo'],"Y-m-d H:i:s"),11,5);            
                    }
                    $data.= '<td>'.$hora.'</td>';
                    $data.= '<td>'.$tiempo.'</td>';
                    $data.= '<td>'.$Dispositivo.'</td>';              
                    if ($bandera<>$row_permiso)
                        $data.= '<tr> ';
                    $bandera++;
                }else{
                    $data.= '<td>1900-01-01</td>';
                    $data.= '<td>0</td>';
                    $data.= '<td>'.$Dispositivo.'</td>';
                    $data.= '<td>No marco Salida</td> </tr>';
                    if ($bandera<>$row_permiso)
                        $data.= '<tr> ';
                    $bandera++;
                }            
            }
            $data.= '</tbody></table></div></div>';
        }
        $json = $data; 
    } 

    if ($_GET['band'] == 'buscar_detalle_tiquete') {
        $idTiquete = $list_record['idTiquete'];
        $usuario = ENCR::descript($list_record['usuario']);

        $sql1 ="SELECT d.Descripcion centro_trabajo, a.Descripcion actividad, u.Descripcion unidadNeg,
            fechafinal-fechainicial as tiempo
        FROM Jornada_Bitacora J 
        inner join Destino D on j.idCentroTrabajo = D.idDestino
        inner join Actividades A on j.idActividad=a.idActividad 
        inner join Jornada_Bitacora_UnidadNegocio U on j.idUnidadNegocio = U.idUnidadNegocio
        WHERE J.idUsuario='$usuario' and Tiquete_Registro='$idTiquete' order by Tiquete_Registro, j.FechaInicial";
        $res = sqlsrv_query($conn, $sql1,$params,$options);
        $row_permiso = sqlsrv_num_rows($res);
        if($row_permiso>0){
            $data='<div class="table-responsive">
                    <div id="div_tabla_detalles">
                        <table id="idTabledetalle" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data.= '<th class="text-center" data-column="centro">Centro Trabajo</th>
                    <th class="text-center" data-column="actividad">Actividad</th>
                    <th class="text-center" data-column="negocio">Negocio</th>
                    <th class="text-center" data-column="tiempo">Tiempo</th>
                    </tr></thead>
                    <tbody>';
           
            while ($aa = sqlsrv_fetch_array($res)) {
                $centro_trabajo = utf8_encode($aa['centro_trabajo']);
                $actividad = utf8_encode($aa['actividad']); 
                $unidadNeg = utf8_encode($aa['unidadNeg']); 
                $tiempo=substr(date_format($aa['tiempo'],"Y-m-d H:i:s"),11,5);
                $data.= '<tr><td>'.$centro_trabajo.'</td>';
                $data.= '<td>'.$actividad.'</td>';
                $data.= '<td>'.$unidadNeg.'</td>';              
                $data.= '<td>'.$tiempo.'</td></tr>';               
            }
            $data.= '</tbody></table></div></div>';
        }else
            $data='';
        $json = $data; 
    } 

    if ($_GET['band'] == 'buscar_detalle_t') {
        $turno = $list_record['turno'];
        $op = $list_record['op'];
        $count=0;
        if ($op==1){
            $sql="SELECT * from bitacora_horarios left join BitacoraTurnos on bitacora_horarios.id_turno = BitacoraTurnos.idturno 
                inner join turnos_empleados on bitacora_horarios.id_turno = turnos_empleados.id_turno where BitacoraTurnos.idxid is not null and idTurno='$turno'";
            $res = sqlsrv_query($conn, $sql,$params, $options);
            $count = sqlsrv_num_rows($res);
            if ($count>0)
                $count=1;
        }

        $usuario = ENCR::descript($list_record['usuario']);
        $sql1 ="SELECT * FROM bitacora_horarios inner join Actividades on Bitacora_horarios.idactividad = Actividades.idActividad  WHERE id_turno ='$turno' ORDER BY FechaRegistro";
        $res = sqlsrv_query($conn, $sql1,$params, $options);
        $row_permiso = sqlsrv_num_rows($res);
        $data='';
        if($row_permiso>0){
            $data.='<div class="table-responsive">
                    <div id="div_tabla_detalle">
                        <table id="idTabledetalle_detalle" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data.= '<th class="text-center" data-column="hora">Turno</th>
                        <th class="text-center" data-column="entrada">Hora Entrada</th>
                        <th class="text-center" data-column="salida">Hora Salida</th>
                    </tr></thead>
                    <tbody>
                <tr>';
            while ($aa = sqlsrv_fetch_array($res)) {
                $descripcion = utf8_encode($aa['Descripcion']);
                $hora_inicio = date_format($aa['hora_inicio'],'H:i:s');
                $hora_fin = date_format($aa['hora_fin'],'H:i:s'); 
                $data.= '<td>'.$descripcion.'</td>';
                $data.= '<td>'.$hora_inicio.'</td>';
                $data.= '<td>'.$hora_fin.'</td>
                    </tr>';                            
            }
            $data.= '</tbody></table></div></div>';
        }
        $json = $data.'||'.$count;
    } 

    if ($_GET['band'] == 'get_turnos_user_activo') {
      //  $turno = $list_record['texto_Usuario'];
        $usuario = ENCR::descript($list_record['usuario']);
        $sql ="SELECT BitacoraTurnos.idxid,BitacoraTurnos.idusuario, Bitacora_horarios.id_turno,turnos_empleados.descripcion,FechaInicio, FechaFin, dias, destino.Descripcion as centro from bitacora_horarios 
            inner join turnos_empleados on bitacora_horarios.id_turno = turnos_empleados.id_turno 
            inner join UsuariosDetalle on bitacora_horarios.idUsuario = UsuariosDetalle.idUsuario
            inner join BitacoraTurnos on Bitacora_horarios.id_turno = BitacoraTurnos.idTurno
            left join destino on BitacoraTurnos.idCentroTrabajo = Destino.idDestino
            WHERE BitacoraTurnos.idusuario='$usuario' and idactividad='47C297B3-411E-445E-8357-797687831DC2'
                and (FechaFin>=cast(GETDATE() as date) or FechaFin='1900-01-01')";
        $res = sqlsrv_query($conn, $sql, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);
        $data='';
        if($row_permiso>0){
            $data.='<div class="table-responsive">
                    <div id="div_tabla_detalle">
                        <table id="idTabledetalle" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data.= '<th data-column="centro">Centro Trabajo</th>
                    <th data-column="hora">Turno</th>
                    <th data-column="fi">Fecha Inicio</th>
                    <th data-column="ff">Fecha Fin</th>
                    <th class="text-center" data-column="salida">Dias Asignados</th>
                    <th class="text-center" data-column="salida">opcion</th>
                </tr></thead>
                <tbody>';
            $id='';
            $idxid_temp='';
            $dias_semana='';
            $nombre_tem='';
            $destino_tem='';
            $fechaI_tem='';
            $fechaF_tem='';
            while ($aa = sqlsrv_fetch_array($res)) {
                $id_turno = $aa['id_turno'];
                $idxid = $aa['idxid'];
                $nombre = utf8_encode($aa['descripcion']);
                $centro = utf8_encode($aa['centro']);
                $FechaInicio = date_format($aa['FechaInicio'],'Y-m-d');
                $FechaFin = date_format($aa['FechaFin'],'Y-m-d');                
                $dias = utf8_encode($aa['dias']);
                if($id==''){                    
                    $nombre_tem=$nombre;
                    $destino_tem=$centro;
                    $fechaI_tem=$FechaInicio;
                    $fechaF_tem=$FechaFin;
                    $dias_semana=$dias;
                    $id=$id_turno;
                    $idxid_temp=$idxid;
                }elseif(($id==$id_turno) && ($fechaI_tem ==$FechaInicio) && ($fechaF_tem==$FechaFin)){
                     $dias_semana=$dias_semana.', '.$dias;
                     $idxid_temp.=','.$idxid;
                }else{
                    $data.= ' <tr><td>'.$destino_tem.'</td>';
                    $data.= '<td>'.$nombre_tem.'</td>';
                    $data.= '<td>'.$fechaI_tem.'</td>';
                    $data.= '<td>'.$fechaF_tem.'</td>';
                    $data.= '<td>'.$dias_semana.'</td>'; 
                    $data.= '<td><center><button class="btn btn-danger" onclick="delete_Turnos_asignado("'.$idxid_temp.'");"><span class="glyphicon glyphicon-trash"></span></button></center></td>';
                    $data.= '</tr>';
                    $nombre_tem=$nombre;
                    $destino_tem=$centro;
                    $dias_semana=$dias;
                    $fechaI_tem=$FechaInicio;
                    $fechaF_tem=$FechaFin;
                    $id=$id_turno;
                    $idxid_temp=$idxid;
                }                                           
            }
            $data.= '<td>'.$destino_tem.'</td>';
            $data.= '<td>'.$nombre_tem.'</td>';
            $data.= '<td>'.$fechaI_tem.'</td>';
            $data.= '<td>'.$fechaF_tem.'</td>';
            $data.= '<td>'.$dias_semana.'</td>';
            $data.= '<td><center><button class="btn btn-danger" onclick="delete_Turnos_asignado(\''.$idxid_temp.'\');"><span class="glyphicon glyphicon-trash"></span></button></center></td>
                        </tr>'; 
            $data.= '</tbody></table></div></div>';
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
        $usuario = ENCR::descript($list_record['usuario']);
        $hoy = date('Y-m-d');
        $sql ="SELECT BitacoraTurnos.idusuario, UsuariosDetalle.NombreCompleto,turnos_empleados.descripcion,FechaInicio, FechaFin, dias, destino.Descripcion as centro  
            FROM bitacora_horarios
            inner join turnos_empleados on bitacora_horarios.id_turno = turnos_empleados.id_turno 
            inner join BitacoraTurnos on Bitacora_horarios.id_turno = BitacoraTurnos.idTurno
            inner join UsuariosDetalle on BitacoraTurnos.idUsuario = UsuariosDetalle.idUsuario
            left join destino on BitacoraTurnos.idCentroTrabajo = Destino.idDestino
            WHERE bitacora_horarios.id_Turno='$turno' and idactividad='47C297B3-411E-445E-8357-797687831DC2' 
                and (fechafin>='$hoy' or FechaFin='1900-01-01') order by NombreCompleto, FechaInicio";
        $res = sqlsrv_query($conn, $sql, $params, $options);
        $row_permiso = sqlsrv_num_rows($res);
        $data='';
        if($row_permiso>0){
            $data.='<div class="table-responsive">
                    <div id="div_tabla_detalle"><h2> Detalle Empleados  '.$texto.'</h2> 
                        <table id="idTabledetalle_asignados" class="table table-hover table-condensed table-bordered table-striped" width="80%">
                        <thead>
                        <tr>';
            $data.= '   <th data-column="hora">Nombre Empleado</th>
                        <th class="text-center" data-column="centro">Centro Trabajo</th>
                        <th class="text-center" data-column="inicio">Fecha inicio</th>
                        <th class="text-center" data-column="salida">Fecha Fin</th>
                        <th class="text-center" data-column="dias">Dias laborales</th>
                    </tr></thead>
                    <tbody>
                <tr>';
            $id='';
            $dias_semana='';
            $nombre_tem='';
            $centro_tem='';
            $fechai_tem='';
            $fechaf_tem='';
            while ($aa = sqlsrv_fetch_array($res)) {
                $idusuario = $aa['idusuario'];
                $nombre = utf8_encode($aa['NombreCompleto']);
                $centro = utf8_encode($aa['centro']);
                $dias = utf8_encode($aa['dias']);
                $hora_inicio = date_format($aa['FechaInicio'],'Y-m-d');
                $hora_fin = date_format($aa['FechaFin'],'Y-m-d'); 
                if($id==''){                    
                    $nombre_tem=$nombre;
                    $centro_tem=$centro;
                    $fechai_tem=$hora_inicio;
                    $fechaf_tem=$hora_fin;
                    $dias_semana=$dias;
                    $id=$idusuario;
                }elseif($id==$idusuario){
                    if($fechai_tem==$hora_inicio)
                        $dias_semana=$dias_semana.', '.$dias;
                    else{
                        $data.= '<td>'.$nombre_tem.'</td>';
                        $data.= '<td>'.$centro_tem.'</td>';
                        $data.= '<td>'.$fechai_tem.'</td>';
                        $data.= '<td>'.$fechaf_tem.'</td>
                                 <td>'.$dias_semana.'</td>
                            </tr>'; 
                        $nombre_tem=$nombre;
                        $centro_tem=$centro;
                        $fechai_tem=$hora_inicio;
                        $fechaf_tem=$hora_fin;
                        $dias_semana=$dias;
                        $id=$idusuario;
                    }                           
                }else{
                    $data.= '<td>'.$nombre_tem.'</td>';
                    $data.= '<td>'.$centro_tem.'</td>';
                    $data.= '<td>'.$fechai_tem.'</td>';
                    $data.= '<td>'.$fechaf_tem.'</td>
                             <td>'.$dias_semana.'</td>
                        </tr>'; 
                    $nombre_tem=$nombre;
                    $centro_tem=$centro;
                    $fechai_tem=$hora_inicio;
                    $fechaf_tem=$hora_fin;
                    $dias_semana=$dias;
                    $id=$idusuario;
                }                                                 
            }
            $data.= '<td>'.$nombre_tem.'</td>';
                    $data.= '<td>'.$centro_tem.'</td>';
                    $data.= '<td>'.$fechai_tem.'</td>';
                    $data.= '<td>'.$fechaf_tem.'</td>
                             <td>'.$dias_semana.'</td>
                        </tr>'; 
            $data.= '</tbody></table></div></div>';
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
            $Fecha = date_format($aa['fecha'],'Y-m-d');

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

    if ($_GET['band'] == 'get_crear_turno') {
        $FechaInicial_turno =$list_record['FechaInicial_turno'];
        $FechaFinal_turno = $list_record['FechaFinal_turno'];
        $lista_turnos = $list_record['lista_turnos'];
        $lista_actividades = $list_record['lista_actividades'];
        $idusuario = $list_record['idusuario'];
        /////////  falta el usuario conectado///////////////////////////////////////////
        $bandera=0;
        if ($lista_turnos == '1') {
            $sqlid ='SELECT Newid() as id';
            $res2 = sqlsrv_query($conn, $sqlid);
            while ($aa = sqlsrv_fetch_array($res2)) {
                $lista_turnos = $aa['id'];
            }
        }else{
            $sql ="SELECT * FROM bitacora_horarios where id_turno ='$lista_turnos' order by fecharegistro";
            $res = sqlsrv_query($conn, $sql);
            while ($aa = sqlsrv_fetch_array($res)) {
                $hora_inicio = date_format($aa['hora_inicio'],'H:i:s');
                $hora_fin = date_format($aa['hora_fin'],'H:i:s');
                $idActividad = $aa['idactividad'];
                if($idActividad=='47C297B3-411E-445E-8357-797687831DC2'){
                    if($FechaInicial_turno<$hora_inicio or $FechaFinal_turno>$hora_fin)
                        $bandera=1;
                }else{
                    if(($FechaInicial_turno>$hora_inicio and $FechaInicial_turno<$hora_fin) or ($FechaFinal_turno>$hora_inicio and $FechaFinal_turno<$hora_fin) or 
                        ($FechaInicial_turno<$hora_inicio and $FechaFinal_turno>$hora_fin)){
                        $bandera=1;
                    }
                }
            }
        }

        if($bandera==0){
            $sql = "EXECUTE SAVE_bitacora_crear_turnos @idturno='$lista_turnos', @fecha_ini='$FechaInicial_turno', @fecha_fin='$FechaFinal_turno', @idactividad='$lista_actividades', @idusuario='$idusuario'";
            $res = sqlsrv_query($conn, $sql);
            if($res){
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
            $json = (json_encode($data));
        }else
            $json= $bandera;        
    }

    if ($_GET['band'] == 'get_asignar_turno') {
        $FechaInicial_turno =$list_record['FechaInicial_turno'];
        $FechaFinal_turno = $list_record['FechaFinal_turno'];
        $lista_turnos = $list_record['lista_turnos'];
        $dias_laborales = $list_record['dias_laborales'];
        $usuario = ENCR::descript($list_record['usuario']);
        $id_usuario_login = $list_record['id_usuario'];
        $idcentroTrabajo = ENCR::descript($list_record['idcentroTrabajo']);
        $bandera=0;
        $array_dias = array();
        $cont= count($list_record["dias_laborales"]);
        for ($i=0; $i <$cont ; $i++) {
            $array_dias[$i] =$list_record["dias_laborales"][$i]; 
        }
        $dias_laborales= implode(",", $array_dias);
        if ($FechaFinal_turno == '') {
            $FechaFinal_turno='1900-01-01';    
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
            $json= $sql;      
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
        if ($fechaFin==''){
            $fecha = new DateTime($fechaInicio);
            $intervalo = new DateInterval('P10D');
            $fecha->add($intervalo);
            $fechaF=$fecha;
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

    echo $json;
}


// <th class="text-center" style="vertical-align: middle;">Acciones</th>