const slides = document.querySelectorAll('.slide');
const totalSlides = slides.length;
let currentIndex = 0;

function showSlide(index) {
    const slidesContainer = document.querySelector('.slides');
    const slideWidth = slides[0].clientWidth;
    slidesContainer.style.transform = `translateX(-${index * slideWidth}px)`;

    //aSctualiza los puntos de navegacion
    document.querySelectorAll('.slider-dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
}

//ss<ider automatico
function startSlider() {
    setInterval(() => {
        currentIndex = (currentIndex + 1) % totalSlides;
        showSlide(currentIndex);
    }, 4000); //sambia la imagen cada 4 segundos
}

//crear puntos de navegacion
function createDots() {
    const dotsContainer = document.createElement('div');
    dotsContainer.classList.add('slider-dots');
    slides.forEach((_, i) => {
        const dot = document.createElement('div');
        dot.classList.add('slider-dot');
        if (i === 0) dot.classList.add('active'); //activa el primer punto
        dot.addEventListener('click', () => {
            currentIndex = i;
            showSlide(currentIndex);
        });
        dotsContainer.appendChild(dot);
    });
    document.querySelector('.content-slider').appendChild(dotsContainer);
}

createDots();
startSlider();
