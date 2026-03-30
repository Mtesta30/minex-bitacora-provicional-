<?php
session_start();
require_once dirname(__DIR__) . '/conectartraz.php';
// require_once '../../conectar.php';

// Usuario admin
 $_SESSION['idUsuario'] = 'c9fa5447-4a96-4309-b6c3-4ffbbdee22fe'; // YUBER LOBO

// Usuario de prueba
// $_SESSION['idUsuario'] = '84db9cee-72aa-476a-a61b-fab16c5983af'; // INGRID VERONICA ACEVEDO CACERES

//$_SESSION['idUsuario'] = 'E67D0C54-938B-421C-9D95-826E9AC4AD2C'; // JONATHAN BALAGUERA CARVAJALINO
//$_SESSION['idUsuario'] = 'd8c916d4-7b13-40a7-ad8c-38bae6f76429'; // EMERSON JIMENEZ

// Permisos del sistema
$_SESSION['permisos_todos'] = array(
    'REGISTRO_TAREAS' => true,
    'REGISTRO_SUELDO' => true,
    'CONSULTAS_DE_TAREAS' => true,
    'CONSULTAR TODOS' => false,
    'CONSULTA HORAS EXTRAS' => true,
    'TURNOS' => true,
    'CREAR_TURNOS' => true,
    'ASIGNAR_TURNOS' => true
);

echo "<script> 
var id_usuario = '" . $_SESSION['idUsuario'] . "';
</script>";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>JORNADA BITACORA</title>
    <meta name="viewport" content="width=auto, initial-scale=0.8">
    <meta charset="UTF-8">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <!-- AlertifyJS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Multiple Select -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/multiple-select@1.5.2/dist/multiple-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/multiple-select@1.5.2/dist/multiple-select.min.js"></script>

    <!-- Controlador -->
    <script type="text/javascript" src="../controlador/jornada_bitacora.js?v=20260327-2"></script>
</head>

<body>
    <nav id="nav_menu_opciones" class="navbar navbar-default" role="navigation" style="background-color: #96c6e9;">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex2-collapse">
                <span class="sr-only">Desplegar navegación</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse navbar-ex2-collapse">
            <ul class="nav navbar-nav">
                <li><a class="navbar-right" style="margin-right: 10px;" id="nombreusuario">
                        <span class="glyphicon glyphicon-user" style="color: #050c92;"></span>Inició sesión:</a></li>

                <?php
                //print_r($_SESSION['permisos_todos']['TURNOS']);                  
                $user = $_SESSION['idUsuario'];

                if (isset($_SESSION['permisos_todos']['REGISTRO_TAREAS'])) {
                    echo  '<li><a href="#" class="opciones" data-toggle="collapse" data-target="#idRegistro"><span class="glyphicon glyphicon-folder-open" style="color: #5c80c0;"></span>Registro</a></li>';
                }

                if (isset($_SESSION['permisos_todos']['REGISTRO_SUELDO'])) {
                    echo '<li><a href="#" class="opciones" data-toggle="collapse" data-target="#idSueldo"><span class="glyphicon glyphicon-usd" style="color: #5c80c0;"></span>Sueldo</a></li>';
                }

                if (isset($_SESSION['permisos_todos']['CONSULTAS_DE_TAREAS'])) {
                    echo '<li><a href="#" class="opciones" data-toggle="collapse" data-target="#idConsultar"><span class="glyphicon glyphicon-search" style="color: #5c80c0;"></span>Consulta</a>
                        <ul id="idConsultar" class="collapse">
                        <a href="#" style="color:#55575D;text-decoration: none;" class="opciones" data-toggle="collapse" data-target="#idPorCentroTrabajo"><span class="glyphicon glyphicon-tower" ></span>Por Centro de Trabajo</a><br>';
                    if (isset($_SESSION['permisos_todos']['CONSULTAR TODOS'])) {
                        echo '
                        <a href="#" style="color:#55575D;text-decoration: none;" class="opciones" data-toggle="collapse" data-target="#idPorEmpresa"><span class="glyphicon glyphicon-folder-open"></span>Por Empresa</a><br>
                        <a href="#" style="color:#55575D;text-decoration: none;" class="opciones" data-toggle="collapse" data-target="#idPorActividad"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAAE3UlEQVR4nO2bz2tdRRTHP7bxadUHFhulQtHSYmNx4SYSEwRTSnFhl1JtwaIIYoV24VJs4n/gqpQiVDEWxCq6MiBI8TfWpquSVEqkVFvQuEhrShvIe3LkPHgMc++buffMfdeaL5zNy9w5P2bm/JoJrGIVqdHuQbcEmsC4oQHGdc7a4zbgReAKcA140MAADwCLwAJwCFhLTTEMfO8o856BAd53/n4GeIoaYRNwAmh5lFlRwwwAY8BEgAEmdOyAfrviGdNSnsK7rxgFlnoodBm4GqC4S1f127wxSypD39AAzhdQzoouAHfQZ+zuowGepSb4og/Kf9kPRUd127t4pw8GEJ4uGil9wiZ1Or/otu/g7UCBF9Vzv6LefRC4HbgLeAgYAQ4Cn+vYkDmFdwe7VbalVNHhhMN8OnDlxUm+rIqGoqlJz3zgTph2fhNZTTGWEefz6DrwhsbzopAtPQksR/JuqcwmWAOcjhRAtuJjVgIAjwMXI2U4rbKXxkuRjGf0fFtDzvVspCwieyk0tbCJWfkUyncXRpci5LlStooc16ou9MxbbvssSLS4GSjTtZyyPBgbtarzFSXdJA6vKrzVQ5YVlVlkN8NwTnFyvqS3j4WE1N8zZLmssppjICdBkThfNQ5kyLKYajHGchjGJDlWuBe4kSFTknR4IoOZedYVgc8yZDpsMXk7kCS3vyXRDqQkDue/ZIANFcmzFXhdj5xkm39pjbCsHeMZ/Zs4xy1VGqBBOog33+fpNofQd8DeMm30diDdTRo8o6l1uyTNAbuKCNAOpIeNFZeQ+q6B4i4dA9alMMCIofL368VHVo3/s6bBTwPbdPcJDelvh3O+75TIg9YGOGio/FyG4h8Bj0TMJcb5OKOJM1e0Yp3IMIAkIxbb/kxG3/+JEvOOZLTVZCfcaZkKNykH35n/CriP8pAwfcoz/1HLYuhQSW/vU146xlZoZBhhp1U5PF8wHxjwhLoLRivv2wnucZgNyRM2BjZEpHsbi30ehxdz5jvfheJJj2N83qoltqzd2xi4GZ54exIaQHDS4fmtZVP0YsStzFbP6seEuqIGGPLw3WzZFp/V7m1sR0eSHCowAFo4dfN+1fpi5FJAhuhetUmGV5UB3JxmKsXV2E3gzZx22VlnvKSyVRlgh8NbkrCeKHo5+hvwGrDemW/BGRd7/ssYYJvD+48qrsdvOGmze7lRJJvsfLs98rumR7YgWDyQsDTAD/rt38D+KgyQhekCBrA4AuJfjnfNcTywRV/oCFg9ksoKRWXu8PbrLpB5nivgBIuE4H/RKNC26uBD417+9oh0fNLh/UHqh5KLHgMcKBKKjHA2JhGyeio76qzyFueblp7N1HjUkbFl1dcc9hQ34pTy4I6XNlZqfOrw/MZy8jVaO3Sey/e6n9/rWQ3LJmtIRrun6n+Y6MZaTyN0PtFtkzRCf3V4navD/x/s8viOU8Y3TjLX1x4+Eg5rgWMZRthgtPI+5Y9QI6zLKLnntY1V5sy7217oxyJt8dQYzLkYOamdnJhQ90lOw6aqG+1CRvgpJ6ma0WbGDjXIPUpD+tukJ8lxVz7lO0az43A0Mr0OoSN13PZ52FngaayPztXJ28dCYvQL2rqOacm1NMPbY/Vgug7YrEXLlBZOC9pUEfpTS9opHWP9ZmEV/F/wD2gwmw8TxclJAAAAAElFTkSuQmCC"width="25" height="25" alt="Imagen 20x20" style="color: #050c92;">Por Actividad</a><br>
                        <a href="#" style="color:#55575D;text-decoration: none;" class="opciones" data-toggle="collapse" data-target="#idPorReglas"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAABt0lEQVR4nO2av0rDQBzHP7q09QFKcXEVHMTRoUuH0k3wCRxctA4+jYu0i30DBf9U30IQn0B3ETcrBxc45NLE5O7y+0m/EJIhF+6T733zhSSwUnDtAUfAGorVBz6ABXAJrKNQA+DTQnzb/YU2Z1wnpsAI+NLmzG+IbNJDTTDucrryTHbkwIhdZq4TZnsFNj3nDSU74zoxA17ssdn3POeLdMaFmNg73HNgznLGiYJZlgkDMy4YP5IAUzYTRWo0M3/NBBKd8fVEF3jW5MygIBN1nTlwluohCSDyJlsHpg3c2rFvwBYNZ6LMo9cHcedAbCOkJ8o8ehuDqNITIiD6gXoiTy3g2l77HdhBQU807kTInnCduEnpRIyeaAP3EjJRx5lWaidi9EQnhROxeyI5RIyeMBAPEjJRR+IyocKJaYSeMBBz7T3RkZKJbg1nkjsxi9QT8//QE/PYELseiCqTbRQiezORZeKYsEoGkencfmgx2wlhlCTYsWEag/DBnKIUIgSMGIg6MBvSIKrAGIhHiRA+mLFWiDIwaiCWwaiDcGGyPxTM8VPKxo7lzEKjE3kwqiEy7Qd+CbGS0Q+F/0cn+CFp3AAAAABJRU5ErkJggg==" width="25" height="25" alt="Imagen 20x20">Por Reglas</a><br>
                        ';
                    }
                    echo '</ul>
                    </li>';
                }

                if (isset($_SESSION['permisos_todos']['CONSULTA HORAS EXTRAS'])) {
                    echo '<li><a href="#" class="opciones" data-toggle="collapse" data-target="#idhorasExtras"><span class="glyphicon glyphicon-time" style="color: #5c80c0;"></span>Horas-Extras</a></li>';
                }

                if (isset($_SESSION['permisos_todos']['TURNOS'])) {
                    echo '<li><a href="#" class="opciones" data-toggle="collapse" data-target="#idTurnos"><span class="glyphicon glyphicon-time" style="color: #5c80c0;"></span>Turnos </a>
                            <ul id="idTurnos" class="collapse">';
                    if (isset($_SESSION['permisos_todos']['CREAR_TURNOS'])) {
                        echo '<a href="#" onClick="cargar_turnos(0); Buscar_actividad(); cargar_turnosAll();" style="color:#55575D;text-decoration: none;" class="opciones" data-toggle="collapse" data-target="#crear_t"><span class="glyphicon glyphicon-folder-open" style="color: #5c80c0;"></span> Crear Turnos</a><br>';
                    }

                    if (isset($_SESSION['permisos_todos']['ASIGNAR_TURNOS'])) {
                        echo '<a href="#" onClick="cargar_turnos(1);get_Usuarios(); cargarTurnosAsignados();" style="color:#55575D;text-decoration: none;" class="opciones" data-toggle="collapse" data-target="#idTurnos_t"><span class="glyphicon glyphicon-pencil" style="color: #5c80c0;"></span> Asignar Turnos</a>';
                    }
                    echo '</ul>
                        </li>';
                }

                ?>
                <li style="float: right !important;">
                    <a href="../../Inicio/inicio.php"><span class="glyphicon glyphicon-user" style="color: #5c80c0;"></span> Cerrar Session </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <div class="collapse fade" id="idSueldo" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
            <div class="container-fluid">
                <div class="row">
                    <h2 class="modal-title" id="exampleModalLabel">SALARIO DEL USUARIO</h2>
                    <div class="col-xs-12 col-sm-12 col-md-5 col-lg-5">
                        <label for="idUsuarioSueldo">Usuario:</label>
                        <input type="text" id="idUsuarioSueldo" list="list_UsuarioSueldo" class="form-control sueldo" placeholder="Escriba un Usuario" onkeyup="list_UsuarioSueldo(this)" onchange="table2()" />
                        <datalist id="list_UsuarioSueldo"></datalist>
                    </div>
                    <div class="col-xs-5 col-sm-5 col-md-2 col-lg-2">
                        <label for="Sueldo">Sueldo:</label>
                        <input type="number" id="Sueldo" class="form-control sueldo" placeholder="Escriba el sueldo" />
                    </div>
                    <div class="col-xs-5 col-sm-5 col-md-2 col-lg-2">
                        <label for="FechaSueldo">Fecha Inicio:</label>
                        <input class="form-control sueldo" type="date" id="FechaSueldo"></input>
                    </div><br>
                    <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2">
                        <button type="button" id="idButton" class="btn btn-primary" onclick="save_sueldo()">Guardar</button>
                    </div>
                    <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2"><br>
                        <button type="button" id="idButtonCancelar" class="btn btn-danger" onclick="list_UsuarioSueldo('')">Cancelar</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <div id="div_tabla2"></div>
                </div>
                <div id="controles_tabla_turnos_inferior"></div>
            </div>
        </div>

        <div class="collapse fade" id="idRegistro" tabindex="-1" role="dialog" aria-labelledby="collapse" aria-hidden="true">
            <div class="container-fluid">
                <div class="row">
                    <h2 class="">JORNADA BITACORA</h2>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                        <label for="idUsuario">Usuario:</label>
                        <input type="text" id="idUsuario" list="list_Usuario" class="form-control validate" placeholder="Escriba un Usuario" onkeyup="list_Usuario(this)" onchange="list_Tiquete_Registro() ; table()" />
                        <datalist id="list_Usuario"></datalist>
                    </div>
                    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                        <label for="idTiquete">Tiquete:</label>
                        <select id="idTiquete" class="form-control validate" onchange="buscar_horas(); buscar_detalle();buscar_detalle_tiquete();buscar_horas_pendientes(1);"> </select>
                    </div>
                    <div class="col-xs-12 col-sm-2 col-md-2 col-lg-2">
                        <label>Horas Tiquete:</label>
                        <input type="text" id="Hora" class="form-control validate" placeholder="Horas Tiquete">
                    </div>
                    <div class="col-xs-12 col-sm-2 col-md-2 col-lg-2">
                        <label>Horas a Distribuir:</label>
                        <input type="text" id="Hora_pendientes" class="form-control validate" placeholder="Horas a Distribuir">
                    </div>
                </div>
                <div class="container">
                    <div class="row">
                        <div class="col-xs-12 col-sm-10 col-md-10 col-lg-10" id="div_detalle_tiempos"></div>
                    </div>
                </div>
            </div><br>
            <div class="container-fluid" id="div_actividad">
                <div class="row">
                    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                        <label for="idCentroTrabajo">Centro de trabajo:</label>
                        <input type="text" id="idCentroTrabajo" list="list_Centro" class="form-control validate" placeholder="Escriba un Centro de trabajo" onkeyup="list_Centro(this)" />
                        <datalist id="list_Centro"></datalist>
                    </div>
                    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                        <label for="idActividad">Actividades:</label>
                        <input type="text" id="idActividad" list="list_Actividad" class="form-control validate" placeholder="Escriba una Actividad" onkeyup="list_Actividad(this)" />
                        <datalist id="list_Actividad"></datalist>
                    </div>
                    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                        <label for="idUnidadNegocio">Unidad de Negocio:</label>
                        <input type="text" id="idUnidadNegocio" list="list_Negocio" class="form-control validate" placeholder="Escriba un Negocio" onkeyup="list_Negocio(this)" />
                        <datalist id="list_Negocio"></datalist>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                        <label for="Descripcion">Descripción:</label>
                        <input type="text" id="Descripcion" class="form-control validate" placeholder="Escriba una Descripción" />
                    </div>
                    <div class="col-xs-12 col-sm-4 col-md-1 col-lg-1">
                        <label>Horas:</label>
                        <input class="form-control validate" type="Number" max="12" id="horas_distribuir" onkeyup="valida('H')" step="1"></input>
                    </div>
                    <div class="col-xs-12 col-sm-4 col-md-1 col-lg-1">
                        <label>Minutos:</label>
                        <input class="form-control validate" type="Number" max="59" id="minutos_distribuir" onkeyup="valida('M')" step="1"></input>
                    </div>
                    <div class="col-xs-1 col-sm-1 col-md-1 col-lg-1">
                        <br>
                        <button type="button" id="buttonn" class="btn btn-primary" onclick="save_jornada_bitacora()">Guardar</button>
                    </div>
                    <div class="col-xs-1 col-sm-1 col-md-1 col-lg-1">
                        <br>
                        <button type="button" id="ButtonCancelar" class="btn btn-danger" onclick="list_Centro('')">Cancelar</button>
                    </div>
                </div>
            </div>
            <div class="container-fluid">
                <div class="container">
                    <div class="row">
                        <div id="div_detail_clasif"></div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <br>
                <div id="div_tabla"></div>
            </div>
        </div>

        <div class="collapse fade" id="idPorActividad" tabindex="-1" role="dialog" aria-labelledby="collapse" aria-hidden="true">
            <div class="container-fluid">
                <div class="row">
                    <h2 class="">CONSULTA BITACORA POR ACTIVIDAD</h2>
                    <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3"> </div>
                    <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                        <label for="FechaInicialConsulta">Fecha Inicio:</label>
                        <input class="form-control consultaBit_actividad" type="date" id="FechaInicialConsulta"></input>
                    </div>
                    <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                        <label for="FechaFinalConsulta">Fecha Fin:</label>
                        <input class="form-control consultaBit_actividad" type="date" id="FechaFinalConsulta"></input>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-5 col-lg-5">
                        <label for="idUnidadNegocioConsulta">Unidad de Negocio:</label>
                        <input type="text" id="idUnidadNegocioConsulta" list="list_NegocioConsulta" class="form-control consultaBit_actividad" placeholder="Escriba un Negocio" onkeyup="list_NegocioConsulta(this)" onchange="si(this)" onblur="si(this)" />
                        <datalist id="list_NegocioConsulta"></datalist>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-5 col-lg-5">
                        <label for="idUsuarioConsulta_actividad">Usuario:</label>
                        <input type="text" id="idUsuarioConsulta" list="list_UsuarioConsulta_actividad" class="form-control consultaBit_actividad" placeholder="Escriba un Usuario" onkeyup="list_UsuarioConsulta_actividad(this)" onchange="si(this)" onblur="si(this)" />
                        <datalist id="list_UsuarioConsulta_actividad"></datalist>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-5 col-lg-5">
                        <label for="idActividadConsulta">Actividades:</label>
                        <input type="text" id="idActividadConsulta" list="list_ActividadConsulta" class="form-control consultaBit_actividad" placeholder="Escriba una Actividad" onkeyup="list_ActividadConsulta(this)" onchange="si(this)" onblur="si(this)" />
                        <datalist id="list_ActividadConsulta"></datalist>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-5 col-lg-5">
                        <label for="idCentroTrabajoConsulta">Centro de trabajo:</label>
                        <input type="text" id="idCentroTrabajoConsulta" list="list_CentroConsulta_actividad" class="form-control consultaBit_actividad" placeholder="Escriba un Centro de trabajo" onkeyup="list_CentroConsulta_actividad(this)" onchange="si(this)" onblur="si(this)" />
                        <datalist id="list_CentroConsulta_actividad"></datalist>
                    </div>

                    <div class="col-xs-1 col-sm-1 col-md-1 col-lg-1">
                        <button type="button" id="button_actividad" class="btn btn-primary" onclick="get_Consulta()">Buscar</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <div id="div_tabla3"></div>
            </div>
        </div>

        <div class="collapse fade" id="idPorEmpresa" tabindex="-1" role="dialog" aria-labelledby="collapse" aria-hidden="true">
            <div class="container-fluid">
                <div class="row">
                    <h2 class="">CONSULTA BITACORA POR EMPRESA</h2>
                    <div class="col-xs-3 col-sm-3 col-md-4 col-lg-4">
                        <label for="idempresa">Empresa:</label>
                        <input type="text" id="idempresa" list="list_empresa" class="form-control consultaBitacora" placeholder="Escriba Empresa" onkeyup="list_empresa(this)" />
                        <datalist id="list_empresa"></datalist>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                        <label for="idUnidadNegocioConsulta">Nombre Biometrico:</label>
                        <input type="text" id="idcentroTrabajo" list="list_CentroConsulta" class="form-control consultaBitacora" placeholder="Todos" onkeyup="list_CentroConsulta(this)" onchange="si(this)" onblur="si(this)" />
                        <datalist id="list_CentroConsulta"></datalist>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
                        <label for="idUsuarioConsulta">Empleado:</label>
                        <input type="text" id="idUsuarioConsulta" list="list_UsuarioConsulta" class="form-control consultaBitacora" placeholder="Todos" onkeyup="list_UsuarioConsulta(this)" />
                        <datalist id="list_UsuarioConsulta"></datalist>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                        <label for="FechaIni">Fecha Inicio:</label>
                        <input class="form-control" type="date" id="FechaIni"></input>
                    </div>
                    <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                        <label for="FechaFinal">Fecha Fin:</label>
                        <input class="form-control" type="date" id="FechaFinal"></input>
                    </div>
                    <div class="col-xs-1 col-sm-1 col-md-1 col-lg-1">
                        <label>&nbsp;</label><br>
                        <button type="button_bit_empresa" id="button_bit_empresa" class="btn btn-primary" onclick="get_Consulta_gral()">Buscar</button>
                    </div>
                </div>

            </div>
            <div class="table-responsive">
                <div id="div_tabla4"></div>
            </div>
        </div>

        <div class="collapse fade" id="idPorCentroTrabajo" tabindex="-1" role="dialog" aria-labelledby="collapse" aria-hidden="true">
            <div class="container-fluid">
                <div class="row">
                    <h2 class="">CONSULTA BITACORA POR CENTRO DE TRABAJO</h2>
                    <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                        <label for="CentroTrabajoMina">Centro de Trabajo:</label>
                        <input type="text" id="CentroTrabajo_consulta" list="list_Centrotrabajo_consulta" class="form-control"
                            onkeyup="list_Centrotrabajo(this, 'consulta')" />
                        <datalist id="list_Centrotrabajo_consulta"></datalist>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                        <label for="FechaInicialMina">Fecha Inicio:</label>
                        <input class="form-control" type="date" id="FechaInicialMina" />
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                        <label for="FechaFinalMina">Fecha Fin:</label>
                        <input class="form-control" type="date" id="FechaFinalMina" />
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                        <label for="CargoMina">Cargo:</label>
                        <input type="text" id="CargoMina" list="list_CargoMina" class="form-control" placeholder="Opcion no Disponible por el Momento" onkeyup="list_CargoMina(this)" disabled />
                        <datalist id="list_CargoMina"></datalist>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                        <label for="UsuarioMina">Usuario:</label>
                        <input type="text" id="UsuarioMina" list="list_UsuarioMina" class="form-control" placeholder="Opcion no Disponible por el Momento" onkeyup="list_UsuarioMina(this)" disabled />
                        <datalist id="list_UsuarioMina"></datalist>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                        <label>&nbsp;</label><br>
                        <button type="button" id="button_mina" class="btn btn-primary" onclick="get_ConsultaCentroTrabajo()">Buscar</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <div id="div_tabla_centrotrabajo"></div>
                </div>
            </div>
        </div>

        <div class="collapse fade" id="idPorReglas" tabindex="-1" role="dialog" aria-labelledby="collapse" aria-hidden="true">
            <div class="row">
                <h2 class="">CONSULTA BITACORA POR REGLAS</h2>
                <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                </div>
                <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                    <label for="FechaInicialReglas">Fecha Inicio:</label>
                    <input class="form-control" type="date" id="FechaInicialReglas"></input>
                </div>
                <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                    <label for="FechaFinalReglas">Fecha Fin:</label>
                    <input class="form-control" type="date" id="FechaFinalReglas"></input>
                </div>
                <div class="col-xs-4 col-sm-4 col-md-5 col-lg-5">
                    <label for="idUnidadNegocioConsultaReglals">Unidad de Negocio:</label>
                    <input type="text" id="idUnidadNegocioConsultaReglals" list="list_NegocioConsulta" class="form-control consulta" placeholder="Escriba un Negocio" onkeyup="list_NegocioConsulta(this)" onchange="si(this)" onblur="si(this)" />
                    <datalist id="list_NegocioConsulta"></datalist>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-5 col-lg-5">
                    <label for="idUsuarioConsultaReglas">Usuario:</label>
                    <input type="text" id="idUsuarioConsultaReglas" list="list_UsuarioConsulta_a" class="form-control consulta" placeholder="Escriba un Usuario" onkeyup="list_UsuarioConsulta(this)" onchange="si(this)" onblur="si(this)" />
                    <datalist id="list_UsuarioConsulta_a"></datalist>
                </div>
                <div class="col-xs-4 col-sm-4 col-md-5 col-lg-5">
                    <label for="idActividadConsultaReglas">Actividades:</label>
                    <input type="text" id="idActividadConsultaReglas" list="list_ActividadConsulta" class="form-control consulta" placeholder="Escriba una Actividad" onkeyup="list_ActividadConsulta(this)" onchange="si(this)" onblur="si(this)" />
                    <datalist id="list_ActividadConsulta"></datalist>
                </div>
                <div class="col-xs-8 col-sm-8 col-md-5 col-lg-5">
                    <label for="idCentroTrabajoConsultaReglas">Centro de trabajo:</label>
                    <input type="text" id="idCentroTrabajoConsultaReglas" list="list_CentroConsulta_b" class="form-control consulta" placeholder="Escriba un Centro de trabajo" onkeyup="list_CentroConsulta_b(this)" onchange="si(this)" onblur="si(this)" />
                    <datalist id="list_CentroConsulta_b"></datalist>
                </div>
                <div class="col-xs-8 col-sm-8 col-md-5 col-lg-5">
                    <label for="list_ReglasConsulta">Reglas</label>
                    <input type="text" id="idReglas" list="list_Reglas" class="form-control consulta" placeholder="Escriba un Centro de trabajo" onkeyup="list_Reglas(this)" />
                    <datalist id="list_ReglasConsulta"></datalist>
                </div>
                <div class="col-xs-1 col-sm-1 col-md-1 col-lg-1"><br>
                    <button type="button" id="buttonReglas" class="btn btn-primary" onclick="get_ConsultaReglas()">Buscar</button>
                </div>
            </div>
            <div class="table-responsive">
                <div id="div_tablaReglas"></div>
            </div>
        </div>

        <div class="collapse fade" id="idhorasExtras" tabindex="-1" role="dialog" aria-labelledby="collapse" aria-hidden="true">
            <div class="row">
                <h2 class="">CONSULTA HORAS EXTRAS</h2>
                <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                    <label for="FechaInicialConsulta_extra">Fecha Inicio:</label>
                    <input class="form-control" type="date" id="FechaInicialConsulta_extra"></input>
                </div>
                <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                    <label for="FechaFinalConsulta_extra">Fecha Fin:</label>
                    <input class="form-control" type="date" id="FechaFinalConsulta_extra"></input>
                </div>
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                    <label for="idUsuarioConsulta_extra">Usuario:</label>
                    <input type="text" id="idUsuarioConsulta_extra" list="list_UsuarioConsulta_extras" class="form-control consulta" placeholder="Escriba un Usuario" onkeyup="list_UsuarioConsulta_extra(this)" onchange="si(this)" onblur="si(this)" />
                    <datalist id="list_UsuarioConsulta_extras"></datalist><br>
                </div>
                <div class="col-xs-12 col-sm-1 col-md-1 col-lg-1">
                    <label>&nbsp;</label>
                    <button type="button" id="button_ConsultasHorasExtras" class=" form-control btn btn-primary" onclick="get_horasExtras()"
                        style=" width: -webkit-fill-available;">Buscar</button>
                </div>
            </div>
            <div class="row">
                <div class="modal-footer">
                    <div class="table-responsive">
                        <div id="div_tabla_extras"></div>
                    </div>
                </div>
            </div>
        </div>


        <div class="collapse fade" id="crear_t" tabindex="-1" role="dialog" aria-labelledby="collapse" aria-hidden="true">
            <div class="container-fluid">
                <h2 class="text-center mb-4">CREAR TURNO TRABAJO</h2>

                <!-- Primera fila: Datos principales del turno -->
                <div class="row mb-3">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                        <div class="form-group">
                            <label for="Nombre_turno">Nombre del turno:</label>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <input type="checkbox" id="descripcion_auto" onchange="toggleDescripcionMode()">
                                    <label for="descripcion_auto" style="margin-bottom: 0; margin-left: 5px;">Auto</label>
                                </span>
                                <input class="form-control" type="text" id="Nombre_turno" placeholder="Nombre del turno">
                            </div>
                            <small class="text-muted" id="descripcion_help">Introduce manualmente el nombre del turno</small>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                        <label>Hora Entrada:</label>
                        <input class="form-control" type="time" id="FechaInicial_turno"
                            onchange="calcularDuracion(this.value, document.getElementById('FechaFinal_turno').value)">
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                        <label>Hora Salida:</label>
                        <input class="form-control" type="time" id="FechaFinal_turno"
                            onchange="calcularDuracion(document.getElementById('FechaInicial_turno').value, this.value)">
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                        <label>Duración:</label>
                        <!-- Código hecho por Mario — Mostrar la duración como texto plano sin AM/PM -->
                        <input class="form-control" type="text" id="Duracion_turno" readonly
                            style="background-color: #f9f9f9;">
                    </div>
                </div>

                <!-- Sección de descanso agrupada en un contenedor -->
                <div class="panel panel-default" style="border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="panel-heading" style="background-color: #f5f5f5; border-bottom: 1px solid #ddd; padding: 10px 15px;">
                        <!-- Checkbox de descanso -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <div class="checkbox">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" id="incluir_descanso" onchange="toggleDescansoFields()" style="margin-right: 5px;">
                                    <i class="glyphicon glyphicon-time" style="margin-right: 8px; color: #337ab7;"></i>
                                    <span style="font-weight: 600; color: #333;">Incluir período de descanso</span>
                                </label>
                                <small class="text-muted d-block">Define un intervalo de descanso dentro del turno</small>
                            </div>
                        </div>
                    </div>

                    <!-- Campos de descanso (inicialmente ocultos) -->
                    <div id="campos_descanso" class="panel-body" style="display: none; padding: 15px; background-color: #f9f9f9;">
                        <div class="row">
                            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                                <div class="form-group">
                                    <label>Inicio Descanso:</label>
                                    <input class="form-control" type="time" id="inicio_descanso"
                                        onchange="calcularDuracionDescanso()">
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                                <div class="form-group">
                                    <label>Fin Descanso:</label>
                                    <input class="form-control" type="time" id="fin_descanso"
                                        onchange="calcularDuracionDescanso()">
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                                <div class="form-group">
                                    <label>Duración Descanso:</label>
                                    <!-- Código hecho por Mario — mostrar intervalos sin formato AM/PM -->
                                    <input class="form-control" type="text" id="duracion_descanso" readonly
                                        style="background-color: #f5f5f5;">
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                                <div class="form-group">
                                    <label>Descripción:</label>
                                    <input class="form-control" type="text" id="descripcion_descanso"
                                        placeholder="Ej: Almuerzo, Refrigerio...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cuarta fila: Botón crear -->
                <div class="row mb-4">
                    <div class="col-xs-12 text-center">
                        <button type="button" id="button_crear_t" class="btn btn-primary"
                            onclick="get_crear_turno()">
                            <i class="glyphicon glyphicon-time"></i> Crear Turno
                        </button>
                        <button type="button" id="button_cancelar_edicion" class="btn btn-default"
                            style="margin-left: 10px; display: none;" onclick="cancelarEdicionTurno()">
                            Cancelar edición
                        </button>
                    </div>
                </div>
                <div id="turno_creado_warning" class="alert alert-warning" style="display:none; margin-top: 10px;">
                    <p id="turno_creado_warning_text" style="margin-bottom: 5px;"></p>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="confirmar_edicion_turno">
                            Confirmo que entiendo que esta acción afectará cualquier asignación activa relacionada con este turno.
                        </label>
                    </div>
                </div>

                <h3 class="mb-4">TURNOS CREADOS</h3>
                <!-- Tabla de turnos existentes -->
                <div id="controles_tabla_turnos_superior"></div>
                <div class="table-responsive">
                    <div id="div_tabla_turnos" class="text-center">
                        <!-- La tabla se mostrará aquí -->
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="modal fade" id="idTurnos_t" tabindex="-1" role="dialog" aria-labelledby="Modal" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="exampleModalLabel">Horas por Turno</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                    <label for="idUsuarioTurnos">Usuario:</label>
                                    <input type="text" id="idUsuarioTurnos" list="list_UsuarioSueldo" class="form-control turnos" placeholder="Escriba un Usuario" onkeyup="list_UsuarioSueldo(this)" onchange="table2()"/>
                                    <datalist id="list_UsuarioSueldo"></datalist>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-5 col-sm-5 col-md-5 col-lg-5">
                                    <label for="h_Turno">Horas por Turno</label>
                                    <input type="number" id="h_Turno" class="form-control turnos" placeholder="Escriba las horas del turno"/>
                                </div>
                                <div class="col-xs-5 col-sm-5 col-md-5 col-lg-5">
                                    <label for="FechaTurno">Fecha Inicio:</label>
                                    <input class="form-control turno" type="date" id="FechaTurno"></input>
                                </div>
                                <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2">
                                    <button type="button" id="idButtonTurno" class="btn btn-primary"onclick="save_turno()">Guardar</button>
                                </div>                            
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="table-responsive">
                                <div id="div_tabla2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>-->
        <div class="collapse fade" id="idTurnos_t" tabindex="-1" role="dialog" aria-labelledby="collapse" aria-hidden="true">
            <div class="container-fluid" style="border: 1px solid; border-radius: 5px;">
                <div class="row">
                    <h2 class="">ASIGNAR TURNOS</h2>
                    <div class="col-xs-2 col-sm-3 col-md-3 col-lg-3">
                        <label>Centro Trabajo:</label><br>
                        <input type="text" id="CentroTrabajo_asignar" list="list_Centrotrabajo_asignar" class="form-control"
                            placeholder="Escriba un Centro de trabajo" onfocus="list_Centrotrabajo(this, 'asignar')" onkeyup="list_Centrotrabajo(this, 'asignar')" />
                        <datalist id="list_Centrotrabajo_asignar"></datalist>
                    </div>
                    <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2">
                        <label>Turnos:</label><br>
                        <select class="form-control" id="lista_turnos_a" onchange="Buscar_detalle_tt(this); buscar_asignados(this);"></select>
                    </div>
                    <div class="col-xs-3 col-sm-2 col-md-2 col-lg-2">
                        <label>Fecha Inicio:</label><br>
                        <input class="form-control" type="date" name="fecha_ini" id="fecha_ini" onchange="validarFechas()">
                    </div>
                    <div class="col-xs-3 col-sm-2 col-md-2 col-lg-2">
                        <label>Fecha Fin:</label><br>
                        <input class="form-control" type="date" name="fecha_fin" id="fecha_fin" onchange="validarFechas()">
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-3 col-lg-3">
                        <label>Dias Laborales:</label><br>
                        <div class="row">
                            <select name="dias_laborales" id="dias_laborales" class="form-control" multiple="multiple" style="text-align: left !important; border: 1px solid;"></select>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <!-- <h3 class="col-xs-12">Registro Múltiple de Usuarios</h3> -->
                    <!--
                    // DESHABILITADO POR MARIO — se usan los filtros de columna en lugar de la barra de búsqueda global
                    <div class="col-xs-9 col-sm-10 col-md-10 col-lg-10">
                        <label for="buscar_usuarios">Buscar Usuarios:</label>
                        <input type="text" id="buscar_usuarios" class="form-control" placeholder="Buscar por nombre, cédula o cargo" onkeyup="get_Usuarios(this.value)">
                    </div>
                    -->
                    <div class="col-xs-3 col-sm-2 col-md-2 col-lg-2" style="margin-top: 25px;">
                        <button type="button" class="btn btn-success" onclick="asignarTurnoMultiple()">
                            Asignar Turnos
                        </button>
                    </div>
                </div>

                <div id="controles_tabla_usuarios_superior"></div>
                <div class="table-responsive" style="margin-top: 15px;">
                    <table id="tabla_usuarios_asignacion" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;">
                            <input type="checkbox" id="seleccionar_todos" onclick="seleccionarTodos()">
                        </th>
                        <th class="text-center">Nombre</th>
                        <th class="text-center">Cédula</th>
                        <th class="text-center">Cargo</th>
                        <th class="text-center">Empresa</th>
                    </tr>
                    <tr class="filters" id="filtros_usuarios">
                        <th></th>
                        <th>
                            <input type="text" id="filter_nombre" class="form-control input-sm" placeholder="Filtrar nombre" />
                        </th>
                        <th>
                            <input type="text" id="filter_cedula" class="form-control input-sm" placeholder="Filtrar cédula" />
                        </th>
                        <th>
                            <input type="text" id="filter_cargo" class="form-control input-sm" placeholder="Filtrar cargo" />
                        </th>
                        <th>
                            <select id="filter_empresa" class="form-control input-sm">
                                <option value="">Todas</option>
                            </select>
                        </th>
                    </tr>
                </thead>
                        <tbody id="tabla_usuarios_multiple">
                            <!-- Los usuarios se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
                <div id="controles_tabla_usuarios_inferior"></div>

                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-xs-12 text-center">
                        <span id="conteo_seleccionados">0</span> usuarios seleccionados
                    </div>
                </div>
                <!-- <div class="container">
                    <div class="row">
                        <div id="div_tabla_turnos_asignar"></div>
                    </div>
                </div><br> -->
            </div>
            <!-- REGISTRO DE TURNOS -->
            <!-- <div class="container-fluid" style="border: 1px solid; border-radius: 5px; margin-top: 15px;"> -->
            <!-- TURNOS ASIGNADOS -->
            <div class="container-fluid" style="border: 1px solid; border-radius: 5px; margin-top: 15px;">
                <div class="row">
                    <h3 class="col-xs-12">Turnos Asignados por Centro de Trabajo</h3>
                </div>
                <div class="table-responsive" id="div_turnos_asignados">
                    <!-- La tabla se cargará aquí dinámicamente -->

                </div>
            </div><br>
        </div> <br>

    </div>

    <!-- Modal para editar programación de turnos -->
    <div class="modal fade" id="modalEditarTurno" tabindex="-1" role="dialog" aria-labelledby="modalEditarTurnoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalEditarTurnoLabel">Editar Programación de Turno</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="div_editar_turno">
                    <form id="formEditarTurno">
                        <!-- Campo oculto para el ID de programación -->
                        <input type="hidden" id="edit_idProgramacion">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_nombreUsuario">Usuario:</label>
                                    <input type="text" id="edit_nombreUsuario" class="form-control" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_fechaInicio">Fecha Inicio:</label>
                                    <input type="date" id="edit_fechaInicio" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_fechaFin">Fecha Fin:</label>
                                    <input type="date" id="edit_fechaFin" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_idCentroTrabajo">Centro de Trabajo:</label>
                                    <input type="text" id="edit_idCentroTrabajo" list="list_CentroTrabajoEdit" class="form-control" placeholder="Seleccione un centro de trabajo" onfocus="list_CentroTrabajoEdit()" />
                                    <datalist id="list_CentroTrabajoEdit"></datalist>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_turnoTipo">Turno asignado:</label>
                                    <select id="edit_turnoTipo" class="form-control"></select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Hora Inicio:</label>
                                    <input type="text" id="edit_horaInicio" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Hora Fin:</label>
                                    <input type="text" id="edit_horaFin" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div id="edit_validation_messages" class="text-danger" style="min-height: 24px;"></div>
                                <p class="text-warning">
                                    <small id="edit_warning_text">Este cambio afecta directamente al trabajador asignado.</small>
                                </p>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="edit_confirmar_cambios"> Confirmo que entiendo que estos cambios afectarán a los usuarios asignados.
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarCambiosProgramacion()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="idReglasDetalles" tabindex="-1" role="dialog" aria-labelledby="Modal" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabelT">Editar Reglas Detalle</h4>
                    <button type="button" id="cerrarmodalidReglasDetalles" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" id="i">
                        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                            <label for="FechaInicialReglasDetalle">Fecha Inicial</label>
                            <input type="datetime-local" id="FechaInicialReglasDetalle" class="form-control detallesRegla" />
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                            <label for="FechaFinalReglasDetalle">Fecha Final</label>
                            <input type="datetime-local" id="FechaFinalReglasDetalle" class="form-control detallesRegla" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                            <label for="ReglaName">Regla</label>
                            <select id="ReglaName" class="form-control detallesRegla"></select>
                            <!-- <input class="form-control turno" type="text" id="ReglaName" ></input> -->
                        </div>
                        <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
                            <label for="ReglaValor">Valor</label>
                            <input class="form-control detallesRegla" type="text" id="ReglaValor"></input>
                        </div>

                        <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3" id="campoRegla">
                            <label for="campoIdRegla">Id</label>
                            <input class="form-control detallesRegla" type="text" id="campoIdRegla"></input>
                        </div>
                        <div class="col-xs-3 col-sm-3 col-md-3 col-lg-3" id="campoidxid">
                            <label for="IdxidRegla">Idxid</label>
                            <input class="form-control detallesRegla" type="text" id="IdxidRegla"></input>
                        </div>
                        <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2"><br>
                            <button type="button" id="idButtonRegla" class="btn btn-primary" onclick="save_Regla()">Guardar</button>
                        </div>
                        <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2"><br>
                            <button type="button" id="idButtonCancelarR" class="btn btn-danger " data-dismiss="modal" aria-label="Cerrar">Cancelar</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="table-responsive">
                        <div id="div_tabla4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mostrar_detalle" tabindex="-1" role="dialog" aria-labelledby="Modal" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="exampleModalLabel">Detalle : <label id="turno_especifico"></label></h4>

                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
                            <label for="idUsuarioTurnos">Usuario:</label>
                            <input type="text" id="user_nombre" class="form-control turnos" disabled />
                            <input type="hidden" id="user_documento" class="form-control turnos" disabled />
                            <input type="hidden" id="user_empresa" class="form-control turnos" disabled />
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                            <label for="idUsuarioTurnos">Tiquete:</label>
                            <input type="text" id="user_tiquete" class="form-control turnos" disabled />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="table-responsive">
                        <div id="div_tabla_detalle_biometrico"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modificar_detalle" tabindex="-1" role="dialog" aria-labelledby="Modal" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <input id="turno_actual" type="text">
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                            <label>Fecha Salida:</label>
                            <input type="date" id="fecha_detalle" class="form-control turnos" />
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                            <label>Hora Salida:</label>
                            <input type="time" id="hora_detalle" class="form-control turnos" />
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                            <label>Tipo de Movimiento:</label>
                            <input type="text" id="salida" class="form-control turnos" disabled value="Salida" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" style="text-align:right;">
                            <button type="button" id="idButtonCancelarR" class="btn btn-primary" onclick="grabar_correccion()" data-dismiss="modal" aria-label="Cerrar">Grabar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    </div>
    <script>
        format_mulsipleselect()
    </script>
</body>

</html>
