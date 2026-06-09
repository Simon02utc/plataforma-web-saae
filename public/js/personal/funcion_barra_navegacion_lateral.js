document.addEventListener('DOMContentLoaded', () => {
    const cloud = document.getElementById('btn-logo-barra');
    const barraLateral = document.querySelector('.barra-lateral');
    const spans = document.querySelectorAll('.barra-lateral span');
    const btnAccionBarraLateral = document.querySelectorAll('.btn-acciones-barra-lateral');
    const toggles = document.querySelectorAll('.barra-lateral [data-submenu-toggle]');
    const menu = document.querySelector('.menu');
    const main = document.querySelector('main');


    //---------SUBMENU DESPLEGABLE----------
    toggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();

            const li = toggle.closest('.has-submenu');
            if (!li) return;

            //Cerrar todos los demas
            document.querySelectorAll('.barra-lateral li.has-submenu.open').forEach(openLi => {
                if (openLi !== li) {
                    openLi.classList.remove('open');
                    const a = openLi.querySelector('[data-submenu-toggle]');
                    if (a) a.setAttribute('aria-expanded', 'false');
                }
            });

            //Alternar el actual
            const isOpen = li.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });


    //----------MODO OSCURO (persistente)----------
    const palancaDark = document.querySelector('.modo-oscuro .switch');
    const circuloDark = document.querySelector('.modo-oscuro .circulo');

    const setDarkMode = (enabled) => {
        document.body.classList.toggle('dark-mode', enabled);
        if (circuloDark) circuloDark.classList.toggle('prendido', enabled);
        localStorage.setItem('darkMode', enabled ? '1' : '0');
    };

    //Cargar preferencia al entrar a cualquier pagina
    const savedDark  = localStorage.getItem('darkMode');
    if (savedDark  !== null) {
        setDarkMode(savedDark  === '1');
    } else {
        // opcional: si no hay guardado, usa el tema del sistema
        const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches;
        setDarkMode(!!prefersDark);
    }

    if (palancaDark) {
        palancaDark.addEventListener('click', () => {
        const enabled = !document.body.classList.contains('dark-mode');
        setDarkMode(enabled);
        });
    }


    //----------VISTA DE ESCRITORIO (para moviles)----------
    const viewportMeta = document.getElementById('viewport-meta') || document.querySelector('meta[name="viewport"]');
    const palancaDesktop = document.querySelector('.modo-escritorio .switch');
    const baseDesktop = document.querySelector('.modo-escritorio .base');
    const circuloDesktop = document.querySelector('.modo-escritorio .circulo');

    const NORMAL_VIEWPORT = 'width=device-width, initial-scale=1.0';
    const DESKTOP_VIEWPORT = 'width= 1119'; //Importante que sea este tamaño como maximo 1119 para la aplicacion de sus CSS y evitar conflictos con los CSS para pantallas de celular,

    const setDesktopMode = (enabled, reload = false) => {
        document.body.classList.toggle('desktop-mode', enabled); //opcional (por si se llega a poner CSS extra)
        if (circuloDesktop) circuloDesktop.classList.toggle('prendido', enabled);
        if (baseDesktop) baseDesktop.classList.toggle('prendido', enabled);

        localStorage.setItem('desktopMode', enabled ? '1' : '0');

        if (viewportMeta) {
        viewportMeta.setAttribute('content', enabled ? DESKTOP_VIEWPORT : NORMAL_VIEWPORT);
        }

        //IMPORTANTE: muchos navegadores aplican bien el cambio solo con recarga
        if (reload) {
        location.reload();
        }
    };

    //cargar preferencia al entrar
    const savedDesktop = localStorage.getItem('desktopMode');
    if (savedDesktop !== null) {
        setDesktopMode(savedDesktop === '1', false);
    }

    //click para alternar
    if (palancaDesktop) {
        palancaDesktop.addEventListener('click', () => {
        const enabled = localStorage.getItem('desktopMode') !== '1';
        setDesktopMode(enabled, true); //recarga para que se aplique perfecto
        });
    }



    //----------BARRA LATERAL----------
    if (menu && barraLateral) {
        menu.addEventListener('click', () => {
        barraLateral.classList.toggle('max-barra-lateral');

        if (menu.children?.length >= 2) {
            if (barraLateral.classList.contains('max-barra-lateral')) {
            menu.children[0].style.display = 'none';
            menu.children[1].style.display = 'block';
            } else {
            menu.children[0].style.display = 'block';
            menu.children[1].style.display = 'none';
            }
        }

        if (window.innerWidth <= 320 && main) {
            barraLateral.classList.add('mini-barra-lateral');
            main.classList.add('min-main');
            spans.forEach((span) => span.classList.add('oculto'));
        }
        });
    }

    if (cloud && barraLateral && main) {
        cloud.addEventListener('click', () => {
            barraLateral.classList.toggle('mini-barra-lateral');
            
            /*Cuando se vuelva mini, cierra submenus abiertos (para que no se queden “open”)*/
            if (barraLateral.classList.contains('mini-barra-lateral')) {
                document.querySelectorAll('.barra-lateral li.has-submenu.open').forEach(li => {
                    li.classList.remove('open');
                    const a = li.querySelector('[data-submenu-toggle]');
                    if (a) a.setAttribute('aria-expanded', 'false');
                });
            }

            main.classList.toggle('min-main');
            spans.forEach((span) => span.classList.toggle('oculto'));
        });
    }



    //---Movimiento del nombre del usuario o correo, el cual esta en sesion de  ---> {{ auth('personal')->user()?->nombre ?? '' }}
    var usernameElement = document.querySelector('.username');
    var iconNomElement = document.querySelector('.info-usuario');

    if (usernameElement && iconNomElement && usernameElement.scrollWidth > iconNomElement.clientWidth) {
        usernameElement.classList.add('scrollText');
    }
    //------------------------------------------------------------------------------------------------------------------------

});