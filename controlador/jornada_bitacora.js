let turnosDisponibles = [];
let turnoEnEdicionId = null;
let turnoEdicionRequiresConfirmation = false;
let tablasPaginadas = {};


/**
Codigo hecho por mario
Funcion: escapeHtml limpia texto antes de insertarlo en tablas y mensajes del
frontend para evitar errores visuales o inyeccion de HTML.
**/
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function sincronizarCheckboxesUsuarios() {
    document.querySelectorAll('#tabla_usuarios_multiple input[name="usuarios[]"]').forEach(checkbox => {
        checkbox.checked = usuariosSeleccionados.has(checkbox.value);
    });
    actualizarEstadoSeleccionarTodos();
}

function crearControlesTabla(selector, opciones = {}) {
    const table = document.querySelector(selector);
    if (!table) {
        return;
    }

    const key = selector;
    const wrapper = table.closest('.table-responsive') || table.parentElement;
    const tbody = table.querySelector('tbody');
    if (!wrapper || !tbody) {
        return;
    }

    const topContainer = opciones.topContainerSelector ? document.querySelector(opciones.topContainerSelector) : null;
    const bottomContainer = opciones.bottomContainerSelector ? document.querySelector(opciones.bottomContainerSelector) : null;

    wrapper.querySelectorAll(`[data-table-controls="${key}"]`).forEach(node => node.remove());
    if (topContainer) {
        topContainer.innerHTML = '';
    }
    if (bottomContainer) {
        bottomContainer.innerHTML = '';
    }

    const topControls = document.createElement('div');
    topControls.setAttribute('data-table-controls', key);
    topControls.className = 'row';
    topControls.style.margin = '10px 0';
    topControls.innerHTML = `
        <div class="col-xs-12 col-sm-6" style="margin-bottom:8px;">
            <label style="font-weight:600; margin-right:8px;">Show</label>
            <select class="form-control input-sm" style="width:110px; display:inline-block;">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <label style="font-weight:600; margin-left:8px;">entries</label>
        </div>
        <div class="col-xs-12 col-sm-6 text-right">
            <label style="font-weight:600; margin-right:8px;">Search:</label>
            <input type="text" class="form-control input-sm" style="width:300px; max-width:100%; display:inline-block;">
        </div>
    `;

    const bottomControls = document.createElement('div');
    bottomControls.setAttribute('data-table-controls', key);
    bottomControls.className = 'row';
    bottomControls.style.margin = '10px 0';
    bottomControls.innerHTML = `
        <div class="col-xs-12 col-sm-6" data-role="info"></div>
        <div class="col-xs-12 col-sm-6 text-right" data-role="pagination"></div>
    `;

    if (topContainer) {
        topContainer.appendChild(topControls);
    } else {
        wrapper.insertBefore(topControls, table);
    }

    if (bottomContainer) {
        bottomContainer.appendChild(bottomControls);
    } else {
        wrapper.appendChild(bottomControls);
    }

    const selectEntries = topControls.querySelector('select');
    const inputSearch = topControls.querySelector('input');
    const info = bottomControls.querySelector('[data-role="info"]');
    const pagination = bottomControls.querySelector('[data-role="pagination"]');

    const state = {
        page: 1,
        pageSize: parseInt(selectEntries.value, 10),
        search: '',
        selector,
        filterSelector: opciones.filterSelector || null,
        afterRender: opciones.afterRender || null
    };

    tablasPaginadas[key] = state;

    function obtenerFilasDatos() {
        return Array.from(tbody.querySelectorAll('tr')).filter(row => {
            const cells = row.querySelectorAll('td');
            if (!cells.length) {
                return false;
            }
            if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
                return false;
            }
            return true;
        });
    }

    function obtenerFilaVacia() {
        return Array.from(tbody.querySelectorAll('tr')).find(row => {
            const cells = row.querySelectorAll('td');
            return cells.length === 1 && cells[0].hasAttribute('colspan');
        });
    }

    function cumpleFiltrosColumna(row) {
        if (!state.filterSelector) {
            return true;
        }

        const filtros = document.querySelectorAll(`${state.filterSelector} th`);
        const cells = row.querySelectorAll('td');
        for (let index = 0; index < filtros.length; index++) {
            const control = filtros[index].querySelector('input, select');
            if (!control) {
                continue;
            }

            const valorFiltro = (control.value || '').toLowerCase().trim();
            if (!valorFiltro) {
                continue;
            }

            const textoCelda = (cells[index]?.innerText || '').toLowerCase().trim();
            if (!textoCelda.includes(valorFiltro)) {
                return false;
            }
        }

        return true;
    }

    function renderPagination(totalPages) {
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        const items = [];
        items.push(`<button type="button" class="btn btn-default btn-sm" data-page="prev" ${state.page === 1 ? 'disabled' : ''}>Previous</button>`);

        const start = Math.max(1, state.page - 2);
        const end = Math.min(totalPages, state.page + 2);

        if (start > 1) {
            items.push(`<button type="button" class="btn btn-default btn-sm" data-page="1">1</button>`);
            if (start > 2) {
                items.push('<span style="display:inline-block;padding:6px 8px;">...</span>');
            }
        }

        for (let page = start; page <= end; page++) {
            items.push(`<button type="button" class="btn btn-sm ${page === state.page ? 'btn-primary' : 'btn-default'}" data-page="${page}">${page}</button>`);
        }

        if (end < totalPages) {
            if (end < totalPages - 1) {
                items.push('<span style="display:inline-block;padding:6px 8px;">...</span>');
            }
            items.push(`<button type="button" class="btn btn-default btn-sm" data-page="${totalPages}">${totalPages}</button>`);
        }

        items.push(`<button type="button" class="btn btn-default btn-sm" data-page="next" ${state.page === totalPages ? 'disabled' : ''}>Next</button>`);
        pagination.innerHTML = items.join(' ');

        pagination.querySelectorAll('button[data-page]').forEach(button => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-page');
                if (target === 'prev' && state.page > 1) {
                    state.page--;
                } else if (target === 'next' && state.page < totalPages) {
                    state.page++;
                } else if (!Number.isNaN(parseInt(target, 10))) {
                    state.page = parseInt(target, 10);
                }
                render();
            });
        });
    }

    function render() {
        const rows = obtenerFilasDatos();
        const emptyRow = obtenerFilaVacia();

        const filteredRows = rows.filter(row => {
            const rowText = row.innerText.toLowerCase();
            const matchesSearch = !state.search || rowText.includes(state.search);
            return matchesSearch && cumpleFiltrosColumna(row);
        });

        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        if (state.page > totalPages) {
            state.page = totalPages;
        }

        const startIndex = total === 0 ? 0 : (state.page - 1) * state.pageSize;
        const endIndex = Math.min(startIndex + state.pageSize, total);
        const visibleRows = new Set(filteredRows.slice(startIndex, endIndex));

        rows.forEach(row => {
            row.style.display = visibleRows.has(row) ? '' : 'none';
        });

        if (emptyRow) {
            emptyRow.style.display = total === 0 ? '' : 'none';
        }

        info.innerHTML = total === 0
            ? 'Showing 0 to 0 of 0 entries'
            : `Showing ${startIndex + 1} to ${endIndex} of ${total} entries`;

        renderPagination(totalPages);

        if (typeof state.afterRender === 'function') {
            state.afterRender();
        }
    }

    selectEntries.addEventListener('change', () => {
        state.pageSize = parseInt(selectEntries.value, 10);
        state.page = 1;
        render();
    });

    inputSearch.addEventListener('input', () => {
        state.search = inputSearch.value.toLowerCase().trim();
        state.page = 1;
        render();
    });

    if (state.filterSelector) {
        document.querySelectorAll(`${state.filterSelector} input, ${state.filterSelector} select`).forEach(control => {
            control.oninput = null;
            control.onchange = null;
            control.addEventListener('input', () => {
                state.page = 1;
                render();
            });
            control.addEventListener('change', () => {
                state.page = 1;
                render();
            });
        });
    }

    render();
}

function inicializarTablaUsuariosAsignacion() {
    crearControlesTabla('#tabla_usuarios_asignacion', {
        filterSelector: '#filtros_usuarios',
        afterRender: sincronizarCheckboxesUsuarios,
        topContainerSelector: '#controles_tabla_usuarios_superior',
        bottomContainerSelector: '#controles_tabla_usuarios_inferior'
    });
}

function inicializarTablaTurnosCreados() {
    crearControlesTabla('#tabla_turnos_creados', {
        filterSelector: '#filtros_turnos_creados',
        topContainerSelector: '#controles_tabla_turnos_superior',
        bottomContainerSelector: '#controles_tabla_turnos_inferior'
    });
}

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
    const selectTurnoEdit = document.getElementById('edit_turnoTipo');
    if (selectTurnoEdit) {
        selectTurnoEdit.addEventListener('change', actualizarHorasTurnoSeleccionado);
    }
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
    // Mostrar indicador de carga
    const tablaTurnos = document.getElementById('div_tabla_turnos');
    if (tablaTurnos) {
        tablaTurnos.innerHTML = '<div class="text-center my-3"><i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Cargando turnos...</div>';
    }

    fetch('../modelo/jornada_bitacora.php?band=get_TurnosDescansos')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la conexión con el servidor');
            }
            return response.json();
        })
        .then(data => {
            // Verificar formato de respuesta (nuevo formato o antiguo)
            let turnos = [];

            if (data.hasOwnProperty('success')) {
                // Nuevo formato de respuesta usando respuestaExito/respuestaError
                if (!data.success) {
                    throw new Error(data.message || 'Error al cargar los turnos');
                }
                turnos = data.data || [];
            } else {
                // Formato antiguo (array directo)
                turnos = data || [];
            }

            turnosDisponibles = turnos;

            let html = '<table id="tabla_turnos_creados" class="table table-hover table-condensed table-bordered table-striped" style="margin: 15px;">';
            html += '<thead><tr>' +
                '<th class="text-center">Descripción</th>' +
                '<th class="text-center">Hora Inicio</th>' +
                '<th class="text-center">Hora Fin</th>' +
                '<th class="text-center">Duración</th>' +
                '<th class="text-center">Descanso</th>' + // Nueva columna para descanso
                '<th class="text-center">Acciones</th>' +
                '</tr>' +
                '<tr id="filtros_turnos_creados">' +
                '<th><input type="text" class="form-control input-sm" placeholder="Filtrar descripción"></th>' +
                '<th><input type="text" class="form-control input-sm" placeholder="Filtrar hora inicio"></th>' +
                '<th><input type="text" class="form-control input-sm" placeholder="Filtrar hora fin"></th>' +
                '<th><input type="text" class="form-control input-sm" placeholder="Filtrar duración"></th>' +
                '<th><input type="text" class="form-control input-sm" placeholder="Filtrar descanso"></th>' +
                '<th></th>' +
                '</tr></thead><tbody>';

            if (turnos.length === 0) {
                html += '<tr><td colspan="6" class="text-center">No hay turnos disponibles</td></tr>';
            } else {
                turnos.forEach(turno => {
                    // Formatear la información de descanso
                    let infoDescanso = '<span class="text-muted">Sin descanso</span>';

                    if (turno.descanso) {
                        // Si tiene descanso, mostrar en formato bonito con detalles
                        const descripcion = turno.descanso.descripcion ?
                            `<small>(${turno.descanso.descripcion})</small>` : '';

                        infoDescanso = `
                            <span class="label label-info">
                                ${turno.descanso.inicio} - ${turno.descanso.fin}
                               
                            </span>`;
                    }

                    html += `<tr>
                        <td>${turno.name}</td>
                        <td class="text-center">${turno.horaInicio || '--:--'}</td>
                        <td class="text-center">${turno.horaFin || '--:--'}</td>
                        <td class="text-center">${turno.duracion || '--:--'}</td>
                        <td class="text-center">${infoDescanso}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-primary" onclick="abrirEdicionTurnoDefinicion('${turno.id}')"><i class="glyphicon glyphicon-pencil"></i></button>
                            <button class="btn btn-xs btn-danger" onclick="iniciarEliminacionTurnoDefinicion('${turno.id}')"><i class="glyphicon glyphicon-trash"></i></button>
                        </td>
                    </tr>`;
                });
            }

            html += '</tbody></table>';

            if (tablaTurnos) {
                tablaTurnos.innerHTML = html;
                setTimeout(() => {
                    inicializarTablaTurnosCreados();
                }, 200);

                // Inicializar tooltips si hay turnos con descanso
                if (turnos.some(t => t.descanso)) {
                    setTimeout(() => {
                        try {
                            $('[data-toggle="tooltip"]').tooltip();
                        } catch (e) {
                            console.warn('No se pudieron inicializar los tooltips', e);
                        }
                    }, 100);
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar turnos:', error);

            if (tablaTurnos) {
                tablaTurnos.innerHTML = `
                    <div class="alert alert-danger" style="margin: 15px;">
                        <i class="glyphicon glyphicon-exclamation-sign"></i> 
                        Error al cargar los turnos: ${error.message}
                        <br>
                        <button class="btn btn-sm btn-default mt-2" onclick="cargar_turnosAll()">
                            <i class="glyphicon glyphicon-refresh"></i> Reintentar
                        </button>
                    </div>`;
            }
        });
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

// Funciones para las acciones
async function get_crear_turno() {
    // Obtener datos básicos del turno
    let FechaInicial_turno = document.getElementById("FechaInicial_turno").value;
    let FechaFinal_turno = document.getElementById("FechaFinal_turno").value;
    let Nombre_turno = document.getElementById("Nombre_turno").value;
    let Duracion_turno = document.getElementById("Duracion_turno").value;

    // Validar que todos los campos básicos estén llenos
    if (!FechaInicial_turno || !FechaFinal_turno || !Nombre_turno) {
        alertify.error("Todos los campos son obligatorios");
        return;
    }

    // Preparar objeto base de parámetros
    let param = {
        FechaInicial_turno: FechaInicial_turno,
        FechaFinal_turno: FechaFinal_turno,
        Nombre_turno: Nombre_turno,
        Duracion_turno: Duracion_turno,
        idusuario: id_usuario
    };

    // Comprobar si se incluye descanso y recopilar sus datos
    const incluirDescanso = document.getElementById("incluir_descanso").checked;
    if (incluirDescanso) {
        const inicioDescanso = document.getElementById("inicio_descanso").value;
        const finDescanso = document.getElementById("fin_descanso").value;
        const duracionDescanso = document.getElementById("duracion_descanso").value;
        const descripcionDescanso = document.getElementById("descripcion_descanso").value;

        // Validar que los campos de descanso necesarios estén llenos
        if (!inicioDescanso || !finDescanso) {
            alertify.error("Debe indicar la hora de inicio y fin del descanso");
            return;
        }

        // Añadir datos del descanso al objeto de parámetros
        param.incluirDescanso = true;
        param.inicioDescanso = inicioDescanso;
        param.finDescanso = finDescanso;
        param.duracionDescanso = duracionDescanso;
        param.descripcionDescanso = descripcionDescanso;
    }

    // Mostrar indicador de carga en el botón
    const btnCrear = document.getElementById("button_crear_t");
    const btnTextoOriginal = btnCrear.innerHTML;
    btnCrear.disabled = true;
    btnCrear.innerHTML = '<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Procesando...';

    // Enviar petición al servidor
    try {
        let url = "../modelo/jornada_bitacora.php?band=save_turno";
        let data = JSON.stringify(param);
        let response = await sendRequest(url, data);

        // Analizar la respuesta
        let resultado;
        try {
            // Intentar parsear como JSON (nuevo formato de respuesta)
            resultado = JSON.parse(response);

            if (resultado.success) {
                // Éxito - nuevo formato de respuesta
                alertify.success(resultado.message || 'Turno creado correctamente');
                limpiarFormularioTurno();
                cargar_turnosAll();
            } else {
                // Error - nuevo formato de respuesta
                manejarErrorTurno(resultado.message, resultado.errors?.code);
            }
        } catch (parseError) {
            // La respuesta no es JSON, usar el formato antiguo (códigos numéricos)
            const codigoRespuesta = parseInt(response);

            if (codigoRespuesta === 0) {
                // Éxito - formato antiguo
                alertify.success('Turno creado correctamente');
                limpiarFormularioTurno();
                cargar_turnosAll();
            } else {
                // Error - formato antiguo
                manejarErrorTurno(null, codigoRespuesta);
            }
        }
    } catch (error) {
        console.error("Error en la solicitud:", error);
        alertify.error('Error al crear el turno: ' + error.message);
    } finally {
        // Restaurar el botón independientemente del resultado
        btnCrear.disabled = false;
        btnCrear.innerHTML = btnTextoOriginal;
    }

}

function construirDatosTurno() {
    const Nombre_turno = document.getElementById("Nombre_turno").value.trim();
    const FechaInicial_turno = document.getElementById("FechaInicial_turno").value;
    const FechaFinal_turno = document.getElementById("FechaFinal_turno").value;
    const Duracion_turno = document.getElementById("Duracion_turno").value;

    if (!Nombre_turno || !FechaInicial_turno || !FechaFinal_turno) {
        alertify.error("Todos los campos básicos son obligatorios");
        return null;
    }

    const payload = {
        FechaInicial_turno,
        FechaFinal_turno,
        Nombre_turno,
        Duracion_turno,
        idusuario: id_usuario
    };

    const incluirDescanso = document.getElementById("incluir_descanso").checked;
    payload.incluirDescanso = incluirDescanso;
    if (incluirDescanso) {
        const inicioDescanso = document.getElementById("inicio_descanso").value;
        const finDescanso = document.getElementById("fin_descanso").value;
        const duracionDescanso = document.getElementById("duracion_descanso").value;
        const descripcionDescanso = document.getElementById("descripcion_descanso").value;

        if (!inicioDescanso || !finDescanso) {
            alertify.error("Debe indicar la hora de inicio y fin del descanso");
            return null;
        }

        payload.inicioDescanso = inicioDescanso;
        payload.finDescanso = finDescanso;
        payload.duracionDescanso = duracionDescanso || null;
        payload.descripcionDescanso = descripcionDescanso || null;
    } else {
        payload.inicioDescanso = null;
        payload.finDescanso = null;
        payload.duracionDescanso = null;
        payload.descripcionDescanso = null;
    }

    return payload;
}

/**
Codigo hecho por mario
Funcion: actualizarTurno envia al backend la actualizacion de una definicion de
turno y refresca la interfaz cuando la operacion termina correctamente.
**/
async function actualizarTurno() {
    if (!turnoEnEdicionId) {
        alertify.error("No hay ningún turno seleccionado para editar");
        return;
    }

    const payload = construirDatosTurno();
    if (!payload) {
        return;
    }

    payload.idTurno = turnoEnEdicionId;

    const btnEditar = document.getElementById("button_crear_t");
    const textoOriginal = btnEditar.innerHTML;
    btnEditar.disabled = true;
    btnEditar.innerHTML = '<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Actualizando...';

    try {
        const confirmarEdicion = document.getElementById('confirmar_edicion_turno');
        if (turnoEdicionRequiresConfirmation) {
            if (!confirmarEdicion || !confirmarEdicion.checked) {
                alertify.error('Debe confirmar que comprende el impacto de los cambios');
                return;
            }
        }

        let response = await sendRequest("../modelo/jornada_bitacora.php?band=update_turno_definicion", JSON.stringify(payload));
        const resultado = JSON.parse(response);
        if (resultado.success) {
            alertify.success(resultado.message || 'Turno actualizado correctamente');
            limpiarFormularioTurno();
            cargar_turnosAll();
        } else {
            alertify.error(resultado.message || 'Error al actualizar el turno');
        }
    } catch (error) {
        console.error("Error en la solicitud de actualización:", error);
        alertify.error('Error al actualizar el turno: ' + error.message);
    } finally {
        btnEditar.disabled = false;
        btnEditar.innerHTML = textoOriginal;
    }
}

function llenarSelectTurnosAsignados(selectedTurnoId) {
    const selectTurno = document.getElementById('edit_turnoTipo');
    if (!selectTurno) {
        return;
    }

    selectTurno.innerHTML = '';

    if (turnosDisponibles.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No hay turnos definidos';
        selectTurno.appendChild(option);
        return;
    }

    turnosDisponibles.forEach(turno => {
        const option = document.createElement('option');
        option.value = turno.id;
        const inicio = turno.horaInicio || '--:--';
        const fin = turno.horaFin || '--:--';
        option.textContent = `${turno.name} (${inicio} - ${fin})`;
        if (selectedTurnoId && selectedTurnoId === turno.id) {
            option.selected = true;
        }
        selectTurno.appendChild(option);
    });

    actualizarHorasTurnoSeleccionado();
}

function actualizarHorasTurnoSeleccionado() {
    const selectTurno = document.getElementById('edit_turnoTipo');
    const horaInicioEl = document.getElementById('edit_horaInicio');
    const horaFinEl = document.getElementById('edit_horaFin');
    if (!selectTurno || !horaInicioEl || !horaFinEl) {
        return;
    }

    const turno = turnosDisponibles.find(item => item.id === selectTurno.value);
    horaInicioEl.value = turno ? (turno.horaInicio || '') : '';
    horaFinEl.value = turno ? (turno.horaFin || '') : '';
}

// Función auxiliar para manejar errores específicos
function manejarErrorTurno(mensaje, codigo) {
    switch (codigo) {
        case 1:
            alertify.error(mensaje || 'Error: La duración mínima del turno es incorrecta');
            break;
        case 2:
            alertify.error(mensaje || 'Error: La duración calculada no coincide con la proporcionada');
            break;
        case 3:
            alertify.error(mensaje || 'Error: El período de descanso debe estar dentro del horario del turno');
            break;
        case 4:
            alertify.error(mensaje || 'Error: El período de descanso no es válido para turnos que cruzan la medianoche');
            break;
        case 5:
            alertify.error(mensaje || 'Error: Ya existe un turno con el mismo horario');
            break;
        default:
            alertify.error(mensaje || 'Hubo un error al crear el turno');
            break;
    }
}

// Función auxiliar para limpiar el formulario completo
function limpiarFormularioTurno() {
    // Limpiar campos básicos del turno
    document.getElementById('Nombre_turno').value = '';
    document.getElementById('FechaInicial_turno').value = '';
    document.getElementById('FechaFinal_turno').value = '';
    document.getElementById('Duracion_turno').value = '';

    // Limpiar campos de descanso
    document.getElementById('incluir_descanso').checked = false;
    document.getElementById('campos_descanso').style.display = 'none';
    limpiarCamposDescanso();

    // Restablecer el estado del checkbox
    document.getElementById('incluir_descanso').disabled = true;
    resetTurnoFormState();
}

function cancelarEdicionTurno() {
    limpiarFormularioTurno();
}

function resetTurnoFormState() {
    turnoEnEdicionId = null;
    const boton = document.getElementById('button_crear_t');
    if (boton) {
        boton.innerHTML = '<i class="glyphicon glyphicon-time"></i> Crear Turno';
        boton.onclick = get_crear_turno;
    }
    const cancelar = document.getElementById('button_cancelar_edicion');
    if (cancelar) {
        cancelar.style.display = 'none';
    }
    const warningDiv = document.getElementById('turno_creado_warning');
    if (warningDiv) {
        warningDiv.style.display = 'none';
    }
    const confirmationCheckbox = document.getElementById('confirmar_edicion_turno');
    if (confirmationCheckbox) {
        confirmationCheckbox.checked = false;
    }
    turnoEdicionRequiresConfirmation = false;
}

async function iniciarEliminacionTurnoDefinicion(idTurno) {
    if (!idTurno) {
        alertify.error('ID de turno inválido');
        return;
    }

    try {
        const response = await fetch('../modelo/jornada_bitacora.php?band=validate_delete_turno_definicion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ idTurno })
        });

        const data = await response.json();

        if (!data.success) {
            alertify.error(data.message || 'Error al validar la eliminación del turno');
            console.error('Validación turno:', data);
            return;
        }

        const validation = data.data || {};

        if (validation.hardBlock) {
            const assignedList = validation.assignedUsers || [];
            const messageParts = [];
            if (validation.hardMessage) {
                messageParts.push(validation.hardMessage);
            }
            if (assignedList.length) {
                messageParts.push(`Usuarios asignados: ${assignedList.join(', ')}`);
            }
            swal('No se puede eliminar', messageParts.join('<br>'), 'error');
            return;
        }

        const content = document.createElement('div');
        if (validation.turnoDescripcion) {
            const turnoDesc = document.createElement('p');
            turnoDesc.innerHTML = `<strong>Turno:</strong> ${validation.turnoDescripcion}`;
            content.appendChild(turnoDesc);
        }

        const assignedUsers = validation.assignedUsers || [];
        if (assignedUsers.length) {
            const assignedEl = document.createElement('p');
            assignedEl.innerHTML = `<strong>Usuarios asignados:</strong> ${assignedUsers.join(', ')}`;
            content.appendChild(assignedEl);
        }

        const warnings = validation.warnings || [];
        const needsCheckbox = warnings.length > 0;
        if (warnings.length) {
            const warningList = document.createElement('div');
            warningList.innerHTML = `<p>Advertencias:</p><ul>${warnings.map(w => `<li>${w}</li>`).join('')}</ul>`;
            content.appendChild(warningList);
        }

        if (needsCheckbox) {
            const checkboxWrapper = document.createElement('div');
            checkboxWrapper.innerHTML = '<label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" id="delete-definicion-checkbox"> Confirmo que comprendo las advertencias.</label>';
            content.appendChild(checkboxWrapper);
        }

        swal({
            title: 'Eliminar turno',
            text: validation.softMessage || 'Esta acción eliminará el turno.',
            icon: needsCheckbox ? 'warning' : 'info',
            content: content,
            buttons: ['Cancelar', 'Eliminar'],
            dangerMode: true
        }).then(async (confirmado) => {
            if (!confirmado) {
                return;
            }

            if (needsCheckbox) {
                const checkbox = document.getElementById('delete-definicion-checkbox');
                if (!checkbox || !checkbox.checked) {
                    alertify.error('Debe confirmar las advertencias marcando la casilla.');
                    return;
                }
            }

            await eliminarTurnoDefinicion(idTurno);
        });
    } catch (error) {
        console.error('Error al validar la eliminación del turno:', error);
        alertify.error('Error al validar la eliminación del turno');
    }
}

async function eliminarTurnoDefinicion(idTurno) {
    try {
        const response = await fetch('../modelo/jornada_bitacora.php?band=delete_turno_definicion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ idTurno })
        });
        const data = await response.json();
        if (data.success) {
            alertify.success(data.message || 'Turno eliminado correctamente');
            limpiarFormularioTurno();
            cargar_turnosAll();
        } else {
            alertify.error(data.message || 'No se pudo eliminar el turno');
        }
    } catch (error) {
        console.error('Error al eliminar turno:', error);
        alertify.error('Error al eliminar el turno');
    }
}

function abrirEdicionTurnoDefinicion(idTurno) {
    const turno = turnosDisponibles.find(item => item.id === idTurno);
    if (!turno) {
        alertify.error('No se encontró la definición del turno seleccionado');
        return;
    }

    document.getElementById('Nombre_turno').value = turno.name;
    document.getElementById('FechaInicial_turno').value = turno.horaInicio;
    document.getElementById('FechaFinal_turno').value = turno.horaFin;
    document.getElementById('Duracion_turno').value = turno.duracion;

    if (turno.descanso) {
        document.getElementById('incluir_descanso').checked = true;
        document.getElementById('campos_descanso').style.display = 'block';
        document.getElementById('inicio_descanso').value = turno.descanso.inicio;
        document.getElementById('fin_descanso').value = turno.descanso.fin;
        document.getElementById('duracion_descanso').value = turno.descanso.duracion;
        document.getElementById('descripcion_descanso').value = turno.descanso.descripcion || '';
    } else {
        document.getElementById('incluir_descanso').checked = false;
        document.getElementById('campos_descanso').style.display = 'none';
        limpiarCamposDescanso();
    }

    turnoEnEdicionId = idTurno;
    const boton = document.getElementById('button_crear_t');
    boton.innerHTML = '<i class="glyphicon glyphicon-pencil"></i> Actualizar Turno';
    boton.onclick = actualizarTurno;
    document.getElementById('button_cancelar_edicion').style.display = 'inline-block';
    cargarAdvertenciasTurnoDefinicion(idTurno);
}

async function cargarAdvertenciasTurnoDefinicion(idTurno) {
    const warningDiv = document.getElementById('turno_creado_warning');
    const warningText = document.getElementById('turno_creado_warning_text');
    const confirmationCheckbox = document.getElementById('confirmar_edicion_turno');

    if (!warningDiv || !warningText || !confirmationCheckbox) {
        return;
    }

    warningDiv.style.display = 'none';
    warningText.innerHTML = '';
    confirmationCheckbox.checked = false;
    turnoEdicionRequiresConfirmation = false;

    try {
        const response = await fetch('../modelo/jornada_bitacora.php?band=validate_delete_turno_definicion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ idTurno })
        });

        const data = await response.json();
        if (!data.success) {
            warningText.textContent = data.message || 'No se pudieron recuperar las advertencias.';
            warningDiv.style.display = 'block';
            return;
        }

        const validation = data.data || {};
        const messages = [];
        if (validation.hardMessage) {
            messages.push(validation.hardMessage);
        }
        if (validation.assignedUsers && validation.assignedUsers.length) {
            messages.push('Usuarios asignados: ' + validation.assignedUsers.join(', '));
        }
        if (validation.warnings && validation.warnings.length) {
            messages.push(...validation.warnings);
        }

        if (messages.length) {
            warningText.innerHTML = `<strong>Advertencias:</strong><br>${messages.map(msg => `<div>${msg}</div>`).join('')}`;
            warningDiv.style.display = 'block';
            turnoEdicionRequiresConfirmation = true;
        }
    } catch (error) {
        console.error('Error al validar las advertencias del turno:', error);
    }
}
/**
 * Calcula la duración entre las horas de inicio y fin del turno
 * y habilita/deshabilita el checkbox de descanso según corresponda
 */
function calcularDuracion(horaInicio, horaFin) {
    const incluirDescanso = document.getElementById('incluir_descanso');

    // Limpiar y deshabilitar checkbox si no hay horas completas
    if (!horaInicio || !horaFin) {
        incluirDescanso.checked = false;
        incluirDescanso.disabled = true;
        document.getElementById('campos_descanso').style.display = 'none';
        limpiarCamposDescanso();
        return;
    }

    // Convertir horas a objetos Date para facilitar comparaciones
    const inicio = new Date(`2000-01-01T${horaInicio}`);
    const fin = new Date(`2000-01-01T${horaFin}`);

    let diferencia = fin - inicio;

    // Ajustar si el turno cruza medianoche
    if (diferencia < 0) {
        diferencia += 24 * 60 * 60 * 1000; // 24 horas en milisegundos
    }

    // Calcular horas y minutos
    const horas = Math.floor(diferencia / (1000 * 60 * 60));
    const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));

    // Actualizar campo de duración
    document.getElementById('Duracion_turno').value =
        `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;

    // Habilitar checkbox si hay un rango válido
    incluirDescanso.disabled = false;

    // Si hay un periodo de descanso activo, actualizar los rangos permitidos
    if (incluirDescanso.checked) {
        validarRangoDescanso();
    }

    // Actualizar descripción automática si está activada
    if (document.getElementById('descripcion_auto')?.checked) {
        generarDescripcionAuto();
    }
}

/**
 * Controla la visualización de los campos de descanso
 * y valida que solo se puedan mostrar si hay horas de turno seleccionadas
 */
/**
 * Controla la visualización de los campos de descanso
 */
function toggleDescansoFields() {
    const incluirDescanso = document.getElementById('incluir_descanso');
    const camposDescanso = document.getElementById('campos_descanso');
    const horaInicio = document.getElementById('FechaInicial_turno').value;
    const horaFin = document.getElementById('FechaFinal_turno').value;

    // Verificar si hay horas seleccionadas
    if (!horaInicio || !horaFin) {
        incluirDescanso.checked = false;
        incluirDescanso.disabled = true;
        camposDescanso.style.display = 'none';
        limpiarCamposDescanso();
        alertify.warning('Debe seleccionar hora de entrada y salida antes de incluir descanso');
        return;
    }

    // Mostrar/ocultar campos según estado del checkbox
    if (incluirDescanso.checked) {
        camposDescanso.style.display = 'block';
        validarRangoDescanso();
    } else {
        camposDescanso.style.display = 'none';
        limpiarCamposDescanso();
        // Recalcular duración efectiva (sin descanso) cuando se desmarca el checkbox
        recalcularDuracionEfectiva();
    }
}

/**
 * Determina si un turno cruza la medianoche
 */
function turnoNocturno(horaInicio, horaFin) {
    return horaFin < horaInicio;
}

/**
 * Valida que las horas de descanso estén dentro del rango del turno,
 * teniendo en cuenta si el turno cruza la medianoche
 */
function validarRangoDescanso() {
    const horaInicio = document.getElementById('FechaInicial_turno').value;
    const horaFin = document.getElementById('FechaFinal_turno').value;
    const inicioDescanso = document.getElementById('inicio_descanso');
    const finDescanso = document.getElementById('fin_descanso');

    if (!horaInicio || !horaFin) {
        alertify.warning('Primero debe establecer las horas del turno');
        return false;
    }

    const esNocturno = turnoNocturno(horaInicio, horaFin);

    // En caso de turnos normales (diurnos), establecer límites normales
    if (!esNocturno) {
        inicioDescanso.min = horaInicio;
        inicioDescanso.max = horaFin;
        finDescanso.min = horaInicio;
        finDescanso.max = horaFin;

        // Validar valores actuales para turnos diurnos
        if (inicioDescanso.value) {
            if (inicioDescanso.value < horaInicio) {
                inicioDescanso.value = horaInicio;
                alertify.warning('La hora de inicio del descanso se ha ajustado al inicio del turno');
            } else if (inicioDescanso.value > horaFin) {
                inicioDescanso.value = horaInicio;
                alertify.warning('La hora de inicio del descanso se ha ajustado al inicio del turno');
            }
        }

        if (finDescanso.value) {
            if (finDescanso.value < horaInicio) {
                finDescanso.value = horaFin;
                alertify.warning('La hora de fin del descanso se ha ajustado al fin del turno');
            } else if (finDescanso.value > horaFin) {
                finDescanso.value = horaFin;
                alertify.warning('La hora de fin del descanso se ha ajustado al fin del turno');
            }
        }
    } else {
        // Para turnos nocturnos, no establecemos restricciones via min/max
        // porque HTML no maneja bien los rangos que cruzan medianoche
        inicioDescanso.removeAttribute('min');
        inicioDescanso.removeAttribute('max');
        finDescanso.removeAttribute('min');
        finDescanso.removeAttribute('max');

        // En su lugar, validamos manualmente después de cada cambio
        if (inicioDescanso.value) {
            // Verificar si el inicio del descanso está dentro del turno nocturno
            const inicioEnRango = tiempoEnRango(inicioDescanso.value, horaInicio, horaFin);

            if (!inicioEnRango) {
                inicioDescanso.value = horaInicio;
                alertify.warning('La hora de inicio del descanso debe estar dentro del horario del turno ' +
                    `(${horaInicio} a ${horaFin})`);
            }
        }

        if (finDescanso.value) {
            // Verificar si el fin del descanso está dentro del turno nocturno
            const finEnRango = tiempoEnRango(finDescanso.value, horaInicio, horaFin);

            if (!finEnRango) {
                finDescanso.value = horaFin;
                alertify.warning('La hora de fin del descanso debe estar dentro del horario del turno ' +
                    `(${horaInicio} a ${horaFin})`);
            }
        }

        // Verificar que el fin del descanso sea posterior al inicio (considerando medianoche)
        if (inicioDescanso.value && finDescanso.value) {
            const inicio = new Date(`2000-01-01T${inicioDescanso.value}`);
            const fin = new Date(`2000-01-01T${finDescanso.value}`);

            // Si el fin es antes del inicio y no cruzan la medianoche, ajustar
            if (fin < inicio && !turnoNocturno(inicioDescanso.value, finDescanso.value)) {
                finDescanso.value = inicioDescanso.value;
                alertify.warning('La hora de fin del descanso debe ser posterior a la hora de inicio');
            }
        }
    }

    return true;
}

/**
 * Verifica si un tiempo está dentro de un rango que puede cruzar medianoche
 */
function tiempoEnRango(tiempo, inicio, fin) {
    if (inicio <= fin) {
        // Rango normal (ejemplo: 8:00 a 16:00)
        return tiempo >= inicio && tiempo <= fin;
    } else {
        // Rango que cruza medianoche (ejemplo: 22:00 a 6:00)
        return tiempo >= inicio || tiempo <= fin;
    }
}

/**
 * Calcula la duración del periodo de descanso
 * y actualiza la duración efectiva del turno
 */
function calcularDuracionDescanso() {
    const inicioDescansoValue = document.getElementById('inicio_descanso').value;
    const finDescansoValue = document.getElementById('fin_descanso').value;
    const horaInicio = document.getElementById('FechaInicial_turno').value;
    const horaFin = document.getElementById('FechaFinal_turno').value;

    if (!inicioDescansoValue || !finDescansoValue) {
        document.getElementById('duracion_descanso').value = '00:00';
        return;
    }

    // Validar que el descanso está dentro del turno
    const esNocturno = turnoNocturno(horaInicio, horaFin);

    if (esNocturno) {
        // Para turnos nocturnos, verificar que el inicio y fin del descanso están en el rango correcto
        const inicioEnRango = tiempoEnRango(inicioDescansoValue, horaInicio, horaFin);
        const finEnRango = tiempoEnRango(finDescansoValue, horaInicio, horaFin);

        if (!inicioEnRango || !finEnRango) {
            alertify.error('El período de descanso debe estar completamente dentro del horario del turno');
            document.getElementById('duracion_descanso').value = '00:00';
            return;
        }
    }

    // Calcular duración del descanso teniendo en cuenta posible cruce de medianoche
    const inicio = new Date(`2000-01-01T${inicioDescansoValue}`);
    const fin = new Date(`2000-01-01T${finDescansoValue}`);

    // Si fin es menor que inicio, significa que el descanso cruza la medianoche
    let diferencia = fin - inicio;
    if (diferencia < 0) {
        diferencia += 24 * 60 * 60 * 1000;
    }

    // Calcular horas y minutos
    const horas = Math.floor(diferencia / (1000 * 60 * 60));
    const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));

    // Actualizar campo de duración del descanso
    document.getElementById('duracion_descanso').value =
        `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;

    // Recalcular duración efectiva del turno
    recalcularDuracionEfectiva();
}

/**
 * Recalcula la duración efectiva del turno restando el tiempo de descanso
 * y actualiza el campo de duración
 */
function recalcularDuracionEfectiva() {
    const incluirDescanso = document.getElementById('incluir_descanso');

    // Obtener los valores necesarios
    const horaInicio = document.getElementById('FechaInicial_turno').value;
    const horaFin = document.getElementById('FechaFinal_turno').value;

    // Calcular duración total del turno sin considerar descanso
    if (!horaInicio || !horaFin) return;

    const inicio = new Date(`2000-01-01T${horaInicio}`);
    const fin = new Date(`2000-01-01T${horaFin}`);

    let duracionTotal = fin - inicio;
    if (duracionTotal < 0) {
        duracionTotal += 24 * 60 * 60 * 1000;
    }

    // Calcular horas y minutos efectivos
    // Si el checkbox está marcado, restar el descanso
    if (incluirDescanso.checked) {
        const duracionDescanso = document.getElementById('duracion_descanso').value;

        if (duracionDescanso && duracionDescanso !== '00:00') {
            const [horasDescanso, minutosDescanso] = duracionDescanso.split(':').map(Number);
            const descansoMs = (horasDescanso * 60 * 60 + minutosDescanso * 60) * 1000;
            duracionTotal -= descansoMs;
        }
    }

    const horasTotales = Math.floor(duracionTotal / (1000 * 60 * 60));
    const minutosTotales = Math.floor((duracionTotal % (1000 * 60 * 60)) / (1000 * 60));
    document.getElementById('Duracion_turno').value =
        `${horasTotales.toString().padStart(2, '0')}:${minutosTotales.toString().padStart(2, '0')}`;

    // Actualizar la descripción automática si está activada
    if (document.getElementById('descripcion_auto')?.checked) {
        generarDescripcionAuto();
    }
}

/**
 * Limpia todos los campos relacionados con el periodo de descanso
 */
function limpiarCamposDescanso() {
    document.getElementById('inicio_descanso').value = '';
    document.getElementById('fin_descanso').value = '';
    document.getElementById('duracion_descanso').value = '';
    document.getElementById('descripcion_descanso').value = '';
}

/**
 * Modificación de la función existente para incluir información del descanso
 */
function generarDescripcionAuto() {
    const checkboxAuto = document.getElementById('descripcion_auto');
    if (!checkboxAuto.checked) return;

    const horaInicio = document.getElementById('FechaInicial_turno').value;
    const horaFin = document.getElementById('FechaFinal_turno').value;
    const incluirDescanso = document.getElementById('incluir_descanso').checked;

    if (horaInicio && horaFin) {
        let descripcion = `Turno (${horaInicio} - ${horaFin})`;

        if (incluirDescanso) {
            const duracionDescanso = document.getElementById('duracion_descanso').value;
            if (duracionDescanso && duracionDescanso !== '00:00') {
                descripcion += ` con ${duracionDescanso} de descanso`;
            }
        }

        document.getElementById('Nombre_turno').value = descripcion;
    }
}

/**
 * Función para inicializar eventos adicionales cuando se carga la página
 */
document.addEventListener('DOMContentLoaded', function () {
    // Asignar eventos para los campos de descanso
    const inicioDescanso = document.getElementById('inicio_descanso');
    const finDescanso = document.getElementById('fin_descanso');
    const descripcionDescanso = document.getElementById('descripcion_descanso');

    // Configurar evento para cuando cambie el inicio del descanso
    if (inicioDescanso) {
        inicioDescanso.addEventListener('change', function () {
            calcularDuracionDescanso();
        });
    }

    // Configurar evento para cuando cambie el fin del descanso
    if (finDescanso) {
        finDescanso.addEventListener('change', function () {
            calcularDuracionDescanso();
        });
    }

    // Configurar evento para cuando cambie la descripción y esté en modo automático
    if (descripcionDescanso) {
        descripcionDescanso.addEventListener('change', function () {
            if (document.getElementById('descripcion_auto')?.checked) {
                generarDescripcionAuto();
            }
        });
    }

    // Inicializar estado del checkbox de descanso
    const incluirDescanso = document.getElementById('incluir_descanso');
    if (incluirDescanso) {
        incluirDescanso.disabled = true;
    }
});



/*==============================================
    🕕 SUBMÓDULO ASIGNAR TURNOS 🕕
================================================
    ✨ Funcionalidades principales:
    
    🔄 Búsqueda de usuarios
    📝 Asignación de turnos a múltiples usuarios

================================================*/

/**
 * Código hecho por Mario
 * Función: get_Usuarios
 * Descripción: Solicita al backend los usuarios filtrados por centro permitido,
 *              renderiza la tabla incluyendo la empresa y mantiene el conjunto de seleccionados.
 */
// Variable global para almacenar IDs de usuarios seleccionados
let usuariosSeleccionados = new Set();

// Función mejorada para buscar usuarios preservando las selecciones
async function get_Usuarios(texto = '') {
    try {
        // 1. Preparar parámetros
        const param = {
            idUsuario: id_usuario, // Agregar idUsuario para filtrar por centros permitidos
            texto: texto
        };

        // 2. Realizar petición
        const response = await fetch('../modelo/jornada_bitacora.php?band=get_Usuarios', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(param)
        });

        // 3. Validar respuesta
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener usuarios');
        }

        // 4. Obtener elemento tabla
        const tablaUsuarios = document.getElementById('tabla_usuarios_multiple');
        if (!tablaUsuarios) {
            throw new Error('Elemento tabla_usuarios_multiple no encontrado');
        }

        // 5. Generar HTML
        let html = '';
        if (!data.data || data.data.length === 0) {
            html = '<tr><td colspan="5" class="text-center">No se encontraron usuarios</td></tr>';
        } else {
            data.data.forEach(usuario => {
                const estaSeleccionado = usuariosSeleccionados.has(usuario.id);
                const nombre = escapeHtml(usuario.nombre || '');
                const cedula = escapeHtml(usuario.cedula || '');
                const cargo = escapeHtml(usuario.cargo || 'NO ESPECIFICADO');
                const empresa = escapeHtml(usuario.empresa || 'Sin empresa');

                html += `
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" 
                                name="usuarios[]" 
                                value="${usuario.id}"
                                onchange="actualizarSeleccionUsuario(this)"
                                ${estaSeleccionado ? 'checked' : ''}>
                        </td>
                        <td>${nombre}</td>
                        <td>${cedula}</td>
                        <td>${cargo}</td>
                        <td>${empresa}</td>
                    </tr>`;
            });
        }

        // 6. Actualizar tabla
        tablaUsuarios.innerHTML = html;

        // 7. Actualizar contadores y estados
        actualizarConteo();
        actualizarEstadoSeleccionarTodos();

        // 8. Refrescar filtros y opciones de empresa
        actualizarOpcionesEmpresa(data.data || []);
        setTimeout(() => {
            inicializarTablaUsuariosAsignacion();
            sincronizarCheckboxesUsuarios();
        }, 200);

        // 9. Agregar tooltip con información adicional
        $('[data-toggle="tooltip"]').tooltip();

    } catch (error) {
        console.error('Error en get_Usuarios:', error);
        alertify.error('Error al cargar la lista de usuarios: ' + error.message);

        // Mostrar mensaje de error en la tabla
        const tablaUsuarios = document.getElementById('tabla_usuarios_multiple');
        if (tablaUsuarios) {
            tablaUsuarios.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Error al cargar usuarios: ${error.message}
                    </td>
                </tr>`;
        }
    }
}

/**
 * Código hecho por Mario
 * Función: actualizarOpcionesEmpresa
 * Descripción: Reconstruye el selector de empresa con las opciones presentes en la respuesta.
 * @param {Array} usuarios Lista de usuarios para extraer empresas únicas
 */
function actualizarOpcionesEmpresa(usuarios) {
    const select = document.getElementById('filter_empresa');
    if (!select) {
        return;
    }

    const opciones = new Set();
    usuarios.forEach(usuario => {
        const nombre = (usuario.empresa || 'Sin empresa').trim();
        if (nombre) {
            opciones.add(nombre);
        }
    });

    const listado = [...opciones].sort((a, b) => a.localeCompare(b));
    const valorActual = select.value;
    let html = '<option value="">Todas</option>';
    listado.forEach(nombre => {
        const seguro = escapeHtml(nombre);
        html += `<option value="${seguro}">${seguro}</option>`;
    });

    select.innerHTML = html;
    if (valorActual && listado.some(option => option.toLowerCase() === valorActual.toLowerCase())) {
        select.value = valorActual;
    } else {
        select.value = '';
    }
}

/**
 * Código hecho por Mario
 * Función: asegurarListenersFiltrosUsuarios
 * Descripción: Adjunta listeners a los filtros de columnas para reaplicar el filtrado dinámicamente.
 */
function asegurarListenersFiltrosUsuarios() {
    const inputs = [
        document.getElementById('filter_nombre'),
        document.getElementById('filter_cedula'),
        document.getElementById('filter_cargo')
    ];

    inputs.forEach(input => {
        if (input && input.dataset.filterAttached !== '1') {
            input.addEventListener('input', aplicarFiltrosUsuarios);
            input.dataset.filterAttached = '1';
        }
    });

    const empresaSelect = document.getElementById('filter_empresa');
    if (empresaSelect && empresaSelect.dataset.filterAttached !== '1') {
        empresaSelect.addEventListener('change', aplicarFiltrosUsuarios);
        empresaSelect.dataset.filterAttached = '1';
    }
}

/**
 * Código hecho por Mario
 * Función: aplicarFiltrosUsuarios
 * Descripción: Oculta filas según los valores ingresados en los filtros de nombre, cédula, cargo y empresa.
 */
function aplicarFiltrosUsuarios() {
    const nombreFiltro = document.getElementById('filter_nombre')?.value.toLowerCase().trim() || '';
    const cedulaFiltro = document.getElementById('filter_cedula')?.value.toLowerCase().trim() || '';
    const cargoFiltro = document.getElementById('filter_cargo')?.value.toLowerCase().trim() || '';
    const empresaFiltro = document.getElementById('filter_empresa')?.value.toLowerCase().trim() || '';

    const filas = document.querySelectorAll('#tabla_usuarios_multiple tr');
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length < 5) {
            fila.style.display = '';
            return;
        }

        const nombre = celdas[1]?.innerText.toLowerCase().trim() || '';
        const cedula = celdas[2]?.innerText.toLowerCase().trim() || '';
        const cargo = celdas[3]?.innerText.toLowerCase().trim() || '';
        const empresa = celdas[4]?.innerText.toLowerCase().trim() || '';

        const cumpleNombre = nombre.includes(nombreFiltro);
        const cumpleCedula = cedula.includes(cedulaFiltro);
        const cumpleCargo = cargo.includes(cargoFiltro);
        const cumpleEmpresa = empresaFiltro === '' || empresa === empresaFiltro;

        fila.style.display = cumpleNombre && cumpleCedula && cumpleCargo && cumpleEmpresa ? '' : 'none';
    });
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
    let seleccionarTodos = document.getElementById('seleccionar_todos').checked;
    let checkboxes = document.querySelectorAll('#tabla_usuarios_multiple tr:not([style*="display: none"]) input[name="usuarios[]"]');

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
    let checkboxes = document.querySelectorAll('#tabla_usuarios_multiple tr:not([style*="display: none"]) input[name="usuarios[]"]');
    let todosSeleccionados = true;
    let ningunSeleccionado = true;

    if (!checkboxes.length) {
        document.getElementById('seleccionar_todos').checked = false;
        document.getElementById('seleccionar_todos').indeterminate = false;
        return;
    }

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
    let idCentroTrabajo = consulta('CentroTrabajo_asignar', 'list_Centrotrabajo_asignar');

    if (!idTurno || !fechaInicio || !fechaFin) {
        alertify.error('Debe seleccionar un turno y fechas de inicio y fin');
        return;
    }

    let diasLaborales = [];
    let diasSeleccionados = $('#dias_laborales').multipleSelect('getSelects');
    if (diasSeleccionados && diasSeleccionados.length > 0) {
        diasLaborales = diasSeleccionados;
    }

    let usuarios = Array.from(usuariosSeleccionados);

    // Mostrar loader o indicador de carga
    let btnAsignar = document.querySelector('button[onclick="asignarTurnoMultiple()"]');
    if (btnAsignar) {
        btnAsignar.disabled = true;
        btnAsignar.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Procesando...';
    }

    // Enviar datos para asignación múltiple
    fetch('../modelo/jornada_bitacora.php?band=save_programacion_turnos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            idTurno: idTurno,
            fechaInicio: fechaInicio,
            fechaFin: fechaFin,
            usuarios: usuarios,
            idCentroTrabajo: idCentroTrabajo,
            diasLaborales: diasLaborales,
            idUsuario: id_usuario
        })
    }).then(response => {
        if (!response.ok) {
            throw new Error('Error en la conexión con el servidor');
        }
        return response.json();
    }).then(data => {
        // Restaurar el botón
        if (btnAsignar) {
            btnAsignar.disabled = false;
            btnAsignar.innerHTML = 'Asignar Turnos';
        }

        // Respuesta según el formato de respuestaExito() o respuestaError()
        if (data && data.success === true) {
            // Extraer información relevante
            const exitosos = data.data && data.data.exitosos ? data.data.exitosos : 0;
            const fallidos = data.data && data.data.fallidos ? data.data.fallidos : 0;

            // Mostrar mensaje de éxito
            alertify.success(data.message || `Turnos asignados correctamente a ${exitosos} usuarios`);

            // Limpiar selecciones solo si hubo asignaciones exitosas
            if (exitosos > 0) {
                usuariosSeleccionados.clear();
                // Recargar tablas y componentes
                Promise.all([
                    get_Usuarios(''),                // Recargar lista de usuarios
                    cargarTurnosAsignados(),        // Recargar tabla de turnos asignados
                    Buscar_detalle_tt(),            // Actualizar detalles
                    buscar_asignados(),             // Actualizar asignados
                    get_turnos_user_activo()        // Actualizar turnos activos
                ]).catch(err => console.error('Error al actualizar componentes:', err));

                // Limpiar formulario
                document.getElementById('fecha_ini').value = '';
                document.getElementById('fecha_fin').value = '';
                $('#dias_laborales').multipleSelect('uncheckAll');
                document.getElementById('lista_turnos_a').selectedIndex = 0;
            }

            // Si hay información detallada de errores, mostrarla
            if (fallidos > 0 && data.data && data.data.resultados) {
                console.group('Detalles de usuarios con errores:');
                data.data.resultados.filter(r => !r.success).forEach(resultado => {
                    console.log(`Usuario ${resultado.idUsuario}: ${resultado.message}`);
                });
                console.groupEnd();
            }
        } else {
            // Respuesta de error
            alertify.error(data.message || 'Error al asignar turnos');

            // Mostrar detalles adicionales si existen
            if (data.data && typeof data.data === 'object') {
                console.error('Detalles del error:', data.data);
            }
        }
    }).catch(error => {
        // Restaurar el botón
        if (btnAsignar) {
            btnAsignar.disabled = false;
            btnAsignar.innerHTML = 'Asignar Turnos';
        }

        console.error('Error:', error);
        alertify.error('Error al comunicarse con el servidor. Por favor, inténtelo de nuevo.');
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

function validarFechas() {
    const fechaInicio = document.getElementById('fecha_ini');
    const fechaFin = document.getElementById('fecha_fin');

    // Convertir fechas a objetos Date y normalizar
    const fechaInicioDate = new Date(fechaInicio.value);
    const fechaFinDate = new Date(fechaFin.value);

    // Normalizar las fechas a medianoche UTC
    fechaInicioDate.setUTCHours(0, 0, 0, 0);
    fechaFinDate.setUTCHours(0, 0, 0, 0);

    // Establecer fecha mínima para fecha fin
    fechaFin.min = fechaInicio.value;

    // Comparar fechas
    if (fechaFinDate < fechaInicioDate) {
        fechaFin.value = '';
        alertify.error('La fecha final no puede ser menor a la fecha inicial');
        return;
    }

    // Si las fechas son válidas, llamar a la función dias
    dias();
}

async function dias() {
    let fecha_ini = document.getElementById("fecha_ini").value;
    let fecha_fin = document.getElementById("fecha_fin").value;

    // Validar que ambas fechas existan
    if (!fecha_ini || !fecha_fin) {
        return;
    }

    try {
        let url = "../modelo/jornada_bitacora.php?band=dias";
        let param = { fecha_ini: fecha_ini, fecha_fin: fecha_fin };
        let data = JSON.stringify(param);

        let response = await sendRequest(url, data);
        let datos = JSON.parse(response);

        $('#dias_laborales').find('option').remove();
        let opcion = '';

        $.each(datos, function (key, val) {
            opcion += '<option value="' + val.id + '">' + val.name + '</option>';
        });

        $('#dias_laborales').html(opcion);
        format_mulsipleselect();

    } catch (error) {
        console.error('Error en función dias:', error);
        alertify.error('Error al procesar las fechas');
    }
}


// Validaciones previas antes de eliminar programación de turno
/**
Codigo hecho por mario
Funcion: cargarTurnosAsignados consulta y renderiza las programaciones de turnos
asignadas por centro de trabajo, incluyendo acciones de editar y eliminar.
**/
async function cargarTurnosAsignados() {
    try {
        const contenedor = document.getElementById('div_turnos_asignados');
        if (contenedor) {
            contenedor.innerHTML = '<div class="text-center"><i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Cargando turnos asignados...</div>';
        }

        const response = await fetch('../modelo/jornada_bitacora.php?band=get_turnos_asignados', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ idUsuario: id_usuario })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener las programaciones');
        }

        const registros = data.data || [];
        let html = `
            <table id="tabla_turnos_asignados" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="text-center">Nombre</th>
                        <th class="text-center">Cédula</th>
                        <th class="text-center">Cargo</th>
                        <th class="text-center">Fecha Inicio</th>
                        <th class="text-center">Fecha Fin</th>
                        <th class="text-center">Hora Inicio</th>
                        <th class="text-center">Hora Fin</th>
                        <th class="text-center">Duración</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>`;

        if (registros.length === 0) {
            html += '<tr><td colspan="9" class="text-center">No hay turnos asignados para mostrar</td></tr>';
        } else {
            registros.forEach(item => {
                html += `<tr>
                    <td>${escapeHtml(item.nombre || '')}</td>
                    <td>${escapeHtml(item.cedula || '')}</td>
                    <td>${escapeHtml(item.cargo || 'NO ESPECIFICADO')}</td>
                    <td>${escapeHtml(item.fechaInicio || '')}</td>
                    <td>${escapeHtml(item.fechaFin || '')}</td>
                    <td>${escapeHtml(item.horaInicio || '')}</td>
                    <td>${escapeHtml(item.horaFin || '')}</td>
                    <td>${escapeHtml(item.duracion || '')}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm" onclick="editarProgramacionTurno('${item.idProgramacion}')" title="Editar turno">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="iniciarEliminacionProgramacionTurno('${item.idProgramacion}')" title="Eliminar turno">
                            <span class="glyphicon glyphicon-trash"></span>
                        </button>
                    </td>
                </tr>`;
            });
        }

        html += '</tbody></table>';
        if (contenedor) {
            contenedor.innerHTML = html;
        }

        if ($.fn.DataTable.isDataTable('#tabla_turnos_asignados')) {
            $('#tabla_turnos_asignados').DataTable().destroy();
        }

        if (registros.length > 0) {
            $('#tabla_turnos_asignados').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json'
                },
                ordering: true,
                searching: true,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                pageLength: 10,
                columnDefs: [{ orderable: false, targets: 8 }]
            });
        }
    } catch (error) {
        console.error('Error al cargar turnos asignados:', error);
        const contenedor = document.getElementById('div_turnos_asignados');
        if (contenedor) {
            contenedor.innerHTML = `<div class="alert alert-danger">Error al cargar los turnos asignados: ${escapeHtml(error.message)}</div>`;
        }
    }
}

/**
Codigo hecho por mario
Funcion: iniciarEliminacionProgramacionTurno valida advertencias y bloqueos antes
de confirmar la eliminacion de una programacion de turnos.
**/
async function iniciarEliminacionProgramacionTurno(idProgramacion) {
    if (!idProgramacion) {
        alertify.error('ID de programación inválido');
        return;
    }

    try {
        const response = await fetch('../modelo/jornada_bitacora.php?band=validate_delete_programacion_turno', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ idProgramacion })
        });

        const data = await response.json();

        if (!data.success) {
            alertify.error(data.message || 'No se pudo validar la eliminación');
            return;
        }
        const validation = data.data || {};

        if (validation.hardBlock) {
            swal('No se puede eliminar', validation.hardMessage || 'La programación está vinculada a registros activos.', 'error');
            return;
        }

        const content = document.createElement('div');
        const assignedUsers = validation.assignedUsers || [];
        if (assignedUsers.length) {
            const assignedEl = document.createElement('p');
            assignedEl.innerHTML = `<strong>Usuarios asignados:</strong> ${assignedUsers.join(', ')}`;
            content.appendChild(assignedEl);
        }
        if (validation.warnings && validation.warnings.length > 0) {
            const warningList = document.createElement('div');
            warningList.innerHTML = `<p>Advertencias antes de eliminar:</p><ul>${validation.warnings.map(w => `<li>${w}</li>`).join('')}</ul>`;
            content.appendChild(warningList);
        }
        const checkboxWrapper = document.createElement('div');
        checkboxWrapper.innerHTML = '<label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" id="delete-confirm-checkbox"> Confirmo que comprendo las advertencias y deseo continuar.</label>';
        content.appendChild(checkboxWrapper);

        swal({
            title: 'Eliminar programación',
            text: validation.softMessage || 'Esta acción eliminará la programación seleccionada.',
            icon: validation.warnings && validation.warnings.length > 0 ? 'warning' : 'info',
            content: content,
            buttons: ['Cancelar', 'Eliminar'],
            dangerMode: true
        }).then(async (confirmado) => {
            if (!confirmado) {
                return;
            }
            if (validation.warnings && validation.warnings.length > 0) {
                const checkbox = document.getElementById('delete-confirm-checkbox');
                if (!checkbox || !checkbox.checked) {
                    alertify.error('Debe confirmar las advertencias marcando la casilla.');
                    return;
                }
            }
            await ejecutarEliminacionProgramacionTurno(idProgramacion);
        });
    } catch (error) {
        console.error('Error al validar la eliminación:', error);
        alertify.error('Error al validar la eliminación del turno');
    }
}

async function ejecutarEliminacionProgramacionTurno(idProgramacion) {
    try {
        const response = await fetch('../modelo/jornada_bitacora.php?band=delete_programacion_turno', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ idProgramacion })
        });

        const data = await response.json();

        if (data.success) {
            alertify.success(data.message || 'Turno eliminado correctamente');
            cargarTurnosAsignados();
        } else {
            alertify.error(data.message || 'No se pudo eliminar el turno');
            console.error('Error al eliminar:', data);
        }
    } catch (error) {
        console.error('Error al eliminar la programación:', error);
        alertify.error('Error al eliminar la programación. Por favor, intente nuevamente.');
    }
}

// Función para editar programación de turno
async function editarProgramacionTurno(idProgramacion) {
    if (!idProgramacion) {
        alertify.error('ID de programación no válido');
        return;
    }

    try {
        // Mostrar un indicador de carga mientras se obtienen los datos
        // alertify.message('Cargando información...');

        // Obtener los detalles de la programación
        const response = await fetch('../modelo/jornada_bitacora.php?band=get_programacion_turno', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ idProgramacion: idProgramacion })
        });

        const data = await response.json();

        if (data.success) {
            // Asegurarse de que el modal esté disponible en el DOM antes de manipularlo
            const modal = $('#modalEditarTurno');

            // Preparar el modal y mostrarlo
            modal.on('shown.bs.modal', function () {
                // Este código se ejecutará una vez que el modal esté completamente visible
                mostrarModalEdicion(data.data);
            });

            // Mostrar el modal
            modal.modal('show');
        } else {
            alertify.error(data.message || 'Error al cargar la información del turno');
            console.error('Error al cargar la información:', data);
        }
    } catch (error) {
        console.error('Error al comunicarse con el servidor:', error);
        alertify.error('Error al comunicarse con el servidor. Por favor, inténtelo de nuevo.');
    }
}

// Función para mostrar modal de edición con los datos cargados
function mostrarModalEdicion(programacion) {
    try {
        // Acceder a los elementos solo después de que el modal esté completamente visible
        const idProgramacionEl = document.getElementById('edit_idProgramacion');
        const nombreUsuarioEl = document.getElementById('edit_nombreUsuario');
        const fechaInicioEl = document.getElementById('edit_fechaInicio');
        const fechaFinEl = document.getElementById('edit_fechaFin');
        const idCentroTrabajoEl = document.getElementById('edit_idCentroTrabajo');

        // Verificar que todos los elementos existen
        if (!idProgramacionEl || !nombreUsuarioEl || !fechaInicioEl || !fechaFinEl || !idCentroTrabajoEl) {
            console.error('Error: No se encontraron algunos elementos del formulario');
            alertify.error('Error al cargar el formulario de edición.');
            return;
        }

        // Asignar los valores a los campos
        idProgramacionEl.value = programacion.idProgramacion || '';
        nombreUsuarioEl.value = programacion.nombreUsuario || '';
        fechaInicioEl.value = programacion.fechaInicio || '';
        fechaFinEl.value = programacion.fechaFin || '';

        const detalles = programacion.detallesTurno || [];
        const turnoSeleccionado = detalles.length ? detalles[0].idTurnoRaw : (turnosDisponibles[0]?.id || '');
        llenarSelectTurnosAsignados(turnoSeleccionado);
        const warningText = document.getElementById('edit_warning_text');
        if (warningText) {
            warningText.textContent = `Este cambio afecta a ${programacion.nombreUsuario || 'el usuario asignado'}.`;
        }
        const confirmCheckbox = document.getElementById('edit_confirmar_cambios');
        if (confirmCheckbox) {
            confirmCheckbox.checked = false;
        }
        const validationMessages = document.getElementById('edit_validation_messages');
        if (validationMessages) {
            validationMessages.textContent = '';
        }

        // Cargar los centros de trabajo para el datalist
        list_CentroTrabajoEdit();

        // Establecer el centro de trabajo con un pequeño retraso para asegurar que el datalist esté cargado
        setTimeout(() => {
            if (idCentroTrabajoEl) {
                idCentroTrabajoEl.value = programacion.centroDeTrabajo || '';
            }
        }, 300);

    } catch (e) {
        console.error('Error al mostrar el modal de edición:', e);
        alertify.error('Error al preparar el formulario de edición.');
    }
}

// Función para cargar los centros de trabajo en el datalist de edición
/**
Codigo hecho por mario
Funcion: list_CentroTrabajoEdit carga el datalist de centros de trabajo del modal
de edicion usando el formato de respuesta actual del backend.
**/
function list_CentroTrabajoEdit() {
    const list_Centro = document.getElementById("list_CentroTrabajoEdit");
    if (!list_Centro) {
        console.error('No se encontró el elemento list_CentroTrabajoEdit');
        return;
    }

    // Limpiar opciones existentes
    list_Centro.innerHTML = '';

    // Hacer la petición para obtener los centros de trabajo
    fetch('../modelo/jornada_bitacora.php?band=get_CentrosDeTrabajo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ idUsuario: id_usuario, texto_Centro: "" })
    })
        .then(response => response.json())
        .then(data => {
            const centros = normalizarRespuestaLista(data);
            centros.forEach(item => {
                const option = document.createElement('option');
                option.value = item.name;
                option.setAttribute('data-id', item.id);
                list_Centro.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error al cargar los centros de trabajo:', error);
        });
}

// Función para guardar los cambios de la programación
async function guardarCambiosProgramacion() {
    // Obtener los valores del formulario
    const idProgramacion = document.getElementById('edit_idProgramacion').value;
    const fechaInicio = document.getElementById('edit_fechaInicio').value;
    const fechaFin = document.getElementById('edit_fechaFin').value;
    const idCentroTrabajo = consulta('edit_idCentroTrabajo', 'list_CentroTrabajoEdit');
    const turnoSeleccionado = document.getElementById('edit_turnoTipo')?.value;
    const confirmarCambios = document.getElementById('edit_confirmar_cambios')?.checked;
    const mensajeValidacion = document.getElementById('edit_validation_messages');

    if (!turnoSeleccionado) {
        alertify.error('Seleccione el turno que aplicará en esta programación');
        return;
    }

    if (!confirmarCambios) {
        alertify.error('Debe confirmar que comprende el impacto de los cambios');
        return;
    }

    if (mensajeValidacion) {
        mensajeValidacion.textContent = '';
    }

    // Validar datos
    if (!fechaInicio || !fechaFin || !idCentroTrabajo) {
        alertify.error('Por favor complete todos los campos obligatorios');
        return;
    }

    try {
        // Mostrar indicador de carga
        alertify.message('Guardando cambios...');

        const response = await fetch('../modelo/jornada_bitacora.php?band=update_programacion_turno', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                idProgramacion: idProgramacion,
                fechaInicio: fechaInicio,
                fechaFin: fechaFin,
                idCentroTrabajo: idCentroTrabajo,
                idTurno: turnoSeleccionado,
                confirmChanges: true
            })
        });

        const data = await response.json();

        if (data.success) {
            alertify.success(data.message || 'Cambios guardados correctamente');
            // Cerrar el modal
            $('#modalEditarTurno').modal('hide');
            // Recargar la tabla para reflejar los cambios
            cargarTurnosAsignados();
        } else {
            const fieldErrors = data.errors?.fieldErrors;
            if (fieldErrors && mensajeValidacion) {
                mensajeValidacion.innerHTML = formatearMensajesValidacion(fieldErrors);
            }
            alertify.error(data.message || 'Error al guardar los cambios');
            console.error('Error al guardar:', data);
        }
        } catch (error) {
            console.error('Error al comunicarse con el servidor:', error);
            alertify.error('Error al comunicarse con el servidor. Por favor, inténtelo de nuevo.');
        }
    }

function formatearMensajesValidacion(fieldErrors) {
    const friendlyNames = {
        fechaInicio: 'Fecha Inicio',
        fechaFin: 'Fecha Fin',
        idCentroTrabajo: 'Centro de Trabajo',
        idTurno: 'Turno',
        duracion: 'Duración',
        idProgramacion: 'Programación'
    };

    return Object.entries(fieldErrors)
        .map(([key, message]) => `<div><strong>${friendlyNames[key] || key}:</strong> ${message}</div>`)
        .join('');
}

// --------------------------------------------------

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
    let param = { idUsuario: id_usuario, texto_Centro: texto_Centro };
    let data = JSON.stringify(param);
    try {
        let response = await sendRequest(url, data);
        const json_parse = normalizarRespuestaLista(JSON.parse(response));
        list_Centro.innerHTML = '';
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
    let param = { idUsuario: id_usuario, texto_Centro: texto_Centro };
    let data = JSON.stringify(param);

    try {
        let response = await sendRequest(url, data);
        let json_parse = normalizarRespuestaLista(JSON.parse(response));
        list_Centro.innerHTML = '';
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

async function get_ConsultaCentroTrabajo() {
    try {
        // Mostrar indicador de carga
        document.getElementById('div_tabla_centrotrabajo').innerHTML =
            '<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando información...</p></div>';

        // Obtener valores para la consulta
        const fechaInicial = document.getElementById("FechaInicialMina").value;
        const fechaFinal = document.getElementById("FechaFinalMina").value;
        const cargo = get_data_id("CargoMina", "list_CargoMina");
        const usuario = get_data_id("UsuarioMina", "list_UsuarioMina");
        const centroTrabajo = get_data_id("CentroTrabajo_consulta", "list_Centrotrabajo_consulta");

        // Validar fechas (opcional)
        if (!fechaInicial || !fechaFinal) {
            alertify.error('Debe seleccionar un rango de fechas válido');
            return;
        }

        // Enviar petición al servidor
        const response = await fetch('../modelo/jornada_bitacora.php?band=get_ConsultaCentroTrabajo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fechaInicial: fechaInicial,
                fechaFinal: fechaFinal,
                cargo: cargo,
                usuario: usuario,
                idCentroTrabajo: centroTrabajo,
                idUsuario: id_usuario
            })
        });

        // Procesar respuesta
        const data = await response.json();

        // Detectar el formato de datos
        const esFormatoSimple = data.data && data.data.formatoSimple === true;

        // Construir la tabla con los datos recibidos según el formato
        let html;

        if (esFormatoSimple) {
            // Formato simple para fnObtenerHorasTrabajadas
            html = `
                <table id="tabla_jornadas" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">Nombre</th>
                            <th class="text-center">Apellido</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Hora Entrada</th>
                            <th class="text-center">Hora Salida</th>
                            <th class="text-center">Horas Trabajadas</th>
                        </tr>
                    </thead>
                    <tbody>`;

            if (data.success && data.data && data.data.registros && data.data.registros.length > 0) {
                // Agregar filas con datos para formato simple
                data.data.registros.forEach(item => {
                    html += `<tr>
                        <td>${item.id || ''}</td>
                        <td>${item.nombre || ''}</td>
                        <td>${item.apellido || ''}</td>
                        <td>${item.fecha || ''}</td>
                        <td>${item.horaEntrada || 'NO MARCADO'}</td>
                        <td>${item.horaSalida || 'NO MARCADO'}</td>
                        <td>${item.horasTrabajadas || ''}</td>
                    </tr>`;
                });
            } else {
                // Mensaje para tabla vacía
                html += `<tr>
                    <td class="text-center" colspan="7">No se encontraron registros para los criterios seleccionados</td>
                </tr>`;
            }
        } else {
            // Formato completo para SP_ReporteJornadasLaborales
            html = `
                <table id="tabla_jornadas" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Día</th>
                            <th class="text-center">Cargo</th>
                            <th class="text-center">Nombre</th>
                            <th class="text-center">Cédula</th>
                            <th class="text-center">Jornada</th>
                            <th class="text-center">Inicio Jornada</th>
                            <th class="text-center">Inicio Receso</th>
                            <th class="text-center">Total 1ra Jornada</th>
                            <th class="text-center">Fin Receso</th>
                            <th class="text-center">Fin Jornada</th>
                            <th class="text-center">Total 2da Jornada</th>
                            <th class="text-center">Tiempo Total</th>
                            <th class="text-center">Sobretiempo</th>
                        </tr>
                    </thead>
                    <tbody>`;

            if (data.success && data.data && data.data.registros && data.data.registros.length > 0) {
                // Agregar filas con datos para formato completo
                data.data.registros.forEach(item => {
                    html += `<tr>
                        <td>${item.fecha || ''}</td>
                        <td>${item.diaSemana || ''}</td>
                        <td>${item.cargo || 'NO ESPECIFICADO'}</td>
                        <td>${item.nombreTrabajador || ''}</td>
                        <td>${item.cedula || ''}</td>
                        <td>${item.jornada || ''}</td>
                        <td>${item.inicioJornada || 'NO MARCADO'}</td>
                        <td>${item.inicioReceso || 'NO MARCADO'}</td>
                        <td>${item.totalPrimeraJornada || 'N/A'}</td>
                        <td>${item.finReceso || 'NO MARCADO'}</td>
                        <td>${item.finJornada || 'NO MARCADO'}</td>
                        <td>${item.totalSegundaJornada || 'N/A'}</td>
                        <td>${item.tiempoTotalTrabajado || ''}</td>
                        <td>${item.sobretiempo || ''}</td>
                    </tr>`;
                });
            } else {
                // Mensaje para tabla vacía
                html += `<tr>
                    <td class="text-center" colspan="14">No se encontraron registros para los criterios seleccionados</td>
                </tr>`;
            }
        }

        html += `</tbody></table>`;

        // Actualizar el contenedor con la tabla
        document.getElementById('div_tabla_centrotrabajo').innerHTML = html;

        // Verificar si hay datos antes de inicializar DataTable
        if (data.success && data.data && data.data.registros && data.data.registros.length > 0) {
            // Destruir DataTable si ya existe
            if ($.fn.DataTable.isDataTable('#tabla_jornadas')) {
                $('#tabla_jornadas').DataTable().destroy();
            }

            // Inicializar DataTable con configuraciones según el formato
            $('#tabla_jornadas').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
                },
                "ordering": true,
                "searching": true,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                "pageLength": 10,
                "dom": 'Bfrtip',
                "buttons": [
                    'excel',
                    'pdf',
                    'print'
                ],
                "responsive": true,
                // Configurar cuál columna no es ordenable según el formato
                "columnDefs": esFormatoSimple ? [] : [
                    { "orderable": false, "targets": [8, 11] } // Las columnas de totales no son ordenables
                ]
            });

            // Mostrar contador de registros
            alertify.success(`Se encontraron ${data.data.totalRegistros} registros`);
        } else {
            // Para tablas sin datos, aplicar un estilo simple sin inicializar DataTable
            $('#tabla_jornadas').addClass('simple-table');

            // Mostrar mensaje de no resultados
            if (data.success) {
                alertify.message('No se encontraron registros para los criterios seleccionados');
            } else {
                alertify.error(data.message || 'Error al procesar la consulta');
            }
        }

    } catch (error) {
        console.error('Error al realizar la consulta:', error);
        document.getElementById('div_tabla_centrotrabajo').innerHTML =
            `<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                Error al cargar los datos. Por favor intente nuevamente.
            </div>`;
        alertify.error('Error al procesar la consulta. Por favor, intente de nuevo.');
    }
}

async function list_Centrotrabajo(object, contexto = 'consulta') {
    try {
        // 1. Resetear formulario si es necesario
        if (contexto === 'consulta') {
            $('#ButtonCancelar').hide();
            document.getElementById("idActividad").value = "";
            document.getElementById("Descripcion").value = "";
            document.getElementById("fecha_ini").value = "";
            $('#idTiquete').find('option').remove();

            // Resetear estado del botón
            $('#button')
                .attr('onclick', 'save();')
                .removeClass('btn-warning')
                .addClass('btn btn-primary')
                .text('Guardar');
        }

        // 2. Obtener elementos según el contexto
        const inputId = contexto === 'asignar' ? "CentroTrabajo_asignar" : "CentroTrabajo_consulta";
        const datalistId = contexto === 'asignar' ? "list_Centrotrabajo_asignar" : "list_Centrotrabajo_consulta";

        const inputCentro = document.getElementById(inputId);
        const list_Centro = document.getElementById(datalistId);

        if (!list_Centro || !inputCentro) {
            throw new Error(`Elementos necesarios no encontrados: ${inputId}, ${datalistId}`);
        }

        // 3. Mostrar indicador de carga
        list_Centro.innerHTML = '<option value="">Cargando centros de trabajo...</option>';
        inputCentro.disabled = true;

        // 4. Preparar parámetros
        const texto_Centro = object?.value || "";
        const url = "../modelo/jornada_bitacora.php?band=get_CentrosDeTrabajo";
        const param = {
            idUsuario: id_usuario,
            texto_Centro: texto_Centro
        };

        // 5. Realizar petición
        const response = await sendRequest(url, JSON.stringify(param));
        const data = JSON.parse(response);

        // 6. Validar respuesta
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener centros de trabajo');
        }

        // 7. Limpiar opciones existentes
        list_Centro.innerHTML = '';

        // 8. Procesar resultados según cantidad
        if (data.data.length === 1) {
            // Si solo hay un registro
            const unicoRegistro = data.data[0];
            const option = document.createElement('option');
            option.value = unicoRegistro.name;
            option.setAttribute('data-id', unicoRegistro.id);
            list_Centro.appendChild(option);

            // Establecer y deshabilitar el input
            inputCentro.value = unicoRegistro.name;
            inputCentro.disabled = true;

            // Opcional: Mostrar indicador visual
            inputCentro.classList.add('bg-light');

            // Opcional: Agregar tooltip
            inputCentro.title = 'Único centro de trabajo disponible';

        } else {
            // Si hay múltiples registros
            const fragment = document.createDocumentFragment();
            data.data.forEach(({ id, name }) => {
                if (!list_Centro.querySelector(`option[value="${name}"]`)) {
                    const option = document.createElement('option');
                    option.value = name;
                    option.setAttribute('data-id', id);
                    fragment.appendChild(option);
                }
            });
            list_Centro.appendChild(fragment);

            // Habilitar el input para selección
            inputCentro.disabled = false;
            inputCentro.classList.remove('bg-light');
            inputCentro.value = ''; // Limpiar valor previo
            inputCentro.title = 'Seleccione un centro de trabajo';
        }

    } catch (error) {
        console.error('Error al cargar centros de trabajo:', error);
        alertify.error('Error al cargar los centros de trabajo');

        // Restablecer elementos en caso de error
        const list_Centro = document.getElementById("list_Centrotrabajo");
        const inputCentro = document.getElementById("CentroTrabajo");

        if (list_Centro) {
            list_Centro.innerHTML = '<option value="">Error al cargar centros de trabajo</option>';
        }
        if (inputCentro) {
            inputCentro.disabled = false;
            inputCentro.value = '';
            inputCentro.classList.remove('bg-light');
        }
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

/* async function dias() {
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
} */

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
