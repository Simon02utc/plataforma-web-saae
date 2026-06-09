(function () {
    const track = document.getElementById('heroStats');
    const dotsEl = document.getElementById('statsDots');
    if (!track || !dotsEl) return;

    // Solo activa en telefonos
    function isMobile() { return window.innerWidth <= 1118; }

    //genera los dots segun cuantos .stat haya
    const stats = track.querySelectorAll('.stat');
    stats.forEach((_, i) => {
        const d = document.createElement('span');
        d.className = 'stats-dot' + (i === 0 ? ' active' : '');
        d.addEventListener('click', () => scrollToStat(i));
        dotsEl.appendChild(d);
    });

    const dots = dotsEl.querySelectorAll('.stats-dot');

    function scrollToStat(index) {
        if (!isMobile()) return;
        const target = stats[index];
        if (!target) return;
        track.scrollTo({ left: target.offsetLeft, behavior: 'smooth' });
    }

    function updateDots() {
        if (!isMobile()) return;
        //el stat activo es el mas cercano al borde izquierdo
        let closest = 0;
        let minDist = Infinity;
        stats.forEach((s, i) => {
            const dist = Math.abs(s.getBoundingClientRect().left - track.getBoundingClientRect().left);
            if (dist < minDist) { minDist = dist; closest = i; }
        });
        dots.forEach((d, i) => d.classList.toggle('active', i === closest));
    }

    track.addEventListener('scroll', updateDots, { passive: true });

    //auto-scroll cada 2.8 s
    let current = 0;
    let autoTimer = null;

    function startAuto() {
        if (!isMobile()) return;
        autoTimer = setInterval(() => {
            current = (current + 1) % stats.length;
            scrollToStat(current);
        }, 2800);
    }

    function stopAuto() {
        clearInterval(autoTimer);
    }

    //pausa si el usuario toca manualmente
    track.addEventListener('touchstart', stopAuto, { passive: true });
    track.addEventListener('touchend', () => { updateDots(); startAuto(); }, { passive: true });

    //reinicia al cambiar tamaño de ventana
    window.addEventListener('resize', () => {
        stopAuto();
        if (isMobile()) startAuto();
    });

    startAuto();
})();