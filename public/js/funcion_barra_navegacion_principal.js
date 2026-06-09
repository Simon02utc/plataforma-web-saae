let lastScrollTop = 0;
const navbar = document.querySelector('.header');

window.addEventListener('scroll', () => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTop > lastScrollTop  && scrollTop >35) {
        // Bajando = ocultar
        navbar.classList.add('hide');
    } else if (scrollTop < lastScrollTop) {
        // Subiendo = mostrar (pero solo si no esta en la parte superior)
        navbar.classList.remove('hide');
    }

    lastScrollTop = scrollTop;
});



/*=============== SHOW MENU ===============*/
const showMenu = (toggleId, navId) =>{
    const toggle = document.getElementById(toggleId),
        nav = document.getElementById(navId)

    toggle.addEventListener('click', () =>{
        // Add show-menu class to nav menu
        nav.classList.toggle('show-menu')

        // Add show-icon to show and hide the menu icon
        toggle.classList.toggle('show-icon')
    })
}

showMenu('nav-toggle','nav-menu')