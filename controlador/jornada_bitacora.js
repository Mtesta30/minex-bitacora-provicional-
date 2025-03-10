document.addEventListener("DOMContentLoaded", () => {
    list_Centro("");
    list_Centrotrabajo("");
    list_Usuario("");
    list_Actividad("");
    list_Negocio("");
    list_UsuarioSueldo("");
    get_usuario();

    $('#ButtonCancelar').hide();
    $('#idButtonCancelar').hide();
    $('#idButtonCancelarT').hide();
    $("#idxid").hide();
    $("#campoRegla").hide();
    $("#campoidxid").hide();
});
/*
async function list_Centro_trabajo(object){
    $('#ButtonCancelar').hide();
    document.getElementById("idActividad").value="";
    document.getElementById("Descripcion").value="";
    document.getElementById("FechaInicial").value="";
    $('#button').attr('onclick', 'save();');
    $('#button').removeClass('btn-warning')
    $('#button').addClass('btn btn-primary');
    $('#button').text('Guardar');
    let list_Centro=document.getElementById("list_Centro_trabajo");
    let url = "../modelo/jornada_bitacora.php?band=get_CentrosDeTrabajo";
    if(object==null) {
        texto_Centro="";
    }else{
        texto_Centro = object.value
    }
    let param = {texto_Centro:texto_Centro};
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Centro.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Centro.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}  */

async function list_Centro(object) {
    $('#ButtonCancelar').hide();
    document.getElementById("idActividad").value = "";
    document.getElementById("Descripcion").value = "";
    $('#button').attr('onclick', 'save();');
    $('#button').removeClass('btn-warning')
    $('#button').addClass('btn btn-primary');
    $('#button').text('Guardar');
    let list_Centro = document.getElementById("list_Centro");
    let url = "../modelo/jornada_bitacora.php?band=get_CentrosDeTrabajo";
    if (object == null) {
        texto_Centro = "";
    } else {
        texto_Centro = object.value
    }
    let param = { texto_Centro: texto_Centro };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Centro.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Centro.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_CentrotrabajoBio(object) {
    let list_Centro = document.getElementById("list_CentrotrabajoMina");
    let url = "../modelo/jornada_bitacora.php?band=get_CentrosDeTrabajo";
    let texto_Centro = object.value;
    let param = { texto_Centro: texto_Centro };
    let data = JSON.stringify(param);

    try {
        let response = await sendRequest(url, data);
        let json_parse = JSON.parse(response);
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Centro.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id);
                list_Centro.appendChild(nuevaOpcion);
            }
        });
    } catch (error) {
        console.log(error);
    }
}

async function list_CargoMina(object) {
    let list_Cargo = document.getElementById("list_CargoMina");
    let url = "../modelo/jornada_bitacora.php?band=get_Cargos";
    let texto_Cargo = object.value;
    let param = { texto_Cargo: texto_Cargo };
    let data = JSON.stringify(param);

    try {
        let response = await sendRequest(url, data);
        let json_parse = JSON.parse(response);
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Cargo.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id);
                list_Cargo.appendChild(nuevaOpcion);
            }
        });
    } catch (error) {
        console.log(error);
    }
}

async function list_UsuarioMina(object) {
    let list_Usuario = document.getElementById("list_UsuarioMina");
    let url = "../modelo/jornada_bitacora.php?band=get_UsuariosMina";
    let idSubGrupo = get_data_id("CargoMina", "list_CargoMina");
    let param = { idSubGrupo: idSubGrupo };
    let data = JSON.stringify(param);

    try {
        let response = await sendRequest(url, data);
        let json_parse = JSON.parse(response);
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Usuario.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id);
                list_Usuario.appendChild(nuevaOpcion);
            }
        });
    } catch (error) {
        console.log(error);
    }
}

async function get_ConsultaMina() {
    let FechaInicial = document.getElementById("FechaInicialMina").value;
    let FechaFinal = document.getElementById("FechaFinalMina").value;
    let Cargo = get_data_id("CargoMina", "list_CargoMina");
    let Usuario = get_data_id("UsuarioMina", "list_UsuarioMina");
    let CentroTrabajo = get_data_id("CentroTrabajoMina", "list_CentrotrabajoMina");

    let param = { FechaInicial: FechaInicial, FechaFinal: FechaFinal, Cargo: Cargo, Usuario: Usuario, CentroTrabajo: CentroTrabajo };
    let data = JSON.stringify(param);
    let url = "../modelo/jornada_bitacora.php?band=get_ConsultaMina";

    try {
        let response = await sendRequest(url, data);
        document.getElementById("div_tabla_mina").innerHTML = response;
        $('#idTableMina').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "ordering": false,
            "searching": true,
            dom: 'Bfrtip',
            buttons: [
                'excel', 'print'
            ]
        });
    } catch (error) {
        console.log(error);
    }
}

async function list_Centrotrabajo(object) {
    $('#ButtonCancelar').hide();
    document.getElementById("idActividad").value = "";
    document.getElementById("Descripcion").value = "";
    document.getElementById("fecha_ini").value = "";
    $('#idTiquete').find('option').remove();
    $('#button').attr('onclick', 'save();');
    $('#button').removeClass('btn-warning')
    $('#button').addClass('btn btn-primary');
    $('#button').text('Guardar');
    let list_Centro = document.getElementById("list_Centrotrabajo");
    let url = "../modelo/jornada_bitacora.php?band=get_CentrosDeTrabajo";
    if (object == null) {
        texto_Centro = "";
    } else {
        texto_Centro = object.value
    }
    let param = { texto_Centro: texto_Centro };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Centro.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Centro.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_Usuario(object) {
    document.getElementById("idTiquete").value;
    let list_Usuario = document.getElementById("list_Usuario");
    let url = "../modelo/jornada_bitacora.php?band=get_Usuarios";
    texto_Usuario = object.value
    let param = { texto_Usuario: texto_Usuario };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Usuario.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Usuario.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_Usuario_asignar(object) {
    let list_Usuario = document.getElementById("list_Usuario_asignar");
    let url = "../modelo/jornada_bitacora.php?band=get_Usuarios";
    texto_Usuario = object.value
    let param = { texto_Usuario: texto_Usuario };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Usuario.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Usuario.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function dias() {
    let fecha_ini = document.getElementById("fecha_ini").value;
    let fecha_fin = document.getElementById("fecha_fin").value;
    let url = "../modelo/jornada_bitacora.php?band=dias";
    let param = { fecha_ini: fecha_ini, fecha_fin: fecha_fin };
    var fecha = new Date().toISOString().slice(0, 10);
    if (fecha_ini >= fecha) {
        let data = JSON.stringify(param);
        let response = await sendRequest(url, data);
        console.log(response)
        let datos = JSON.parse(response);
        $('#dias_laborales').find('option').remove();
        var opcion = '';
        $.each(datos, function (key, val) {
            //console.log(key, val)     
            opcion += '<option value="' + val.id + '">' + val.name + '</option>';
        });
        $('#dias_laborales').html(opcion);
    } else {
        alertify.error('Fecha Inicio no debe ser menor a la fecha actual..')
        $('#dias_laborales').find('option').remove();
        $('#dias_laborales').html('');
    }
    format_mulsipleselect();
}

function format_mulsipleselect() {
    $(function () {
        $('#dias_laborales').change(function () {
            //  console.log($(this).val());
        }).multipleSelect({
            width: '100%'
        });
    });
    $('#contenedor').hide();
}


async function get_turnos_user_activo(object) {
    usuario = consulta('idUsuario_asignar', 'list_Usuario_asignar')
    let param = { usuario: usuario };
    let data = JSON.stringify(param);
    $('#div_tabla_activos').html('');
    let url = "../modelo/jornada_bitacora.php?band=get_turnos_user_activo";
    let response = await sendRequest(url, data);
    console.log(response)
    $('#div_tabla_activos').html(response);
}

async function list_UsuarioSueldo(object) {
    $('#idButtonCancelarT').hide();
    $('#idButtonCancelar').hide();
    let list_UsuarioSueldo = document.getElementById("list_UsuarioSueldo");
    // alert(list_UsuarioSueldo)
    let url = "../modelo/jornada_bitacora.php?band=get_Usuarios";
    texto_Usuario = object.value
    let param = { texto_Usuario: texto_Usuario };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_UsuarioSueldo.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_UsuarioSueldo.appendChild(nuevaOpcion);
            }
            /*  document.getElementById("Sueldo").value="";
                 document.getElementById("FechaSueldo").value="";
               //   $('#idButton').attr('onclick', 'save_sueldo();');
                $('#idButton').removeClass('btn-warning')
                $('#idButton').addClass('btn btn-primary');
                $('#idButton').text('Guardar');

              document.getElementById("h_Turno").value="";
                document.getElementById("FechaTurno").value="";
            //    $('#idButtonTurno').attr('onclick', 'save_turno();');
                $('#idButtonTurno').removeClass('btn-warning')
                $('#idButtonTurno').addClass('btn btn-primary');
                $('#idButtonTurno').text('Guardar');*/
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_Actividad(object) {
    let list_Actividad = document.getElementById("list_Actividad");
    let url = "../modelo/jornada_bitacora.php?band=get_Actividades";
    if (object == null) {
        texto_Actividad = "";
    } else {
        texto_Actividad = object.value
    }
    let param = { texto_Actividad: texto_Actividad };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Actividad.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Actividad.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_Negocio(object) {
    let list_Negocio = document.getElementById("list_Negocio");
    let url = "../modelo/jornada_bitacora.php?band=get_Negocio";
    if (object == null) {
        texto_Negocio = "";
    } else {
        texto_Negocio = object.value
    }
    let param = { texto_Negocio: texto_Negocio };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Negocio.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Negocio.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}
function get_values(elements) {
    let result = {};
    for (x of elements) {
        let valor = '';
        if ($('#' + x.id).hasClass('textarea')) {
            valor = $('#' + x.id).html();
        } else if (x.list) {
            valor = get_data_id(x.id, x.list.id)
        } else {
            valor = x.value;
            if (x.type == "datetime-local") {
                valor = valor.replace("T", " ").substring(0, 16);
                valor += ":00";
            }
        }
        result[x.id] = valor;
    }
    return result;
}
function get_values_consulta(elements) {
    let result = {};
    for (x of elements) {
        let clave = x.id.replace("Consulta", "");
        let valor = '';
        if ($('#' + x.id).hasClass('textarea')) {
            valor = $('#' + x.id).html();
        } else if (x.list) {
            valor = get_data_id(x.id, x.list.id)
        } else {
            valor = x.value;
            if (x.type == "datetime-local") {
                valor = valor.replace("T", " ").substring(0, 16);
                valor += ":00";
            }
        }
        if (valor !== '' && valor !== null) {
            result[clave] = valor;
        }
    }
    return result;
}

async function save_jornada_bitacora(idBitacora = '') {
    let elements = $('.validate').filter(':not([disabled])');
    let usuario = consulta('idUsuario', 'list_Usuario');
    let ok = validate_elements(elements);
    let message_error = "Los campos no deben estar vacios";
    document.getElementById("idTiquete").value;
    let Hora_pendientes = $('#Hora_pendientes').val()
    let horas_distribuir = $('#horas_distribuir  ').val()
    let minutos_distribuir = $('#minutos_distribuir').val()
    if (minutos_distribuir < 10) {
        minutos_distribuir = '0' + minutos_distribuir
    }
    let d = Hora_pendientes.split(':')

    tiempopendiente = d[0] + d[1]
    tiemposolicitado = horas_distribuir + minutos_distribuir
    tiemposolicitado = parseInt(tiemposolicitado, 10)
    tiempopendiente = parseInt(tiempopendiente, 10)
    if (tiemposolicitado <= tiempopendiente) {
        if (ok) {
            let param = get_values(elements);
            param['idusuarioRegistra'] = id_usuario;
            param['id_Bitacora'] = idBitacora;
            let url = "../modelo/jornada_bitacora.php?band=save_Bitacora";

            let data = JSON.stringify(param);
            // console.log(data)
            try {
                let response = await sendRequest(url, data);
                //  console.log(response);
                var json_parse = JSON.parse(response);
                if (json_parse.message_error != '') {
                    alertify.error(json_parse.message_error);
                    if (json_parse.message_error == "La fecha debe ser mayor a la fecha Inicial") {
                        $("#" + document.getElementById("FechaInicial").id).prop("style", "border: 1px solid; border-color: red");
                        $("#" + document.getElementById("FechaInicioTabla").id).prop("style", "border: 1px solid; border-color: red");
                    } else {
                        $("#" + document.getElementById("FechaInicial").id).prop("style", "border: 1px solid; border-color: #ccc");
                        $("#" + document.getElementById("FechaInicioTabla").id).prop("style", "border: 1px solid; border-color: #ccc");
                    }
                }
                if (json_parse.response == 1) {
                    alertify.success("Se ha guardado exitosamente");
                    // pdf(json_parse);
                    $('#button').attr('onclick', 'save();');
                    $('#button').removeClass('btn-warning')
                    $('#buttonn').addClass('btn btn-primary');
                    $('#buttonn').text('Guardar');
                    document.getElementById("idActividad").value = "";
                    document.getElementById("idUnidadNegocio").value = "";
                    document.getElementById("Descripcion").value = "";
                    //  document.getElementById("FechaInicial").value="";
                    //  document.getElementById("FechaFinal").value="";
                    //  document.getElementById("idTiquete").value;
                    $('#ButtonCancelar').hide();
                    buscar_detalle_tiquete()  /// verificar si ya esta completado el tiquete  OJOJOJOJOJOJOJOJOJOJOJO
                    buscar_horas_pendientes(2);
                    table(object = 0);
                }
            } catch (error) {
                console.error(error);
            }
        }
        else {
            alertify.error(message_error);
        }
        table();
    }
    else
        alertify.error("El Tiempo Solicitado es mayor que el Pendiente a Distribuir..")

}

async function buscar_detalle_tiquete() {
    let idTiquete = document.getElementById("idTiquete").value;
    let usuario = consulta('idUsuario', 'list_Usuario');
    let data = JSON.stringify({ idTiquete: idTiquete, usuario: usuario });
    let url = "../modelo/jornada_bitacora.php?band=buscar_detalle_tiquete";
    let response = await sendRequest(url, data);
    //console.log(response)
    $('#div_detail_clasif').html(response);
}

function valida(op) {
    let horas_distribuir = document.getElementById("horas_distribuir").value;
    if (op == 'H') {
        let horas_distribuir = document.getElementById("horas_distribuir").value;
        if (horas_distribuir > 12) {
            alertify.warning("No puede ser mayor a 12 Horas")
            $('#horas_distribuir').val('');
        }
        if (horas_distribuir.includes('.')) {
            alert('Por favor, ingrese un número entero.');
            $('#horas_distribuir').val('');
        }
    } else {
        let minutos_distribuir = document.getElementById("minutos_distribuir").value;
        if (minutos_distribuir > 59) {
            alertify.warning("No puede ser mayor a 59 Minutos")
            $('#minutos_distribuir').val('');
        }
        if (minutos_distribuir.includes('.')) {
            alert('Por favor, ingrese un número entero.');
            $('#minutos_distribuir').val('');
        }
    }
}


async function save_sueldo(obj = '') {
    let elements = $('.sueldo');
    let ok = validate_elements(elements);
    let message_error = "Los campos no deben estar vacios";
    var ElemntUsuarioSueldo = document.getElementById("idUsuarioSueldo");
    var Elementlist_UsuarioSueldo = document.getElementById("list_UsuarioSueldo");
    var selectedValue = ElemntUsuarioSueldo.value;
    var selectedOption = [...Elementlist_UsuarioSueldo.options].find(option => option.value === selectedValue);
    var data_id = selectedOption ? selectedOption.getAttribute("data-id") : null;
    let Sueldo = document.getElementById("Sueldo").value;
    let FechaSueldo = document.getElementById("FechaSueldo").value;
    if (ok) {
        let param = { idUsuario: data_id, Sueldo: Sueldo, Fecha: FechaSueldo, idusuarioRegistra: id_usuario, idxid: obj };
        let url = "../modelo/jornada_bitacora.php?band=save_Sueldo";
        let data = JSON.stringify(param);
        try {
            let response = await sendRequest(url, data);
            // console.log(response);
            var json_parse = JSON.parse(response);
            if (json_parse.response == 1) {
                alertify.success("Se ha guardado exitosamente");
                table2();
                $('#idButton').attr('onclick', 'save_sueldo();');
                $('#idButton').removeClass('btn-warning')
                $('#idButton').addClass('btn btn-primary');
                $('#idButton').text('Guardar');
                $("#" + document.getElementById("FechaSueldo").id).prop("style", "border: 1px solid; border-color: #ccc");
                document.getElementById("FechaSueldo").value = "";
                document.getElementById("Sueldo").value = "";
                $('#idButtonCancelar').hide();
                table2();
            }
            else {
                alertify.error(json_parse.message_error);
            }
        } catch (error) {
            console.error(error);
        }
    } else {
        alertify.error(message_error);
    }
}
function edit_button(obj) {
    $('#button').removeClass('btn-primary')
    $('#button').addClass('btn btn-warning');
    $('#button').text('Editar');
    $('#button').attr('onclick', 'save(\'' + obj.idBitacora + '\');');
    $('#ButtonCancelar').show();
    Object.keys(obj).forEach(function (propiedad) {
        if (propiedad != 'idCentroTrabajo' && propiedad != 'idBitacora' && propiedad != 'usuarioRegistra') {
            var valor = obj[propiedad];
            let elemento = document.getElementById(propiedad)
            elemento.value = valor;

            if (propiedad === 'idUsuario') {
                list_Tiquete_Registro(obj.idTiquete)
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var opciones = document.querySelectorAll('.opciones');
    opciones.forEach(function (opcion) {
        opcion.addEventListener('click', function () {
            var target = this.getAttribute('data-target');
            var collapsibles = document.querySelectorAll('.collapse');
            collapsibles.forEach(function (collapsible) {
                if (collapsible.id !== target) {
                    $(collapsible).collapse('hide'); // Cierra todos los colapsables excepto el objetivo
                }
            });
        });
    });
});

function edit_button_usuario(obj) {
    $('#idButtonCancelar').show();
    $('#idButton').removeClass('btn-primary')
    $('#idButton').addClass('btn btn-warning');
    $('#idButton').text('Editar');
    $('#idButton').attr('onclick', 'save_sueldo(\'' + obj.idxid + '\', \'' + obj.FechaSueldo + '\');');
    Object.keys(obj).forEach(function (propiedad) {
        if (propiedad != 'idxid' && propiedad != 'NombreUsuarioLargo' && propiedad != 'idUsuarioSueldo') {
            var valor = obj[propiedad];
            document.getElementById(propiedad).value = valor;
        }
    });
}

async function table(object = 0) {
    $("#" + document.getElementById("idUsuario").id).prop("style", "border: 1px solid; border-color: #ccc");
    var id = get_data_id("idUsuario", "list_Usuario")
    var val = document.getElementById("idUsuario").value;

    if (val.toLowerCase() === 'todos' || val.toLowerCase() === 'todo') {
        id = '';
    } else if (id == null) {
        id = '1';
    }

    var param = { id: id, usuario: id_usuario };
    try {
        let data = JSON.stringify(param);
        let url = "../modelo/jornada_bitacora.php?band=get_Bitacora";
        let response = await sendRequest(url, data);
        if (object == 0) {
            $('#div_tabla').html(response);
        } else {
            $('#idtbody').html(response);
        }
        // }
        if (object == 0) {
            datatable('id_Table');

            var table = $('#idTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'excel', 'print'
                ]
            });
        }
        var table = $('#idTable').DataTable()
        table.settings({
            searching: false
        });
        table.draw();
    } catch (error) {
        console.log(error);
    }
}

async function table2(object = 0) {
    $("#" + document.getElementById("idUsuarioSueldo").id).prop("style", "border: 1px solid; border-color: #ccc");
    var id = get_data_id("idUsuarioSueldo", "list_UsuarioSueldo")
    var val = document.getElementById("idUsuarioSueldo").value;

    if (val.toLowerCase() === 'todos' || val.toLowerCase() === 'todo') {
        id = '';
    } else if (id == null) {
        id = '1';
    }
    var param = { id: id };
    try {
        let data = JSON.stringify(param);
        let url = "../modelo/jornada_bitacora.php?band=table_usuarioSueldo";
        let response = await sendRequest(url, data);
        if (response == 2) {
            alertify.error('Debe seleccionar un Usuario')
            $("#" + document.getElementById("idUsuarioSueldo").id).prop("style", "border: 1px solid; border-color: red");
        } else {
            if (object == 0) {
                $('#div_tabla2').html(response);
            } else {
                $('#idtbody2').html(response);
            }
        }
        if (object == 0) {
            datatable('idTable_usuarioSueldo');
        }
    } catch (error) {
        console.log(error);
    }
}

async function delete_button(obj) {
    let confirmacion = await confirmarEliminacion();
    if (confirmacion) {

        var param = { idBitacora: obj };
        try {
            let data = JSON.stringify(param);
            let url = "../modelo/jornada_bitacora.php?band=delete_Bitacora";
            let response = await sendRequest(url, data);
            //console.log(response)
            if (response == 1) {
                alertify.success("Se ha eliminado exitosamente");
                table();
                $('#button').attr('onclick', 'save();');
                $('#button').removeClass('btn-warning')
                $('#button').addClass('btn btn-primary');
                $('#button').text('Guardar');
                list_Tiquete_Registro()
                table();
            } else {
                alertify.error("No se logro eliminar el registro");
            }
        } catch (error) {
            console.log(error);
        }
    }
}

function confirmarEliminacion() {
    return new Promise((resolve) => {
        alertify.confirm("¿Estás seguro de que deseas eliminar este registro?", function (e) {
            resolve(e);
        });
    });
}

async function delete_usuario_button(obj) {
    var confirmacion = confirm("¿Estás seguro de que deseas eliminar este registro?");
    if (confirmacion) {
        var param = { idxid: obj };
        try {
            let data = JSON.stringify(param);
            let url = "../modelo/jornada_bitacora.php?band=delete_Usuario";
            let response = await sendRequest(url, data);
            //console.log(response);
            if (response == 1) {
                alertify.success("Se ha eliminado exitosamente");
                table2();
                $('#idButton').attr('onclick', 'save_sueldo();');
                $('#idButton').removeClass('btn-warning')
                $('#idButton').addClass('btn btn-primary');
                $('#idButton').text('Guardar');
            } else {
                alertify.error("No se logro eliminar el registro");
            }
        } catch (error) {
            console.log(error);
        }
        alert("Registro eliminado");
    } else {
        alert("Eliminación cancelada");
    }
}

function si(object) {
    //     let elements = $('.consulta');
    //     var elemento = '';
    //     for (x of elements) {
    //         elemento = document.getElementById(x.id).value;
    //     }
    //     if(elemento == '') {
    //     list_UsuarioConsulta('');
    //     list_NegocioConsulta('');
    //     list_ActividadConsulta('');
    //     list_CentroConsulta('');
    // }
}

async function list_NegocioConsulta(object) {
    // $('#list_NegocioConsulta').html('');
    let elements = $('.consulta').not('#idUnidadNegocioConsulta')
    let registro = get_values_consulta(elements);
    let list_NegocioConsulta = document.getElementById("list_NegocioConsulta");
    let url = "../modelo/jornada_bitacora.php?band=get_NegocioConsulta";
    let param = registro;
    param['texto_NegocioConsulta'] = object.value;
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_NegocioConsulta.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_NegocioConsulta.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_empresa(object) {
    let list_empresa = document.getElementById("list_empresa");
    let url = "../modelo/jornada_bitacora.php?band=get_Empresa";
    texto_empresa = object.value
    let param = { texto_empresa: texto_empresa };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_empresa.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_empresa.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_UsuarioConsulta_extra(object) {
    let list_Usuario = document.getElementById("list_UsuarioConsulta_extras");
    let url = "../modelo/jornada_bitacora.php?band=get_Usuarios";
    texto_Usuario = object.value
    let param = { texto_Usuario: texto_Usuario };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Usuario.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Usuario.appendChild(nuevaOpcion);

            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_UsuarioConsulta(object) {
    let list_Usuario = document.getElementById("list_UsuarioConsulta");
    let url = "../modelo/jornada_bitacora.php?band=get_Usuarios";
    texto_Usuario = object.value
    let param = { texto_Usuario: texto_Usuario };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Usuario.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Usuario.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_UsuarioConsulta_actividad(object) {
    let list_Usuario = document.getElementById("list_UsuarioConsulta_actividad");
    let url = "../modelo/jornada_bitacora.php?band=list_UsuarioConsulta_actividad";
    texto_Usuario = object.value
    let param = { texto_Usuario: texto_Usuario };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Usuario.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Usuario.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_ActividadConsulta(object) {
    // $('#list_ActividadConsulta').html('');
    let elements = $('.consulta').not('#idActividadConsulta')
    let registro = get_values_consulta(elements);
    let list_ActividadConsulta = document.getElementById("list_ActividadConsulta");
    let url = "../modelo/jornada_bitacora.php?band=get_ActividadesConsulta";
    let param = registro;
    param['texto_ActividadConsulta'] = object.value;
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_ActividadConsulta.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_ActividadConsulta.appendChild(nuevaOpcion);
            }
            // list_UsuarioConsulta("");
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_CentroConsulta(object) {
    // $('#list_CentroConsulta').html('');
    let elements = $('.consulta').not('#idCentroTrabajoConsulta')
    let registro = get_values_consulta(elements);
    let list_CentroConsulta = document.getElementById("list_CentroConsulta");
    let url = "../modelo/jornada_bitacora.php?band=get_dispositivos";
    let param = registro;
    param['texto_CentroConsulta'] = object.value;
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_CentroConsulta.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_CentroConsulta.appendChild(nuevaOpcion);
            }
            // list_UsuarioConsulta("");
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_CentroConsulta_actividad(object) {
    // $('#list_CentroConsulta').html('');
    let elements = $('.consulta').not('#idCentroTrabajoConsulta')
    let registro = get_values_consulta(elements);
    let list_CentroConsulta = document.getElementById("list_CentroConsulta_actividad");
    let url = "../modelo/jornada_bitacora.php?band=get_CentrosDeTrabajoConsulta";
    let param = registro;
    param['texto_CentroConsulta'] = object.value;
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_CentroConsulta.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_CentroConsulta.appendChild(nuevaOpcion);
            }
            // list_UsuarioConsulta("");
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_CentroConsulta_b(object) {
    // $('#list_CentroConsulta').html('');
    let elements = $('.consulta').not('#idCentroTrabajoConsulta')
    let registro = get_values_consulta(elements);
    let list_CentroConsulta_b = document.getElementById("list_CentroConsulta_b");
    let url = "../modelo/jornada_bitacora.php?band=get_CentrosDeTrabajoConsulta";
    let param = registro;
    param['texto_CentroConsulta'] = object.value;
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_CentroConsulta_b.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_CentroConsulta_b.appendChild(nuevaOpcion);
            }
            // list_UsuarioConsulta("");
        })
    } catch (error) {
        console.log(error);
    }
}

async function list_Actividad_turno(object) {
    let list_Actividad = document.getElementById("list_Actividad_turno");
    let url = "../modelo/jornada_bitacora.php?band=get_Actividades";
    if (object == null) {
        texto_Actividad = "";
    } else {
        texto_Actividad = object.value
    }
    let param = { texto_Actividad: texto_Actividad };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);
        json_parse = JSON.parse(response)
        json_parse.forEach((json) => {
            let id = json['id'];
            let name = json['name'];
            if (!list_Actividad.querySelector(`option[value="${name}"]`)) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = name;
                nuevaOpcion.setAttribute('data-id', id); // Agrega el atributo data-id
                list_Actividad.appendChild(nuevaOpcion);
            }
        })
    } catch (error) {
        console.log(error);
    }
}



async function get_asignar_turno() {
    let FechaInicial_turno = document.getElementById("fecha_ini").value;
    let FechaFinal_turno = document.getElementById("fecha_fin").value;
    let lista_turnos = document.getElementById("lista_turnos_a").value;
    var dias_laborales = $('#dias_laborales').val();
    let usuario = consulta('idUsuario_asignar', 'list_Usuario_asignar');
    let idcentroTrabajo = consulta('CentroTrabajo', 'list_Centrotrabajo');
    let bandera = 0
    if (lista_turnos == 0 || dias_laborales == '' || FechaInicial_turno == '' || usuario == '')
        bandera = 1;

    if (FechaFinal_turno != '') {
        if (FechaInicial_turno > FechaFinal_turno)
            bandera = 1;
    }
    if (bandera == 0) {
        let url = "../modelo/jornada_bitacora.php?band=get_asignar_turno";
        let param = { FechaInicial_turno: FechaInicial_turno, FechaFinal_turno: FechaFinal_turno, lista_turnos: lista_turnos, dias_laborales: dias_laborales, usuario: usuario, idcentroTrabajo: idcentroTrabajo, id_usuario: id_usuario };
        let data = JSON.stringify(param);
        try {
            let response = await sendRequest(url, data);
            //console.log(response);  
            if (response != 1) {
                alertify.success('Turno Asignado.')

            } else {
                alertify.error('Horas Invalidas..')
            }

        } catch (error) {
            console.log(error);
        }
    } else {
        alertify.error('Verifique datos...')
    }
    Buscar_detalle_t();
    buscar_asignados();
    get_turnos_user_activo();
}

function cargar_turnos(op) {
    let xhr = new XMLHttpRequest();
    let url = "../modelo/jornada_bitacora.php?band=cargar_turnos&op=" + op;
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-Type", 'application/json; charset=utf-8');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // console.log(this.responseText)
            let datos = JSON.parse(this.responseText);
            $('#lista_turnos').find('option').remove();
            $('#lista_turnos_a').find('option').remove();
            let opcion = '';
            if (op == 0)
                opcion += '<option value="1" >Turno Nuevo</option>';
            else
                opcion += '<option value="0" selected disabled>Seleccione</option>';
            $.each(datos, function (key, val) {
                opcion += '<option value="' + val.id + '">' + val.name + '</option>';
            });
            if (op == 0)
                $('#lista_turnos').append(opcion);
            else
                $('#lista_turnos_a').append(opcion);
        }// opcion = '<option value=0 disabled selected>Seleccione</option>';
    }
    xhr.send('');
}

/*==============================================
    💼 MÓDULO DE GESTIÓN DE TURNOS 💼
================================================


/*==============================================
    🕕 SUBMÓDULO DE TURNOS 🕕
================================================
    ✨ Funcionalidades principales:
    
    ⏰ Visualización de turnos existentes
    🗑️ Eliminación de turnos
    ✏️ Edición y actualización de turnos
    ➕ Creación de nuevos turnos
================================================*/

function cargar_turnosAll() {
    fetch('../modelo/jornada_bitacora.php?band=cargar_turnos')
        .then(response => response.json())
        .then(data => {
            let tablaTurnos = document.getElementById('div_tabla_turnos');
            let html = '<table class="table table-hover table-condensed table-bordered table-striped" style="margin: 15px;">';
            html += '<thead><tr>' +
                '<th class="text-center">Descripción</th>' +
                '<th class="text-center">Hora Inicio</th>' +
                '<th class="text-center">Hora Fin</th>' +
                '<th class="text-center">Duración</th>' +
                '<th class="text-center">Acciones</th>' +
                '</tr></thead><tbody>';

            data.forEach(turno => {
                html += `<tr>
                    <td>${turno.name}</td>
                    <td class="text-center">${turno.horaInicio || '--:--'}</td>
                    <td class="text-center">${turno.horaFin || '--:--'}</td>
                    <td class="text-center">${turno.duracion || '--:--'}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-primary" onclick="editarTurno('${turno.id}')"><i class="glyphicon glyphicon-pencil"></i></button>
                        <button class="btn btn-xs btn-danger" onclick="eliminarTurno('${turno.id}')"><i class="glyphicon glyphicon-trash"></i></button>
                    </td>
                </tr>`;
            });

            html += '</tbody></table>';
            tablaTurnos.innerHTML = html;
        })
        .catch(error => console.error('Error:', error));
}


// Función para alternar entre descripción manual y automática
function toggleDescripcionMode() {
    let checkboxAuto = document.getElementById('descripcion_auto');
    let inputDescripcion = document.getElementById('Nombre_turno');
    let helpText = document.getElementById('descripcion_help');

    if (checkboxAuto.checked) {
        // Modo automático
        inputDescripcion.setAttribute('readonly', true);
        helpText.textContent = 'El nombre se generará al introducir las horas';
        generarDescripcionAuto();
    } else {
        // Modo manual
        inputDescripcion.removeAttribute('readonly');
        inputDescripcion.value = '';
        helpText.textContent = 'Introduce manualmente el nombre del turno';
    }
}

// Función para generar descripción automática del turno
function generarDescripcionAuto() {
    let checkboxAuto = document.getElementById('descripcion_auto');
    if (!checkboxAuto.checked) return;

    let horaInicio = document.getElementById('FechaInicial_turno').value;
    let horaFin = document.getElementById('FechaFinal_turno').value;

    if (horaInicio && horaFin) {
        // Generar descripción usando formato de 24 horas
        let descripcion = `Turno ( ${horaInicio} - ${horaFin} )`;
        document.getElementById('Nombre_turno').value = descripcion;

    }
}

// Función para calcular la duración entre dos horas
function calcularDuracion(horaInicio, horaFin) {
    if (!horaInicio || !horaFin) return false;

    // Convertir a minutos para calcular diferencia
    let [h1, m1] = horaInicio.split(':').map(Number);
    let [h2, m2] = horaFin.split(':').map(Number);

    let minInicio = h1 * 60 + m1;
    let minFin = h2 * 60 + m2;

    // Si hora fin es menor, asumimos que cruza la medianoche
    if (minFin < minInicio) {
        minFin += 24 * 60; // Añadimos 24 horas
    }

    let difMinutos = minFin - minInicio;
    let horas = Math.floor(difMinutos / 60);
    let minutos = difMinutos % 60;

    // Validar que haya al menos 8 horas entre inicio y fin
    const MINIMO_HORAS = 8;
    const MINIMO_MINUTOS = MINIMO_HORAS * 60;

    if (difMinutos < MINIMO_MINUTOS) {
        alertify.error(`Error: El turno debe tener una duración mínima de ${MINIMO_HORAS} horas.`);
        // Resaltar los campos de hora con borde rojo
        document.getElementById('FechaInicial_turno').style.borderColor = 'red';
        document.getElementById('FechaFinal_turno').style.borderColor = 'red';
        // No actualizar el campo de duración si no cumple con el mínimo
        return false;
    } else {
        // Restablecer el color del borde si es válido
        document.getElementById('FechaInicial_turno').style.borderColor = '#ccc';
        document.getElementById('FechaFinal_turno').style.borderColor = '#ccc';
    }

    // Formatear para el campo duración - siempre actualizar esto si pasa la validación
    document.getElementById('Duracion_turno').value =
        `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;

    // Generar descripción automática solo si el checkbox está marcado
    if (document.getElementById('descripcion_auto')?.checked) {
        generarDescripcionAuto();
    }

    return true;
}
// Funciones para las acciones
async function get_crear_turno() {
    let FechaInicial_turno = document.getElementById("FechaInicial_turno").value;
    let FechaFinal_turno = document.getElementById("FechaFinal_turno").value;
    let Nombre_turno = document.getElementById("Nombre_turno").value;

    // Validar que todos los campos estén llenos
    if (!FechaInicial_turno || !FechaFinal_turno || !Nombre_turno) {
        alertify.error("Todos los campos son obligatorios");
        return;
    }

    // Validar la duración mínima
    if (!calcularDuracion(FechaInicial_turno, FechaFinal_turno)) {
        return; // Si no cumple con la duración mínima, detener la ejecución
    }

    let Duracion_turno = document.getElementById("Duracion_turno").value;

    let url = "../modelo/jornada_bitacora.php?band=save_turno";
    let param = {
        FechaInicial_turno: FechaInicial_turno,
        FechaFinal_turno: FechaFinal_turno,
        Nombre_turno: Nombre_turno,
        Duracion_turno: Duracion_turno,
        idusuario: id_usuario
    };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        // console.log(response);  
        if (response == 0) {
            alertify.success('Turno creado correctamente');
            cargar_turnosAll();
            document.getElementById('Nombre_turno').value = '';
            document.getElementById('FechaInicial_turno').value = '';
            document.getElementById('FechaFinal_turno').value = '';
            document.getElementById('Duracion_turno').value = '';
        } else {
            alertify.error('Hubo un error al crear el turno');
        }

    } catch (error) {
        console.log(error);
        alertify.error('Error al crear el turno');
    }

}

function editarTurno(idTurno) {
    // Buscar el turno en la lista de turnos
    fetch(`../modelo/jornada_bitacora.php?band=get_turno&idTurno=${idTurno}`)
        .then(response => response.json())
        .then(turno => {
            // Cargar datos en el formulario
            document.getElementById('Nombre_turno').value = turno.name;
            document.getElementById('FechaInicial_turno').value = turno.horaInicio;
            document.getElementById('FechaFinal_turno').value = turno.horaFin;
            document.getElementById('Duracion_turno').value = turno.duracion;

            // Cambiar el botón para editar
            let botonCrear = document.getElementById('button_crear_t');
            botonCrear.innerHTML = 'Actualizar Turno';
            botonCrear.onclick = function () {
                actualizarTurno(idTurno);
            };
        })
        .catch(error => console.error('Error:', error));
}

function eliminarTurno(idTurno) {
    if (confirm('¿Está seguro que desea eliminar este turno?')) {
        fetch(`../modelo/jornada_bitacora.php?band=eliminar_turno&idTurno=${idTurno}`, {
            method: 'POST'
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alertify.success('Turno eliminado correctamente');
                    cargar_turnosAll(); // Recargar la tabla
                } else {
                    alertify.error('No se pudo eliminar el turno: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertify.error('Error al eliminar el turno');
            });
    }
}

/*==============================================
    🕕 SUBMÓDULO ASIGNAR TURNOS 🕕
================================================
    ✨ Funcionalidades principales:
    
    🔄 Búsqueda de usuarios
    📝 Asignación de turnos a múltiples usuarios

================================================*/

// Variable global para almacenar IDs de usuarios seleccionados
let usuariosSeleccionados = new Set();

// Función mejorada para buscar usuarios preservando las selecciones
function get_Usuarios(texto = '') {
    let param = { texto: texto };
    let data = JSON.stringify(param);
    fetch('../modelo/jornada_bitacora.php?band=get_Usuarios', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: data
    })
        .then(response => response.json())
        .then(data => {
            let tablaUsuarios = document.getElementById('tabla_usuarios_multiple');
            let html = '';

            if (data.length === 0) {
                html = '<tr><td colspan="5" class="text-center">No se encontraron usuarios</td></tr>';
            } else {
                data.forEach(usuario => {
                    // Verificar si el usuario ya estaba seleccionado previamente
                    const estaSeleccionado = usuariosSeleccionados.has(usuario.id);

                    html += `<tr>
                            <td class="text-center">
                                <input type="checkbox" name="usuarios[]" value="${usuario.id}" 
                                    onchange="actualizarSeleccionUsuario(this)" 
                                    ${estaSeleccionado ? 'checked' : ''}>
                            </td>
                            <td>${usuario.nombre}</td>
                            <td>${usuario.cedula}</td>
                            <td>${usuario.cargo || 'No especificado'}</td>
                        </tr>`;
                });
            }

            tablaUsuarios.innerHTML = html;
            actualizarConteo(); // Actualizar el contador

            // Actualizar estado del checkbox "seleccionar todos"
            actualizarEstadoSeleccionarTodos();
        })
        .catch(error => console.error('Error:', error));
}

// Nueva función para actualizar el Set de usuarios seleccionados
function actualizarSeleccionUsuario(checkbox) {
    if (checkbox.checked) {
        usuariosSeleccionados.add(checkbox.value);
    } else {
        usuariosSeleccionados.delete(checkbox.value);
    }
    actualizarConteo();
}

// Función modificada para seleccionar/deseleccionar todos
function seleccionarTodos() {
    let checkboxes = document.getElementsByName('usuarios[]');
    let seleccionarTodos = document.getElementById('seleccionar_todos').checked;

    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = seleccionarTodos;

        if (seleccionarTodos) {
            usuariosSeleccionados.add(checkboxes[i].value);
        } else {
            usuariosSeleccionados.delete(checkboxes[i].value);
        }
    }

    actualizarConteo();
}

// Función para actualizar el estado del checkbox "seleccionar todos"
function actualizarEstadoSeleccionarTodos() {
    let checkboxes = document.getElementsByName('usuarios[]');
    let todosSeleccionados = true;
    let ningunSeleccionado = true;

    for (let i = 0; i < checkboxes.length; i++) {
        if (!checkboxes[i].checked) {
            todosSeleccionados = false;
        } else {
            ningunSeleccionado = false;
        }
    }

    // Actualizar el checkbox "seleccionar todos"
    document.getElementById('seleccionar_todos').checked = todosSeleccionados;
    document.getElementById('seleccionar_todos').indeterminate = !todosSeleccionados && !ningunSeleccionado;
}

// Función modificada para actualizar el conteo
function actualizarConteo() {
    document.getElementById('conteo_seleccionados').innerText = usuariosSeleccionados.size;
}

// Función modificada para asignar turno a múltiples usuarios
function asignarTurnoMultiple() {
    if (usuariosSeleccionados.size === 0) {
        alertify.error('Debe seleccionar al menos un usuario');
        return;
    }

    let idTurno = document.getElementById('lista_turnos_a').value;
    let fechaInicio = document.getElementById('fecha_ini').value;
    let fechaFin = document.getElementById('fecha_fin').value;

    if (!idTurno || !fechaInicio || !fechaFin) {
        alertify.error('Debe seleccionar un turno y fechas de inicio y fin');
        return;
    }

    let diasLaborales = [];
    let diasSeleccionados = $('#dias_laborales').multipleSelect('getSelects');
    if (diasSeleccionados && diasSeleccionados.length > 0) {
        diasLaborales = diasSeleccionados;
    }

    // Enviar datos para asignación múltiple
    fetch('../modelo/jornada_bitacora.php?band=asignar_turno_multiple', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            idTurno: idTurno,
            fechaInicio: fechaInicio,
            fechaFin: fechaFin,
            usuarios: Array.from(usuariosSeleccionados),
            diasLaborales: diasLaborales
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertify.success('Turnos asignados correctamente a ' + usuariosSeleccionados.size + ' usuarios');
                // Limpiar selecciones después de asignar correctamente
                usuariosSeleccionados.clear();
                get_Usuarios('');
            } else {
                alertify.error(data.message || 'Error al asignar turnos');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertify.error('Error al asignar turnos');
        });
}

// Agregar función para limpiar selecciones (botón opcional)
function limpiarSelecciones() {
    usuariosSeleccionados.clear();
    let checkboxes = document.getElementsByName('usuarios[]');
    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = false;
    }
    document.getElementById('seleccionar_todos').checked = false;
    actualizarConteo();
}





async function delete_Turnos_asignado(id) {
    let iduser = consulta('idUsuario_asignar', 'list_Usuario_asignar');

    let param = { id: id, iduser: iduser };
    let data = JSON.stringify(param);
    let url = "../modelo/jornada_bitacora.php?band=delete_Turnos_asignado";
    let response = await sendRequest(url, data);
    console.log(response)
    alertify.success('Turno Eliminado..')
    get_turnos_user_activo();
}

async function Buscar_detalle_t(object) {
    let turno = document.getElementById("lista_turnos").value;
    $('#lista_actividades').find('option').remove();
    let opcion = '';
    $('#div_tabla_turnos').html('');
    var uno = document.getElementById('button_crear_t');
    if (turno == 1) {
        opcion += '<option value="47C297B3-411E-445E-8357-797687831DC2" disabled selected>Jornada Laboral</option>';
        uno.innerHTML = 'Crear Turno';
        $('#div_tabla_turnos').html('');
        $('#FechaInicial_turno').prop('disabled', false)
        $('#FechaFinal_turno').prop('disabled', false)
        $('#lista_actividades').prop('disabled', false)
        $('#button_crear_t').prop('disabled', false)

    } else {
        opcion += '<option value="1AC30E97-8F11-47E2-A5EA-96BBEEA575AC" disabled selected>Descanso</option>';
        uno.innerHTML = 'Agregar Actividad';
        let param = { turno: turno, op: 1 };
        let data = JSON.stringify(param);
        let url = "../modelo/jornada_bitacora.php?band=buscar_detalle_t";
        let response = await sendRequest(url, data);
        //console.log(response)
        d = response.split('||');
        $('#div_tabla_turnos').html(d[0]);
        if (d[1] == 1) {
            $('#FechaInicial_turno').prop('disabled', true)
            $('#FechaFinal_turno').prop('disabled', true)
            $('#lista_actividades').prop('disabled', true)
            $('#button_crear_t').prop('disabled', true)
            alertify.warning('Turno con Usuarios Asignados.. No se puede Modificar')
        } else {
            $('#FechaInicial_turno').prop('disabled', false)
            $('#FechaFinal_turno').prop('disabled', false)
            $('#lista_actividades').prop('disabled', false)
            $('#button_crear_t').prop('disabled', false)
        }
    }
    $('#lista_actividades').append(opcion);
}

async function Buscar_detalle_tt(object) {
    let turno = document.getElementById("lista_turnos_a").value;
    let opcion = '';
    $('#div_tabla_turnos_asignar').html('');
    opcion += '<option value="1AC30E97-8F11-47E2-A5EA-96BBEEA575AC" disabled selected>Descanso</option>';
    let param = { turno: turno, op: 0 };
    let data = JSON.stringify(param);
    let url = "../modelo/jornada_bitacora.php?band=buscar_detalle_t";
    let response = await sendRequest(url, data);
    //console.log(response)
    d = response.split('||');
    $('#div_tabla_turnos_asignar').html(d[0]);
}

async function buscar_asignados(object) {
    let turno = document.getElementById("lista_turnos_a").value;
    var texto = $('#lista_turnos_a').find('option:selected').text();
    let opcion = '';
    $('#div_tabla_asignados').html('');
    //opcion+= '<option value="1AC30E97-8F11-47E2-A5EA-96BBEEA575AC" disabled selected>Descanso</option>';
    let param = { turno: turno, texto: texto };
    let data = JSON.stringify(param);
    let url = "../modelo/jornada_bitacora.php?band=buscar_asignados";
    let response = await sendRequest(url, data);
    //console.log(response)
    $('#div_tabla_asignados').html(response);
    $('#idTabledetalle_asignados').DataTable()
}

async function Buscar_actividad(object) {
    $('#lista_actividades').find('option').remove();
    let opcion = '';
    var uno = document.getElementById('button_crear_t');
    opcion += '<option value="47C297B3-411E-445E-8357-797687831DC2" disabled selected>Jornada Laboral</option>';
    uno.innerHTML = 'Crear Turno';
    $('#div_tabla_turnos').html('');
    $('#lista_actividades').append(opcion);
}

async function get_Consulta(object = 0) {
    let elements = $('.consultaBit_actividad');
    let ok = validate_elements(elements);
    let FechaI = document.getElementById("FechaInicialConsulta").value;
    let FechaF = document.getElementById("FechaFinalConsulta").value;
    let param = get_values_consulta(elements);
    param['FechaInicial'] = FechaI;
    param['FechaFinal'] = FechaF;
    param['idMiUsuario'] = id_usuario;
    //console.log(elements,param)
    if (ok) {
        try {
            let data = JSON.stringify(param);
            let url = "../modelo/jornada_bitacora.php?band=get_Consulta";
            let response = await sendRequest(url, data);
            if (response == 2) {
                alertify.error('Debe seleccionar un Usuario')
                $("#" + document.getElementById("idUsuarioSueldo").id).prop("style", "border: 1px solid; border-color: red");
            } else {
                if (object == 0) {
                    $('#div_tabla3').html(response);
                    console.log('if')
                } else {
                    $('#idtbody2').html(response);
                    console.log('else')
                }
            }
            if (object == 0) {
                datatable('idTable_consulta');
                var table = $('#idTableConsulta').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'excel', 'print'
                    ]
                });
            }
        } catch (error) {
            console.log(error);
        }
    } else
        alertify.error('Complete los campos..')
}

async function get_Consulta_gral(object = 0) {
    let elements = $('.consultaBitacora');
    let FechaI = document.getElementById("FechaIni").value;
    let FechaF = document.getElementById("FechaFinal").value;
    var idempresa = get_data_id("idempresa", "list_empresa")
    var list_CentroConsulta = get_data_id("idcentroTrabajo", "list_CentroConsulta")
    var list_UsuarioConsulta = get_data_id("idUsuarioConsulta", "list_UsuarioConsulta")
    if (FechaF != '' && FechaI != '') {
        let param = get_values_consulta(elements);
        param['FechaInicial'] = FechaI;
        param['FechaFinal'] = FechaF;
        param['list_empresa'] = idempresa;
        param['list_CentroConsulta'] = list_CentroConsulta;
        param['list_UsuarioConsulta'] = list_UsuarioConsulta;
        try {
            // console.log(param)
            let data = JSON.stringify(param);
            let url = "../modelo/jornada_bitacora.php?band=get_Consulta_gral";
            let response = await sendRequest(url, data);
            //console.log(response)
            if (response == 2) {
                alertify.error('Debe seleccionar un Usuario')
                $("#" + document.getElementById("idUsuarioSueldo").id).prop("style", "border: 1px solid; border-color: red");
            } else {
                if (object == 0) {
                    $('#div_tabla4').html(response);
                    //  console.log('if')
                } else {
                    $('#idtbody2').html(response);
                    //console.log('else')
                }
            }
            if (object == 0) {
                datatable('idTable_consulta');
                var table = $('#idTableConsulta').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'excel', 'print'
                    ]
                });
            }
        } catch (error) {
            console.log(error);
        }
    } else {
        alertify.error('Digite Fechas')
    }
}

async function list_Tiquete_Registro(seleccionado) {
    let usuario = consulta('idUsuario', 'list_Usuario');
    let xhr = new XMLHttpRequest();
    let url = "../modelo/jornada_bitacora.php?band=obtener_tiquete&idUsuario=" + usuario;
    xhr.open("POST", url, true);
    // console.log(url)
    xhr.setRequestHeader("Content-Type", 'application/json; charset=utf-8');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // console.log(this.responseText)
            let datos = JSON.parse(this.responseText);

            $('#idTiquete').find('option').remove();
            let opcion = '<option value=0 disabled selected>Seleccione</option>';
            $.each(datos, function (key, val) {

                if (val.id == seleccionado && seleccionado != undefined) {
                    opcion += '<option value="' + val.id + '" selected>' + val.name + '</option>';
                } else
                    opcion += '<option value="' + val.id + '">' + val.name + '</option>';
            });
            // opcion = '<option value=-1>Nuevo</option>';
            $('#idTiquete').append(opcion);
        }
    }
    xhr.send('');
}

async function buscar_horas() {
    let idTiquete = document.getElementById("idTiquete").value;
    let usuario = consulta('idUsuario', 'list_Usuario');
    let data = JSON.stringify({ idTiquete: idTiquete, usuario: usuario });
    let url = "../modelo/jornada_bitacora.php?band=buscar_horas";
    let response = await sendRequest(url, data);
    let hora = (response);
    console.log(response)
    $('#Hora').val(hora);
    $('#Hora').prop('disabled', true);
    var x = document.getElementById("div_actividad");
    if (hora == 0)
        x.style.display = "none";
    else
        x.style.display = "block";
}

async function buscar_horas_pendientes(op) {
    let idTiquete = document.getElementById("idTiquete").value;
    let usuario = consulta('idUsuario', 'list_Usuario');
    let data = JSON.stringify({ idTiquete: idTiquete, usuario: usuario });
    let url = "../modelo/jornada_bitacora.php?band=buscar_horas_pendientes";
    let response = await sendRequest(url, data);
    let hora = (response);
    //console.log('a'+response+'a')       
    $('#Hora_pendientes').val(hora);
    $('#Hora_pendientes').prop('disabled', true);
    if (op == 2) {
        if (hora == '00:00') {
            list_Tiquete_Registro();
            buscar_horas();
            buscar_detalle();
            buscar_detalle_tiquete();
            buscar_horas_pendientes(2);
            $('#horas_distribuir').val('');
            $('#minutos_distribuir').val('');
        }
    }
}

async function buscar_detalle() {
    let idTiquete = document.getElementById("idTiquete").value;
    let d = idTiquete.split('-')
    let año = d[0];
    let tiquete = d[1];

    let usuario = consulta('idUsuario', 'list_Usuario');
    let data = JSON.stringify({ tiquete: tiquete, año: año, usuario: usuario });
    let url = "../modelo/jornada_bitacora.php?band=buscar_detalle";
    let response = await sendRequest(url, data);
    //console.log(response)
    $('#div_detalle_tiempos').html(response);
}

function mostrarNumero() {
    let selectElement = document.getElementById('idTiquete');
    let numero = 123; // Cambia esto al número que deseas mostrar
    let option = new Option(numero, numero);
    selectElement.appendChild(option);
    //console.log(option)
}

function consulta(elemento, datalist) {
    // Obtener el elemento <input> y el elemento <datalist>
    var inputElement = document.getElementById(elemento);
    var dataListElement = document.getElementById(datalist);
    // Agregar un evento "change" al <input> para detectar cambios en la selección
    // Obtener el valor del <input>
    var selectedValue = inputElement.value;
    // Buscar la opción seleccionada en el <datalist> por su valor
    var selectedOption = [...dataListElement.options].find(option => option.value === selectedValue);
    // Obtener el valor del atributo data-id de la opción seleccionada
    var dataIdValue = selectedOption ? selectedOption.getAttribute("data-id") : null;
    // Mostrar el valor de data-id en la consola
    // console.log("data-id:", dataIdValue);
    return dataIdValue;
}

async function get_horasExtras(object = 0) {
    // let elements = $('.consulta');
    let FechaI = document.getElementById("FechaInicialConsulta_extra").value;
    let FechaF = document.getElementById("FechaFinalConsulta_extra").value;
    let usuario = consulta('idUsuarioConsulta_extra', 'list_UsuarioConsulta_extras');
    param = { FechaInicial: FechaI, FechaFinal: FechaF, usuario: usuario };
    //console.log(param)
    try {
        let data = JSON.stringify(param);
        let url = "../modelo/jornada_bitacora.php?band=get_horasExtras";
        let response = await sendRequest(url, data);
        // $("#" + document.getElementById("idUsuarioSueldo").id).prop("style","border: 1px solid; border-color: red");
        console.log(response)
        if (object == 0) {
            $('#div_tabla_extras').html(response);
        } else {
            $('#tbody2').html(response);
        }
        if (object == 0) {
            datatable('idTable_HorasExtras');
            var table = $('#idTableHorasExtras').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'excel', 'print'
                ]
            });

        }
    } catch (error) {
        console.log(error);
    }
}

async function save_turno(obj) {
    let elements = $('.turno');
    let ok = validate_elements(elements);
    if (ok) {
        let idxid = '';//document.getElementById('idxid').value;
        let usuario = consulta('idUsuarioTurnos', 'list_UsuarioSueldo');
        let horaTurno = document.getElementById("h_Turno").value;
        let fechaInicio = document.getElementById("FechaTurno").value;

        if (horaTurno <= 0) {
            alertify.warning('No se aceptan 0 ni negativos')
            horaTurno = document.getElementById('h_Turno');
            horaTurno.focus();
        } else {
            param = { horaTurno: horaTurno, fechaInicio: fechaInicio, usuario: usuario, idxid: idxid };
            let data = JSON.stringify(param);
            // console.log(data)

            let url = "../modelo/jornada_bitacora.php?band=save_Turnos";
            let response = await sendRequest(url, data);
            // console.log(response)
            response = JSON.parse(response);

            if (response.estado == 'error') {
                alertify.warning(response.mensaje)
            } else {
                alertify.success(response.mensaje)
                table4();
            }
            // console.log(response)                
        }
    }
}

async function table4(object = 0) {
    $('#idxid').val('');
    $('#h_Turno').val('');
    $('#FechaTurno').val('');
    // $('#idButtonTurno').addClass('btn btn-primary');
    $('#idButtonTurno').text('Guardar');
    $('#idButtonTurno').removeClass('btn-warning');
    $('#idButtonTurno').addClass('btn btn-primary')
    $('#idButtonCancelarT').hide();
    // $('#idUsuarioTurnos').val('');

    $("#" + document.getElementById("idUsuarioTurnos").id).prop("style", "border: 1px solid; border-color: #ccc");
    var id = get_data_id("idUsuarioTurnos", "list_UsuarioSueldo")

    var val = document.getElementById("idUsuarioTurnos").value;

    if (val.toLowerCase() === 'todos' || val.toLowerCase() === 'todo') {
        id = '';
    } else if (id == null) {
        id = '1';
    }
    var param = { id: id };
    try {
        let data = JSON.stringify(param);
        let url = "../modelo/jornada_bitacora.php?band=table_usuarioTurnos";
        let response = await sendRequest(url, data);
        if (response == 2) {
            alertify.error('Debe seleccionar un Usuario')
            $("#" + document.getElementById("idUsuarioSueldo").id).prop("style", "border: 1px solid; border-color: red");
        } else {
            if (object == 0) {
                $('#div_tabla4').html(response);
            } else {
                $('#idtbody2').html(response);
            }
        }
        if (object == 0) {
            datatable('idTable_turnos');
        }
    } catch (error) {
        console.log(error);
    }
}

async function delete_usuario_button_Turnos(obj) {
    //  $('#idxid').val(obj.idxid);
    var confirmacion = confirm("¿Estás seguro de que deseas eliminar este registro?");
    if (confirmacion) {
        //  let idxid = obj.idxid;
        //  $('#idxid').val(obj.idxid);
        let param = { idxid: obj };
        //s console.log(param)
        try {
            let data = JSON.stringify(param);
            let url = "../modelo/jornada_bitacora.php?band=delete_Turnos";
            let response = await sendRequest(url, data);
            //console.log(response);
            alertify.success("Se ha eliminado exitosamente");
            table4();
            $('#idButtonTurno').attr('onclick', 'save_turno();');
            $('#idButtonTurno').removeClass('btn-warning');
            $('#idButtonTurno').addClass('btn btn-primary');
            $('#idButtonTurno').text('Guardar');
        } catch (error) {
            console.log(error);
        }
        alert("Registro eliminado");
    } else {
        alert("Eliminación cancelada");
    }
}

function edit_button_usuario_Turnos(obj) {
    let idxid = obj.idxid;
    let Hora = obj.Hora;
    let FechaInicio = obj.FechaInicio;
    let usuario = obj.NombreUsuarioLargo

    // console.log(fechaFormateada)
    //console.log(obj,'-')
    // document.getElementById("idxid").value;
    table4();
    $('#idButtonCancelarT').show();
    $('#idButtonTurno').removeClass('btn-primary')
    $('#idButtonTurno').addClass('btn btn-warning');
    $('#idButtonTurno').text('Editar');
    $('#idButtonTurno').attr('onclick', 'save_turno(\'' + obj.idxid + '\');');
    $('#idxid').val(obj.idxid);
    // $('#FechaInicio').val(obj.NombreUsuarioLargo);
    // $('#FechaTurno').val(obj.FechaInicio);
    $('#h_Turno').val(obj.Hora);
    $('#FechaTurno').val(obj.FechaInicio);
    $('#idUsuarioTurnos').val(obj.NombreUsuarioLargo);
}

async function mostrar_detalle(año, tiquete, empresa, cedula, nombre) {
    param = { año: año, tiquete: tiquete, empresa: empresa, cedula: cedula };
    $('#div_tabla_detalle_biometrico').html(' ');
    try {
        let data = JSON.stringify(param);
        let url = "../modelo/jornada_bitacora.php?band=mostrar_detalle";
        let response = await sendRequest(url, data);
        //console.log(response)
        d = response.split('||');
        $('#mostrar_detalle').modal('show');
        $('#div_tabla_detalle_biometrico').html(d[0]);
        $('#user_nombre').val(nombre);
        $('#user_documento').val(cedula);
        $('#user_empresa').val(empresa);
        $('#user_tiquete').val(año + '-' + tiquete);
        $('#turno_especifico').html(d[1]);
    } catch (error) {
        console.log(error);
    }
}

function Modificar_Turnos_asignado(time) {
    $('#modificar_detalle').modal('show');
    $('#turno_actual').val(time);

}

async function grabar_correccion() {
    $('#modificar_detalle').modal('show');
    let user_tiquete = $('#user_tiquete').val()
    let user_nombre = $('#user_nombre').val()
    let user_documento = $('#user_documento').val()
    let user_empresa = $('#user_empresa').val()
    let fecha_detalle = $('#fecha_detalle').val()
    let d = user_tiquete.split('-');

    let hora_detalle = $('#hora_detalle').val()
    let salida = $('#salida').val()
    let turno = $('#turno_actual').val()
    let parcial = fecha_detalle + ' ' + hora_detalle

    let fecha = new Date(turno)
    let fecha1 = new Date(parcial)

    let horas_diferencia = fecha1.getTime() - fecha.getTime();
    horas_diferencia = parseFloat((horas_diferencia / 1000 / 60 / 60).toFixed(2))
    //    alert(horas_diferencia)
    if (horas_diferencia > 8) {
        let bandera = confirm("La fecha Digitada tiene  (" + horas_diferencia + ")  horas con respecto al hora de Entrada");
    } else
        bandera = true;

    if (bandera = true) {
        param = { fecha_detalle: fecha_detalle, hora_detalle: hora_detalle, salida: salida, turno: turno, usuario: id_usuario, user_tiquete: user_tiquete };
        try {
            let data = JSON.stringify(param);
            let url = "../modelo/jornada_bitacora.php?band=grabar_correccion";
            let response = await sendRequest(url, data);
            //console.log(response)
            mostrar_detalle(d[0], d[1], user_empresa, user_documento, user_nombre)
            get_Consulta_gral();
        } catch (error) {
            console.log(error);
        }
    }
}

function datatable(id) {
    idTable = $('#' + id).DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
        },
        "ordering": false, // Desactivar el ordenamiento de columnas
        "searching": true, // Desactivar el ordenamiento de
        initComplete: function () {
            this.api().columns().every(function () {
                let column = this;
                let columnIdx = column.index();
                let header = $('#' + id + ' thead th').eq(columnIdx);
                if (header.data('column')) {
                    let select = $('<br><input type="text" id="th_' + header.data('column') + '" data-column="' + header.data('column') + '" data-index="' + columnIdx + '" placeholder="Filtrar ' + header.text() + '" class="form-control filter" />')
                        .appendTo(header)
                        .on('keyup', function () {
                            column.search($(this).val()).draw();
                            // console.log(column.data().unique());
                            let pageInfo = idTable.page.info();
                            let totalRecords = pageInfo.recordsDisplay;

                            if (totalRecords === 0) {
                                table($('.filter'));
                                column.search($(this).val()).draw();
                                // console.log(this);
                            }
                        });

                    column.data().unique().sort().each(function (d, j) {
                        select.append('<option value="' + d + '">' + d + '</option>');
                        // console.log(column);
                        // console.log(d,j);
                        // console.log(column);
                    });
                }
            });
        }
    });
}

function loader(object) {
    // console.log(object);
    if ($('#' + object.id).hasClass('loader_min')) {
        $('#' + object.id).removeClass('loader_min');
        $('#' + object.id).prop("disabled", false);
    } else {
        $('#' + object.id).prop("disabled", true);
        $('#' + object.id).addClass('loader_min');
    }
}

function sendRequest(url, data) {
    return new Promise(function (resolve, reject) {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-Type", "application/json; charset=utf-8");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    // $('#'+object.id).removeClass('loader_min');
                    // $('#'+object.id).prop("disabled",false);
                    resolve(xhr.responseText);
                } else {
                    reject(new Error("Request failed with status: " + xhr.status));
                }
            }
        };
        xhr.send(data);
    });
}

function get_data_id(elemento, datalist) {
    //Obtener el elemento <input> y el elemento <datalist>
    var inputElement = document.getElementById(elemento);
    var dataListElement = document.getElementById(datalist);

    // Agregar un evento "change" al <input> para detectar cambios en la selección

    // Obtener el valor del <input>
    var selectedValue = inputElement.value;

    // Buscar la opción seleccionada en el <datalist> por su valor
    var selectedOption = [...dataListElement.options].find(option => option.value === selectedValue);
    //   console.log(selectedOption);

    // Obtener el valor del atributo data-id de la opción seleccionada
    var dataIdValue = selectedOption ? selectedOption.getAttribute("data-id") : null;

    // Mostrar el valor de data-id en la consola
    //   console.log("data-id:", dataIdValue);
    return dataIdValue;
}

function validate_elements(elements) {
    let ok = true
    for (x of elements) {
        //  console.log(x)
        let valor = '';
        if ($('#' + x.id).hasClass('textarea')) {
            valor = $('#' + x.id).html();
        } else if (x.list) {
            // valor = $('#' + x.list.id + '[value="' + x.value + '"]').data('id');
            valor = get_data_id(x.id, x.list.id)
            // console.log(x.list.id.options);
            // console.log(x.id,x.list.id,x.value,'-->',valor);
        } else {
            valor = x.value;
        }
        if (valor == '' || valor == ' ' || valor == -1 || valor == null) {

            //$('#'+x.id).prop('style','')
            ok = false
            $("#" + x.id).prop("style", "border: 1px solid; border-color: red");
        } else {
            $("#" + x.id).prop("style", "border: 1px solid; border-color: #ccc");

        }
    }
    return ok
}

async function get_usuario() {
    let data = JSON.stringify({ usuario: id_usuario });
    let url = "../modelo/jornada_bitacora.php?band=get_usuario";
    let response = await sendRequest(url, data);
    $('#nombreusuario').html('<span class="glyphicon glyphicon-user"></span> Inició sesión: ' + response)
    // console.log(data,response,'s')
}

function htmlToDataArray(html) {
    var table = document.createElement('table');
    table.innerHTML = html;
    var data = [];
    for (var i = 0; i < table.rows.length; i++) {
        var row = table.rows[i];
        var rowData = [];
        for (var j = 0; j < row.cells.length; j++) {
            rowData.push(row.cells[j].innerText.trim());
        }
        data.push(rowData);
    }
    return data;
}

let tablaReglasData;

async function get_ConsultaReglas(object = 0) {
    let elements = $('.reglas');
    let FechaI = document.getElementById("FechaInicialReglas").value;
    let FechaF = document.getElementById("FechaFinalReglas").value;
    // if(FechaF != '' && FechaI != ''){
    let param = get_values_consulta(elements);
    param['FechaInicial'] = FechaI;
    param['FechaFinal'] = FechaF;
    // console.log(param)
    try {
        let data = JSON.stringify(param);
        let url = "../modelo/jornada_bitacora.php?band=get_ConsultaReglas";
        let response = await sendRequest(url, data);
        // console.log(response)
        if (response == 2) {
            alertify.error('Debe seleccionar un Usuario')
            $("#" + document.getElementById("idUsuarioSueldo").id).prop("style", "border: 1px solid; border-color: red");
        } else {
            if (object == 0) {
                $('#div_tablaReglas').html(response);
                tablaReglasData = htmlToDataArray(response);
            } else {
                $('#idtbody2').html(response);
            }
        }
        // if (object == 0) {
        //     datatable('idTableConsultaReglas');

        // }
        setTimeout(foco(ultimoElementoConFoco), 2000)
    } catch (error) {
        console.log(error);
    }
}

function generarExcel() {
    // const XLSX = require('xlsx');
    if (tablaReglasData) {
        // Crear el libro de trabajo
        var wb = XLSX.utils.book_new();

        // Crear una hoja de trabajo y agregarla al libro de trabajo
        var ws = XLSX.utils.aoa_to_sheet(tablaReglasData);
        XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
        // Aplicar estilos a la primera fila (encabezados)
        var defaultCellStyle = { font: { bold: true }, alignment: { horizontal: "center" } };
        // Encabezados específicos que deseas resaltar
        var encabezadosResaltados = ['Usuario', 'Cedula'];
        // Obtener el rango de columnas
        var range = XLSX.utils.decode_range(ws['!ref']);
        var C = range.e.c + 1;  // Número total de columnas
        // Aplicar estilos a la primera fila (encabezados)
        for (var col = 0; col < C; col++) {
            var cellAddress = XLSX.utils.encode_cell({ r: 0, c: col });
            // Crear un objeto de estilo para la celda
            var cellStyle = defaultCellStyle;
            // Verificar si el encabezado debe tener un estilo especial
            if (ws[cellAddress] && ws[cellAddress].v !== undefined) {
                if (encabezadosResaltados.includes(ws[cellAddress].v)) {
                    // Puedes personalizar el estilo aquí según tus necesidades
                    cellStyle = { ...cellStyle, /* Agrega cualquier estilo adicional aquí */ };
                }
            }
            // Aplicar el estilo a la celda
            ws[cellAddress].s = cellStyle;
        }
        // Guardar el libro de trabajo como un archivo Excel
        XLSX.writeFile(wb, "datos_excel.xlsx");
    } else {
        alert("Primero debes cargar los datos de la tabla");
    }
}

function edit_button_Reglas(idxid, idRegla, FechaInicial, FechaFinal, Regla, Valor) {
    var $elementoObjetivo = $('*[data-target="#idReglasDetalles"]');
    $elementoObjetivo.click();

    $('#idButtonRegla').removeClass('btn-primary')
    $('#idButtonRegla').addClass('btn btn-warning');
    $('#idButtonRegla').text('Editar');
    // alert(Regla)
    document.getElementById('IdxidRegla').value = idxid;
    document.getElementById('campoIdRegla').value = idRegla;
    // $('#FechaInicialReglas').val()=  FechaInicial;
    document.getElementById('FechaInicialReglasDetalle').value = FechaInicial;
    document.getElementById('FechaFinalReglasDetalle').value = FechaFinal;
    document.getElementById('ReglaName').value = idRegla;
    document.getElementById('ReglaValor').value = Valor;

    obtenerRegla(idRegla);
    foco(idxid)
}

function obtenerRegla(idRegla) {
    let xhr = new XMLHttpRequest();
    let url = "../modelo/jornada_bitacora.php?band=obtener_Regla";
    //console.log(url)
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-Type", 'application/json; charset=utf-8');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {

            let datos = JSON.parse(this.responseText);
            //console.log(datos)
            destinos = datos;

            $('#ReglaName').find('option').remove();
            // let opcion = '';
            let opcion = '<option value=0 disabled >Seleccione</option>';
            $.each(datos, function (key, val) {
                if (idRegla == val.id) {
                    opcion += '<option value="' + val.id + '"selected>' + val.name + '</option>';
                } else {
                    opcion += '<option value="' + val.id + '" >' + val.name + '</option>';
                }
            });
            $('#ReglaName').append(opcion);
        }
    }
    xhr.send('');
}

function save_Regla() {
    let idxid = document.getElementById('IdxidRegla').value;
    let idRegla = document.getElementById('ReglaName').value;
    let FechaInicial = document.getElementById('FechaInicialReglasDetalle').value;
    //console.log('fechaInicial--', FechaInicial)
    let FechaFinal = document.getElementById('FechaFinalReglasDetalle').value;
    //console.log('FechaFinal---', FechaFinal)
    let Valor = document.getElementById('ReglaValor').value;
    let xhr = new XMLHttpRequest();
    let url = "../modelo/jornada_bitacora.php?band=editarReglaTiquete&idxid=" + idxid + "&idRegla=" + idRegla + "&FechaInicial=" + FechaInicial + "&FechaFinal=" + FechaFinal + "&Valor=" + Valor;
    //console.log(url)
    xhr.open("POST", url, true);
    //   // // console.log(url)
    xhr.setRequestHeader("Content-Type", 'application/json; charset=utf-8');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            $('#cerrarmodalidReglasDetalles').click()
            get_ConsultaReglas();
        }
    }
    xhr.send('');

}


let ultimoElementoConFoco = '';
let ultimoElementoConFoco0 = '';
function foco(idxid = 0) {
    // window.location.reload();
    //  get_ConsultaReglas(ultimoElementoConFoco);

    if (idxid == 0) {
        let ultimoElementoConFoco1 = document.getElementById('mantenerFoco' + ultimoElementoConFoco);
        ultimoElementoConFoco1.scrollIntoView({ behavior: 'smooth' });
        ultimoElementoConFoco1.style.backgroundColor = '#B1BBD2';
        ultimoElementoConFoco1.focus();
    } else {
        let registroConFoco = document.getElementById('mantenerFoco' + idxid);

        if (registroConFoco) {

            if (ultimoElementoConFoco0) {
                ultimoElementoConFoco0.style.backgroundColor = '';
            }

            registroConFoco.scrollIntoView({ behavior: 'smooth' });
            registroConFoco.style.backgroundColor = '#B1BBD2';
            registroConFoco.focus();
            ultimoElementoConFoco = idxid;
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('idReglasDetalles').addEventListener('click', function () {
        console.log('Se hizo clic en un elemento con la clase "miClase"');
        setTimeout(foco, 1000)


    });

});