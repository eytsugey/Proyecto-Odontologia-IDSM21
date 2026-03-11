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

  function storageKey() {
    return `odontograma_paciente_${window.pacienteActual || 1}`;
  }

  function obtenerDatos() {
    try { return JSON.parse(localStorage.getItem(storageKey())) || {}; }
    catch (e) { return {}; }
  }

  function guardarDatos(data) {
    localStorage.setItem(storageKey(), JSON.stringify(data));
  }

  function clasePorEstado(estado) {
    const permitidos = ["sano", "caries", "restaurado", "extraido", "fracturado", "endodoncia"];
    return permitidos.includes(estado) ? estado : "sano";
  }

  function crearDiente(numero) {
    const div = document.createElement("div");
    div.className = "diente sano";
    div.dataset.numero = numero;
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
    const data = obtenerDatos();
    document.querySelectorAll(".diente").forEach(d => {
      d.className = "diente";
      const numero = d.dataset.numero;
      const info = data[numero];
      d.classList.add(clasePorEstado(info?.estado || "sano"));
    });
  }

  function seleccionarDiente(numero) {
    dienteSeleccionado = numero;
    document.querySelectorAll(".diente").forEach(d => d.classList.remove("activo"));
    const actual = document.querySelector(`.diente[data-numero="${numero}"]`);
    if (actual) actual.classList.add("activo");

    const data = obtenerDatos();
    const info = data[numero] || { estado: "sano", descripcion: "" };
    numeroInput.value = numero;
    estadoInput.value = info.estado || "sano";
    descripcionInput.value = info.descripcion || "";
  }

  window.guardarDienteLocal = function () {
    if (!dienteSeleccionado) {
      alert("Selecciona un diente.");
      return;
    }

    const data = obtenerDatos();
    data[dienteSeleccionado] = {
      estado: estadoInput.value,
      descripcion: descripcionInput.value.trim()
    };

    guardarDatos(data);
    aplicarDatos();
    alert("Diente guardado.");
  };

  renderArcadas();
})();