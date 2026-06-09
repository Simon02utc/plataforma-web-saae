document.addEventListener('DOMContentLoaded', function () {

    const botonFlotante = document.getElementById('boton-flotante');
    const btnSubir = document.getElementById('btn-subir');

    btnSubir.addEventListener('click', scrollToTop);

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    window.addEventListener('scroll', function () {
        if (window.scrollY > 200) {
            botonFlotante.style.display = 'flex';
        } else {
            botonFlotante.style.display = 'none';
        }
    });

});