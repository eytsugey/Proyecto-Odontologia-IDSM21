let dienteSeleccionado = null;
const pacienteActual = 1; // temporal

document.addEventListener("DOMContentLoaded", () => {
  generarOdontograma();
  conectarEventosUI();
  cargarOdontograma(); // si quieres cargar de BD
});

function conectarEventosUI(){
  // Cuando cambias el estado, pinta inmediatamente el diente seleccionado (sin guardar)
  const estadoSelect = document.getElementById("estadoDiente");
  estadoSelect.addEventListener("change", () => {
    if(!dienteSeleccionado) return;
    const estado = estadoSelect.value;
    // Mantén la descripción actual en UI
    const desc = document.getElementById("descripcionDiente")?.value?.trim() || "";
    pintarDiente(dienteSeleccionado, estado, desc, true); // true = solo UI
  });

  // Si escribes descripción, la guardamos en el dataset del diente (solo UI) para que no se pierda
  const descEl = document.getElementById("descripcionDiente");
  if(descEl){
    descEl.addEventListener("input", () => {
      if(!dienteSeleccionado) return;
      const el = getDienteEl(dienteSeleccionado);
      if(!el) return;
      el.dataset.descripcion = descEl.value;
    });
  }
}

function generarOdontograma(){
  const sup = document.getElementById("arcadaSuperior");
  const inf = document.getElementById("arcadaInferior");

  for(let i=1;i<=16;i++) sup.appendChild(crearDiente(i));
  for(let i=17;i<=32;i++) inf.appendChild(crearDiente(i));
}

function crearDiente(numero){
  const d = document.createElement("div");
  d.className = "diente sano";
  d.innerText = numero;
  d.dataset.estado = "sano";
  d.dataset.descripcion = "";

  d.addEventListener("click", () => seleccionarDiente(numero));
  return d;
}

function seleccionarDiente(numero){
  dienteSeleccionado = numero;

  // quitar selección a todos
  document.querySelectorAll(".diente").forEach(x => x.classList.remove("selected"));

  const el = getDienteEl(numero);
  if(el) el.classList.add("selected");

  // cargar a panel lo que tenga ese diente (BD o UI)
  document.getElementById("estadoDiente").value = el?.dataset.estado || "sano";
  const descEl = document.getElementById("descripcionDiente");
  if(descEl) descEl.value = el?.dataset.descripcion || "";
}

function pintarDiente(numero, estado, descripcion, soloUI = false){
  const el = getDienteEl(numero);
  if(!el) return;

  // Limpia clases de estado y pone la nueva
  el.classList.remove("sano","caries","restaurado","extraido","fracturado","endodoncia");
  el.classList.add(estado);

  // Guarda en dataset (sirve para mostrar en panel aunque no guardes)
  el.dataset.estado = estado;
  el.dataset.descripcion = descripcion || "";
  el.setAttribute("data-estado", estado); // para la mini etiqueta

  // soloUI no hace nada extra, solo indica intención
}

function getDienteEl(numero){
  return [...document.querySelectorAll(".diente")]
    .find(d => parseInt(d.innerText) === numero);
}

/* =========================
   GUARDAR (API) - opcional
========================= */
function guardarDiente(){
  if(!dienteSeleccionado) return alert("Selecciona un diente");

  const id_historial = parseInt(document.getElementById("idHistorial")?.value || "0");
  if(!id_historial) return alert("Pon el ID del historial (id_historial)");

  const estado = document.getElementById("estadoDiente").value;
  const descripcion = document.getElementById("descripcionDiente")?.value?.trim() || "";

  // Nota: el color YA CAMBIÓ por UI, aquí solo guardamos en BD
  fetch("../api/odontograma.php",{
    method:"POST",
    headers:{ "Content-Type":"application/json" },
    body: JSON.stringify({ id_historial, numero_diente: dienteSeleccionado, estado, descripcion })
  })
  .then(r=>r.json())
  .then(res=>{
    if(!res.success) return alert(res.message || "Error al guardar");
    alert("Guardado en BD");
  });
}

function cargarOdontograma(){
  fetch(`../api/odontograma.php?id_paciente=${pacienteActual}`)
    .then(r=>r.json())
    .then(res=>{
      if(!res.success) return;

      // reset UI
      document.querySelectorAll(".diente").forEach(d=>{
        d.classList.remove("caries","restaurado","extraido","fracturado","endodoncia");
        d.classList.add("sano");
        d.dataset.estado = "sano";
        d.dataset.descripcion = "";
        d.setAttribute("data-estado","sano");
      });

      // pinta lo que venga de BD (vista_odontograma)
      res.data.forEach(item=>{
        pintarDiente(parseInt(item.numero_diente), item.estado, item.descripcion || "", true);
      });
    });
}