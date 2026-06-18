document.addEventListener("DOMContentLoaded", function () {
    inactivityTime();
});


var colors = [
    [27, 57, 106], // principal
    [0, 119, 182], // azul eléctrico
    [0, 150, 199], // cian
    [72, 202, 228], // celeste
    [2, 62, 138], // azul oscuro
    [0, 180, 216], // turquesa
    [10, 29, 59] // principal extra oscuro
];

var step = 0;
var colorIndices = [0, 1, 2, 3];
var gradientSpeed = 0.006;
var inactive = false;
var gradientInterval = null;

function updateGradient() {

    const gradient = document.getElementById("gradient");

    if (!gradient) {
        return;
    }

    var c0_0 = colors[colorIndices[0]];
    var c0_1 = colors[colorIndices[1]];
    var c1_0 = colors[colorIndices[2]];
    var c1_1 = colors[colorIndices[3]];

    var istep = 1 - step;

    var r1 = Math.round(istep * c0_0[0] + step * c0_1[0]);
    var g1 = Math.round(istep * c0_0[1] + step * c0_1[1]);
    var b1 = Math.round(istep * c0_0[2] + step * c0_1[2]);

    var color1 = `rgb(${r1},${g1},${b1})`;

    var r2 = Math.round(istep * c1_0[0] + step * c1_1[0]);
    var g2 = Math.round(istep * c1_0[1] + step * c1_1[1]);
    var b2 = Math.round(istep * c1_0[2] + step * c1_1[2]);

    var color2 = `rgb(${r2},${g2},${b2})`;

    gradient.style.background =
        `linear-gradient(to right, ${color1}, ${color2})`;

    step += gradientSpeed;

    if (step >= 1) {

        step %= 1;

        colorIndices[0] = colorIndices[1];
        colorIndices[2] = colorIndices[3];

        colorIndices[1] =
            (colorIndices[1] +
                Math.floor(1 + Math.random() * (colors.length - 1)))
            % colors.length;

        colorIndices[3] =
            (colorIndices[3] +
                Math.floor(1 + Math.random() * (colors.length - 1)))
            % colors.length;
    }
}

function showInactiveScreen() {

    const gradient = document.getElementById("gradient");

    if (!gradient) {
        return;
    }

    inactive = true;

    gradient.style.display = "block";

    if (!gradientInterval) {
        gradientInterval = setInterval(updateGradient, 16);
    }
}

function hideInactiveScreen() {

    const gradient = document.getElementById("gradient");

    if (!gradient) {
        return;
    }

    inactive = false;

    gradient.style.display = "none";

    if (gradientInterval) {
        clearInterval(gradientInterval);
        gradientInterval = null;
    }
}

function inactivityTime() {

    let time;

    function logout() {

        if (!inactive) {
            showInactiveScreen();
        }
    }

    function resetTimer() {

        clearTimeout(time);

        if (inactive) {
            hideInactiveScreen();
        }

        // 10 minutos = 600000
        time = setTimeout(logout, 600000);
        // Para pruebas usar: setTimeout(logout, 1000);
    }

    [
        'mousemove',
        'mousedown',
        'keypress',
        'scroll',
        'touchstart',
        'click'
    ].forEach(event => {
        document.addEventListener(event, resetTimer, true);
    });

    resetTimer();
}