/*--------------------Mostrar mensajes estilo toast------------------------*/
function displayMensajeToast(htmlMessage, options = {}) {
    const container = document.getElementById('mensaje-toast');
    if (!container) return;

    const duration = options.duration ?? 7000;
    const theme = 'tema-toast';
    const animation = 'bounce';

    const toast = document.createElement('div');
    toast.className = `toast ${theme}`;

    const closeBtnToast = document.createElement('span');
    closeBtnToast.className = 'btn-cerrar-mensaje-toast';
    closeBtnToast.setAttribute('type', 'span');
    closeBtnToast.setAttribute('aria-label', 'Cerrar mensaje');
    closeBtnToast.innerHTML = '&times;';

    const icon = document.createElement('i');
    icon.className = `${resolveToastIcon(htmlMessage, options.type)}`;

    const textWrap = document.createElement('div');
    textWrap.className = 'toast-text';
    textWrap.innerHTML = htmlMessage;

    toast.appendChild(icon);
    toast.appendChild(textWrap);
    toast.appendChild(closeBtnToast);
    container.appendChild(toast);

    // Animación de entrada
    toast.style.animationName = animation;
    toast.style.animationDuration = '0.6s';
    toast.style.animationTimingFunction = 'cubic-bezier(0.25, 1.5, 0.5, 1)';
    toast.style.animationFillMode = 'both';

    let isClosing = false;

    const closeToast = () => {
        if (isClosing) return;
        isClosing = true;

        clearTimeout(autoCloseTimer);

        //quitar la animacion de entrada para que no se pelee con la salida
        toast.style.animation = 'none';

        //forzar reflow para que el navegador aplique el cambio anterior
        void toast.offsetWidth;

        //transicion de salida
        toast.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-50px) scale(0.96)';

        setTimeout(() => {
            toast.remove();
        }, 350);
    };

    closeBtnToast.addEventListener('click', closeToast);

    const autoCloseTimer = setTimeout(closeToast, duration);
}

function resolveToastIcon(htmlMessage, forcedType = null) {
    const type = forcedType || detectToastType(htmlMessage);

    const icons = {
        exito: 'fa-regular fa-circle-check icono-exito',
        error: 'fa-regular fa-circle-xmark icono-error',
        advertencia: 'ri-error-warning-line icono-advertencia',
        warning: 'fa-solid fa-triangle-exclamation icono-advertencia',
        info: 'fa-solid fa-circle-info icono-info',
        default: 'fa-regular fa-circle'
    };

    return icons[type] || icons.default;
}

function detectToastType(htmlMessage) {
    const html = String(htmlMessage).toLowerCase();

    if (html.includes('class="exito"') || html.includes("class='exito'")) return 'exito';
    if (html.includes('class="error"') || html.includes("class='error'")) return 'error';
    if (html.includes('class="advertencia"') || html.includes("class='advertencia'")) return 'advertencia';
    if (html.includes('class="warning"') || html.includes("class='warning'")) return 'warning';
    if (html.includes('class="info"') || html.includes("class='info'")) return 'info';

    return 'default';
}
/*-----------------------------------------------------------------------*/