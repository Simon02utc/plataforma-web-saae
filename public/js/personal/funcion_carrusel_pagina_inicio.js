document.addEventListener('DOMContentLoaded', () => {
    // Soporta múltiples carruseles en la página
    document.querySelectorAll('.carousel').forEach((carousel) => {
        const imgs = Array.from(carousel.querySelectorAll('img'));
        if (imgs.length <= 1) {
            if (imgs[0]) imgs[0].classList.add('is-active');
            return;
        }

        // (Opcional) crea botones Prev/Next sin tocar tu HTML
        const prevBtn = document.createElement('button');
        prevBtn.className = 'carousel-btn prev';
        prevBtn.type = 'button';
        prevBtn.setAttribute('aria-label', 'Imagen anterior');
        prevBtn.textContent = '‹';

        const nextBtn = document.createElement('button');
        nextBtn.className = 'carousel-btn next';
        nextBtn.type = 'button';
        nextBtn.setAttribute('aria-label', 'Siguiente imagen');
        nextBtn.textContent = '›';

        carousel.appendChild(prevBtn);
        carousel.appendChild(nextBtn);

        let index = 0;
        let timer = null;
        const INTERVAL_MS = 3000; /*Intervalo de segundos*/

        function show(i) {
            index = (i + imgs.length) % imgs.length;
            imgs.forEach((img, idx) => img.classList.toggle('is-active', idx === index));
        }

        function start() {
            stop();
            timer = setInterval(() => show(index + 1), INTERVAL_MS);
        }

        function stop() {
            if (timer) clearInterval(timer);
            timer = null;
        }

        // Inicial
        show(0);
        start();

        // Controles
        prevBtn.addEventListener('click', () => { show(index - 1); start(); });
        nextBtn.addEventListener('click', () => { show(index + 1); start(); });

        // Pausa al pasar el mouse
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);

        // Swipe básico (móvil)
        let startX = null;
        carousel.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
        carousel.addEventListener('touchend', (e) => {
            if (startX === null) return;
            const endX = e.changedTouches[0].clientX;
            const dx = endX - startX;
            startX = null;

            if (Math.abs(dx) > 40) {
                show(dx > 0 ? index - 1 : index + 1);
                start();
            }
        }, { passive: true });
    });
});