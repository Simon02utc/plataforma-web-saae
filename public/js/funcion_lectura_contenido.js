// Variables globales para rastrear qué se está leyendo actualmente
let botonActivo = null;
let contenedorActivo = null;
let colaLectura = [];
let indiceLectura = 0;
let reproduciendo = false;

// limpiar estilos visuales de un boton/contenedor
function limpiarEstados(btn, cont) {
    if (btn) {
        btn.classList.remove('leyendo-contenido');
        btn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
    }
    if (cont) {
        cont.classList.remove('leyendo-contenido');
    }

    window.getSelection().removeAllRanges();
    colaLectura = [];
    indiceLectura = 0;
    reproduciendo = false;
}

// cancelar todo
function detenerLectura() {
    window.speechSynthesis.cancel();
    limpiarEstados(botonActivo, contenedorActivo);
    botonActivo = null;
    contenedorActivo = null;
}

// forzar carga de voces en Chrome/Edge
if (window.speechSynthesis) {
    speechSynthesis.onvoiceschanged = () => {
        console.log('✅ Voces de síntesis cargadas');
    };
    speechSynthesis.getVoices();
}

// limpiar y mejorar puntuación
function prepararTextoParaLectura(texto) {
    return texto
        .replace(/\s+/g, ' ')              // colapsar espacios múltiples
        .replace(/\s+([,.;:!?])/g, '$1')   // quitar espacio antes de signos
        .replace(/([,.;:!?])(?!\s)/g, '$1 ') // asegurar espacio después
        .replace(/\n+/g, '. ')             // saltos de línea como pausa
        .trim();
}

// dividir texto en fragmentos para que respete pausas
function dividirEnFragmentos(texto) {
    // Divide conservando signos de puntuación
    const partes = texto.match(/[^.;:!?]+[.;:!?]?/g) || [];
    
    return partes
        .map(p => p.trim())
        .filter(Boolean);
}

// obtener voz
function obtenerVoz() {
    const voces = window.speechSynthesis.getVoices();

    const vozPreferida = voces.find(v =>
        v.name === 'Microsoft Dalia Online (Natural) - Spanish (Mexico)' &&
        v.lang === 'es-MX'
    );

    if (vozPreferida) {
        console.log('🎙️ Usando voz:', vozPreferida.name);
        return vozPreferida;
    }

    const fallback = voces.find(v => v.lang === 'es-MX');
    if (fallback) {
        console.log('🎙️ Voz preferida no disponible, usando:', fallback.name);
        return fallback;
    }

    console.log('⚠️ Sin voz es-MX disponible, usando predeterminada');
    return null;
}

// leer siguiente fragmento
function leerSiguienteFragmento() {
    if (!reproduciendo || indiceLectura >= colaLectura.length) {
        limpiarEstados(botonActivo, contenedorActivo);
        botonActivo = null;
        contenedorActivo = null;
        return;
    }

    const fragmento = colaLectura[indiceLectura];
    const utterance = new SpeechSynthesisUtterance(fragmento);

    utterance.lang = 'es-MX';
    utterance.rate = 1.0;
    utterance.pitch = 0.9;
    utterance.volume = 1.0;

    const voz = obtenerVoz();
    if (voz) {
        utterance.voice = voz;
    }

    utterance.onend = () => {
        indiceLectura++;

        // Pausa artificial leve entre fragmentos
        setTimeout(() => {
            leerSiguienteFragmento();
        }, 20);
    };

    utterance.onerror = (e) => {
        console.error('Error en lectura:', e);
        detenerLectura();
    };

    window.speechSynthesis.speak(utterance);
}

document.querySelectorAll('.btn-leer-contenido').forEach(boton => {
    boton.addEventListener('click', function () {
        const nuevoContenedor = this.closest('.contenedor-informacion-leer');

        // si se presionó el mismo botón, detener
        if (this === botonActivo && this.classList.contains('leyendo-contenido')) {
            detenerLectura();
            return;
        }

        // si había otro leyendo, detenerlo
        if (botonActivo && botonActivo !== this) {
            detenerLectura();
        }

        const elementosATexto = nuevoContenedor.querySelectorAll('.texto-objetivo-leer');
        let textoCompleto = '';

        elementosATexto.forEach(el => {
            const clone = el.cloneNode(true);
            clone.querySelectorAll('i').forEach(icono => icono.remove());

            // textContent suele ser más estable para lectura que innerText
            textoCompleto += clone.textContent + '\n';
        });

        textoCompleto = prepararTextoParaLectura(textoCompleto);

        if (!textoCompleto.trim()) return;

        colaLectura = dividirEnFragmentos(textoCompleto);
        indiceLectura = 0;
        reproduciendo = true;

        botonActivo = this;
        contenedorActivo = nuevoContenedor;

        nuevoContenedor.classList.add('leyendo-contenido');
        this.classList.add('leyendo-contenido');
        this.innerHTML = '<i class="fa-solid fa-stop"></i>';

        leerSiguienteFragmento();
    });
});

// Cancelar lectura al recargar, cerrar pestaña o navegar
window.addEventListener('beforeunload', () => {
    window.speechSynthesis.cancel();
});

window.addEventListener('pagehide', () => {
    window.speechSynthesis.cancel();
});