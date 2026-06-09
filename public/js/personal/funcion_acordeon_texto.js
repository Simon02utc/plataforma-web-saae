const headers = document.querySelectorAll('.titulo-acordeon');

headers.forEach(header => {
    header.addEventListener('click', () => {
        // Buscamos el contenedor padre (.acordeon-item)
        const item = header.parentElement;
        
        // Alternamos la clase 'activo'
        item.classList.toggle('activo');
    });
});