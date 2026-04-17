(function () {
  const contenedor = document.getElementById("odontogramaSecciones");
  const numeroInput = document.getElementById("numeroDiente");
  const estadoInput = document.getElementById("estadoDiente");
  const descripcionInput = document.getElementById("descripcionDiente");
  const badgeTipo = document.getElementById("badgeTipoDenticion");

  if (!contenedor) return;

  const odontogramas = {
    permanente: {
      titulo: "Dentición permanente",
      filas: [
        { clase: "fila-1", dientes: [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25] },
        { clase: "fila-2", dientes: [26, 27, 28] },
        { clase: "fila-3", dientes: [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35] },
        { clase: "fila-4", dientes: [36, 37, 38] }
      ]
    },
    temporal: {
      titulo: "Dentición temporal",
      filas: [
        { clase: "fila-temp", dientes: [55, 54, 53, 52, 51, 61, 62, 63, 64, 65] },
        { clase: "fila-temp", dientes: [85, 84, 83, 82, 81, 71, 72, 73, 74, 75] }
      ]
    }
  };

  const estadosPermitidos = ["sano", "caries", "restaurado", "extraido", "fracturado", "endodoncia"];
  let dienteSeleccionado = null;
  let datos = {};

  function obtenerTipoDenticion(edad) {
    if (typeof edad !== "number" || Number.isNaN(edad)) return "permanente";
    if (edad <= 5) return "temporal";
    if (edad <= 12) return "mixta";
    return "permanente";
  }

  function obtenerSeccionesActivas() {
    const tipo = obtenerTipoDenticion(window.pacienteEdad);
    if (tipo === "mixta") return ["permanente", "temporal"];
    if (tipo === "temporal") return ["temporal"];
    return ["permanente"];
  }

  function clasePorEstado(estado) {
    return estadosPermitidos.includes(estado) ? estado : "sano";
  }

  function crearDienteItem(numero) {
    const item = document.createElement("div");
    item.className = "diente-item";

    const boton = document.createElement("div");
    boton.className = "diente sano";
    boton.dataset.numero = String(numero);
    boton.textContent = numero;
    boton.addEventListener("click", () => seleccionarDiente(numero));

    const estado = document.createElement("div");
    estado.className = "estado-label";
    estado.dataset.labelNumero = String(numero);
    estado.textContent = "sano";

    item.appendChild(boton);
    item.appendChild(estado);
    return item;
  }

  function crearSeccion(nombre) {
    const config = odontogramas[nombre];
    const seccion = document.createElement("section");
    seccion.className = "odontograma-seccion";

    const titulo = document.createElement("h3");
    titulo.className = "odontograma-titulo";
    titulo.textContent = config.titulo;
    seccion.appendChild(titulo);

    const grid = document.createElement("div");
    grid.className = "odontograma-grid";

    config.filas.forEach(fila => {
      const filaDiv = document.createElement("div");
      filaDiv.className = `fila-odontograma ${fila.clase}`;
      fila.dientes.forEach(numero => filaDiv.appendChild(crearDienteItem(numero)));
      grid.appendChild(filaDiv);
    });

    seccion.appendChild(grid);
    return seccion;
  }

  function renderOdontograma() {
    contenedor.innerHTML = "";
    obtenerSeccionesActivas().forEach(nombre => contenedor.appendChild(crearSeccion(nombre)));
    const tipo = obtenerTipoDenticion(window.pacienteEdad);
    if (badgeTipo) {
      badgeTipo.textContent = `Vista activa: ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
    }
    aplicarDatos();
  }

  function aplicarDatos() {
    document.querySelectorAll(".diente").forEach(diente => {
      const numero = diente.dataset.numero;
      const info = datos[numero] || { estado: "sano", descripcion: "" };
      const estado = clasePorEstado(info.estado);

      diente.className = "diente";
      diente.classList.add(estado);
      if (dienteSeleccionado === numero) {
        diente.classList.add("activo");
      }

      const label = document.querySelector(`.estado-label[data-label-numero="${numero}"]`);
      if (label) label.textContent = estado;
    });
  }

  function seleccionarDiente(numero) {
    dienteSeleccionado = String(numero);
    const info = datos[dienteSeleccionado] || { estado: "sano", descripcion: "" };

    numeroInput.value = dienteSeleccionado;
    estadoInput.value = clasePorEstado(info.estado);
    descripcionInput.value = info.descripcion || "";
    aplicarDatos();
  }

  async function cargarDesdeBD() {
    try {
      const pacienteId = window.pacienteActual;
      if (!pacienteId) return;

      const res = await fetch(`../api/odontograma.php?paciente_id=${encodeURIComponent(pacienteId)}`);
      const data = await res.json();
      if (!res.ok) {
        console.error(data);
        return;
      }
      datos = data || {};
      renderOdontograma();
    } catch (error) {
      console.error("Error al cargar odontograma:", error);
    }
  }

  window.guardarDienteBD = async function () {
    try {
      const pacienteId = window.pacienteActual;
      if (!pacienteId) return alert("No se encontró el paciente.");
      if (!dienteSeleccionado) return alert("Selecciona un diente.");

      const payload = {
        paciente_id: Number(pacienteId),
        diente: String(dienteSeleccionado),
        estado: estadoInput.value,
        descripcion: descripcionInput.value.trim()
      };

      const res = await fetch("../api/odontograma.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok) return alert(data.error || "No se pudo guardar.");

      datos[dienteSeleccionado] = {
        estado: payload.estado,
        descripcion: payload.descripcion
      };
      aplicarDatos();
      alert("Diente guardado correctamente.");
    } catch (error) {
      console.error("Error al guardar:", error);
      alert("Ocurrió un error al guardar.");
    }
  };

  window.recargarOdontograma = function () {
    dienteSeleccionado = null;
    numeroInput.value = "";
    estadoInput.value = "sano";
    descripcionInput.value = "";
    cargarDesdeBD();
  };

  cargarDesdeBD();
})();
