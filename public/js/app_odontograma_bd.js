(function () {
  const arcadaSuperior = document.getElementById("arcadaSuperior");
  const arcadaInferior = document.getElementById("arcadaInferior");
  const numeroInput = document.getElementById("numeroDiente");
  const estadoInput = document.getElementById("estadoDiente");
  const descripcionInput = document.getElementById("descripcionDiente");

  if (!arcadaSuperior || !arcadaInferior) return;

  const dientesSuperiores = [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28];
  const dientesInferiores = [48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];

  let dienteSeleccionado = null;
  let datos = {};

  function clasePorEstado(estado) {
    const permitidos = ["sano", "caries", "restaurado", "extraido", "fracturado", "endodoncia"];
    return permitidos.includes(estado) ? estado : "sano";
  }

  function crearDiente(numero) {
    const div = document.createElement("div");
    div.className = "diente sano";
    div.dataset.numero = String(numero);
    div.innerHTML = `<span>${numero}</span>`;
    div.addEventListener("click", () => seleccionarDiente(numero));
    return div;
  }

  function renderArcadas() {
    arcadaSuperior.innerHTML = "";
    arcadaInferior.innerHTML = "";

    dientesSuperiores.forEach(n => arcadaSuperior.appendChild(crearDiente(n)));
    dientesInferiores.forEach(n => arcadaInferior.appendChild(crearDiente(n)));

    aplicarDatos();
  }

  function aplicarDatos() {
    document.querySelectorAll(".diente").forEach(d => {
      d.className = "diente";
      const numero = d.dataset.numero;
      const info = datos[numero];
      d.classList.add(clasePorEstado(info && info.estado ? info.estado : "sano"));
    });
  }

  function seleccionarDiente(numero) {
    dienteSeleccionado = String(numero);

    document.querySelectorAll(".diente").forEach(d => d.classList.remove("activo"));

    const actual = document.querySelector(`.diente[data-numero="${numero}"]`);
    if (actual) actual.classList.add("activo");

    const info = datos[dienteSeleccionado] || { estado: "sano", descripcion: "" };

    numeroInput.value = numero;
    estadoInput.value = info.estado || "sano";
    descripcionInput.value = info.descripcion || "";
  }

  async function cargarDesdeBD() {
    try {
      const pacienteId = window.pacienteActual;

      if (!pacienteId) {
        console.error("pacienteActual no definido");
        return;
      }

      const res = await fetch(`../api/odontograma.php?paciente_id=${encodeURIComponent(pacienteId)}`);
      const data = await res.json();

      if (!res.ok) {
        console.error("Error al cargar odontograma:", data);
        return;
      }

      datos = data || {};
      renderArcadas();
    } catch (error) {
      console.error("Error en cargarDesdeBD:", error);
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

      console.log("Enviando payload:", payload);

      const res = await fetch("../api/odontograma.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (!res.ok) {
        console.error("Respuesta backend:", data);
        alert(data.error || "No se pudo guardar.");
        return;
      }

      datos[dienteSeleccionado] = {
        estado: payload.estado,
        descripcion: payload.descripcion
      };

      aplicarDatos();
      alert("Diente guardado en la base de datos.");
    } catch (error) {
      console.error("Error al guardar:", error);
      alert("Ocurrió un error al guardar.");
    }
  };

  renderArcadas();
  cargarDesdeBD();
})();