(function () {
  const grid = document.getElementById("odontogramaGrid");
  const numeroInput = document.getElementById("numeroDiente");
  const estadoInput = document.getElementById("estadoDiente");
  const descripcionInput = document.getElementById("descripcionDiente");

  if (!grid) return;

  const filas = [
    {
      clase: "fila-1",
      dientes: [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25]
    },
    {
      clase: "fila-2",
      dientes: [26, 27, 28]
    },
    {
      clase: "fila-3",
      dientes: [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35]
    },
    {
      clase: "fila-4",
      dientes: [36, 37, 38]
    }
  ];

  const estadosPermitidos = ["sano", "caries", "restaurado", "extraido", "fracturado", "endodoncia"];

  let dienteSeleccionado = null;
  let datos = {};

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

  function renderOdontograma() {
    grid.innerHTML = "";

    filas.forEach(fila => {
      const filaDiv = document.createElement("div");
      filaDiv.className = `fila-odontograma ${fila.clase}`;

      fila.dientes.forEach(numero => {
        filaDiv.appendChild(crearDienteItem(numero));
      });

      grid.appendChild(filaDiv);
    });

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
      if (label) {
        label.textContent = estado;
      }
    });
  }

  function seleccionarDiente(numero) {
    dienteSeleccionado = String(numero);

    const info = datos[dienteSeleccionado] || {
      estado: "sano",
      descripcion: ""
    };

    numeroInput.value = numero;
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

      if (!pacienteId) {
        alert("No se encontró el paciente.");
        return;
      }

      if (!dienteSeleccionado) {
        alert("Selecciona un diente.");
        return;
      }

      const payload = {
        paciente_id: Number(pacienteId),
        diente: String(dienteSeleccionado),
        estado: estadoInput.value,
        descripcion: descripcionInput.value.trim()
      };

      const res = await fetch("../api/odontograma.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (!res.ok) {
        alert(data.error || "No se pudo guardar.");
        return;
      }

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

  renderOdontograma();
  cargarDesdeBD();
})();