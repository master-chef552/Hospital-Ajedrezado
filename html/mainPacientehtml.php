<?php
session_start();
if (empty($_SESSION['id_usuario'])) {
    header('Location: login.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pagina de citas</title>
  <link rel="icon" href="../imagenes/hospital-icon.png" type="image/png">
  <link rel="stylesheet" href="../css/mainPaciente.css">
</head>
<body>
  <div class="wrapper">
    <!-- Navbar -->
    <div class="navbar">
      <a href="mainPacientehtml.php" class="color">
        <div class="hospital-name">Hospital Ajedrezado</div>
      </a>
      <div class="profile-button">
        <a href="perfilPaciente.html">
          <img src="../imagenes/sesion.png" alt="Perfil">
        </a>
      </div>
    </div>

    <!-- Bienvenida -->
    <div class="welcome">
      <h1>Bienvenido</h1>
    </div>

    <div class="windows">
      <!-- Ver mis citas -->
      <div class="expandable-window" style="max-height: 1000px; padding: 1rem;">
        <div class="window-header">Ver mis citas</div>
        <div class="window-content" id="citasContainer">
          <p>Cargando tus citas…</p>
        </div>
      </div>

      <!-- Agendar nuevas citas -->
      <div class="expandable-window" style="max-height: 1000px; padding: 1rem;">
        <div class="window-header">Agendar nuevas citas</div>
        <div class="window-content">

          <div class="panel-body"> 

          <form id="agendarCitaForm" action="../php/mainPaciente.php?action=agendarCita" method="post">
            <div class="form-group">
            <label for="especialidadSelect">Selecciona especialidad:</label>
            <select id="especialidadSelect" required>
              <option value="">-- Elige una especialidad --</option>
            </select>
            </div>

            <div class="form-group">
            <label for="doctorSelect">Selecciona doctor:</label>
            <select name="id_medico" id="doctorSelect" required disabled>
              <option value="">-- Primero selecciona especialidad --</option>
            </select>
            </div>

            <div class="form-group-inline">
            <label for="fechaCita">Fecha de la cita:</label>
            <input type="date" id="fechaCita" name="fecha_cita" required>
            </div>

            <div class="form-group">
            <label for="horaCita">Hora de la cita:</label>
            <input type="time" id="horaCita" name="hora_cita" required>
            </div>

            <button type="submit">Solicitar cita</button>
            <p id="errorServidor" style="color: red; font-size: 14px; display: none;"></p>
          </form>
         </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // 1) Toggle de las ventanas
    document.querySelectorAll('.expandable-window .window-header').forEach(h => {
      h.addEventListener('click', () => {
        const c = h.nextElementSibling;
        c.style.maxHeight = c.style.maxHeight && c.style.maxHeight !== "0px"
          ? "0px"
          : c.scrollHeight + "px";
      });
    });

    // 2) Rango de fecha (48h–3m)
    (function(){
      const f = document.getElementById('fechaCita');
      const now = new Date();
      const min = new Date(now.getTime() + 48*3600*1000);
      const max = new Date(); max.setMonth(max.getMonth()+3);
      const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
      f.min = fmt(min);
      f.max = fmt(max);
      document.getElementById('agendarCitaForm')
        .addEventListener('submit', e => {
          const sel = new Date(f.value);
          sel.setHours(0,0,0,0);
          if (sel < new Date(f.min) || sel > new Date(f.max)) {
            e.preventDefault();
            alert("La fecha debe ser entre 48 hrs y 3 meses desde hoy.");
          }
        });
    })();

    // 3) Mostrar mensaje de error según ?error=
    (function(){
      const err = new URLSearchParams(location.search).get('error');
      if (!err) return;
      const msgs = {
        '1':'Debe completar todos los campos.',
        '2':'Fecha fuera de rango.',
        '3':'Horario inválido (8:00-18:00).',
        '4':'Ya tienes cita pendiente con este doctor.',
        '5':'Doctor no disponible en ese horario.'
      };
      const p = document.getElementById('errorServidor');
      p.textContent = msgs[err] || 'Error desconocido.';
      p.style.display = 'block';
      alert(msgs[err] || 'Error desconocido.');
    })();

    // Utilidad fetch JSON
    const fetchJSON = url => fetch(url, { credentials: 'include' })
      .then(r => r.ok ? r.json() : Promise.reject(r.statusText));


    // 4) Cargar y mostrar citas
    fetchJSON('../php/mainPaciente.php?action=getCitas')
      .then(showCitas)
      .catch(err => {
        document.getElementById('citasContainer')
          .innerHTML = `<p style="color:red;">${err}</p>`;
      });

    function showCitas(data) {
  const ct = document.getElementById('citasContainer');
  if (!data.length) {
    ct.innerHTML = "<p>No tienes citas registradas.</p>";
    return;
  }

  let html = `<table class="citas-table">
    <thead><tr>
      <th>Folio</th><th>Doctor</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Especialidad</th><th></th>
    </tr></thead><tbody>`;

  data.forEach(r => {
    const doctor = `${r.nombre_doctor} ${r.apellido_doctor}`;
    const [fecha, time] = r.fecha_cita.split(' ');
    const hora = time.slice(0, 5);

    
    let botonPagar = '';
    if (r.estado_cita === "Agendada pendiente de pago") {
      botonPagar = `<button type="submit" class="boton" data-id="${r.id_cita}" data-folio="${r.folio}" data-doctor="${doctor}" data-fecha="${fecha}" data-hora="${hora}" data-especialidad="${r.nombre_especialidad}">Pagar</button>`;
    }

    html += `<tr>
      <td data-label="Folio">${r.folio}</td>
      <td data-label="Doctor">${doctor}</td>
      <td data-label="Fecha">${fecha}</td>
      <td data-label="Hora">${hora}</td>
      <td data-label="Estatus">${r.estado_cita}</td>
      <td data-label="Especialidad">${r.nombre_especialidad}</td>
      <td data-label="Pagar">${botonPagar}</td>
    </tr>`;
  });

  ct.innerHTML = html + `</tbody></table>`;
}

//mandar el evento de click para el botón de pagar
document.addEventListener('click', function (e) {
  if (e.target.matches('.boton')) {
    const btn = e.target;

    // Crear formulario y agregar campos ocultos
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../php/reciboPDF.php'; // Ruta a tu recibo

    const campos = ['id', 'folio', 'doctor', 'fecha', 'hora', 'especialidad'];
    campos.forEach(campo => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = campo;
      input.value = btn.dataset[campo];
      form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
  }
});

    

    // 5) Cargar especialidades
    const selEsp = document.getElementById('especialidadSelect');
    fetchJSON('../php/mainPaciente.php?action=getEspecialidades')
      .then(data => {
        data.forEach(es => selEsp.add(new Option(es.nombre, es.id_especialidad)));
      })
      .catch(console.error);

    // 6) Al cambiar especialidad, cargar doctores
    const selDoc = document.getElementById('doctorSelect');
    selEsp.addEventListener('change', () => {
      const id = selEsp.value;
      selDoc.disabled = true;
      selDoc.innerHTML = '<option>Cargando…</option>';
      if (!id) {
        selDoc.innerHTML = '<option>-- Primero selecciona especialidad --</option>';
        return;
      }
      fetchJSON(`../php/mainPaciente.php?action=getDoctores&id_especialidad=${id}`)
        .then(data => {
          selDoc.innerHTML = '<option value="">-- Elige doctor --</option>';
          data.forEach(d => {
          const texto = `${d.nombre} ${d.ap_paterno} ${d.ap_materno}`;
          selDoc.add(new Option(texto, d.cedula)); 
      });
selDoc.disabled = false;

        })
        .catch(_ => {
          selDoc.innerHTML = '<option>Error cargando doctores</option>';
        });
    });
  });
  </script>
</body>
</html>
