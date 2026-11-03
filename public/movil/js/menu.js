// FIXA, portal en celular: abre y cierra el menú de la barra superior.
(() => {
    const barra = document.querySelector('.barra');
    const boton = barra?.querySelector('.barra-menu');
    if (!boton) return;

    const cerrar = () => {
        barra.classList.remove('barra-abierta');
        boton.setAttribute('aria-expanded', 'false');
        boton.setAttribute('aria-label', 'Abrir menú');
    };

    boton.addEventListener('click', () => {
        const abierto = barra.classList.toggle('barra-abierta');
        boton.setAttribute('aria-expanded', String(abierto));
        boton.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
    });

    // Al elegir una sección o tocar Escape, el menú se cierra solo.
    barra.querySelectorAll('.barra-enlaces a').forEach((enlace) => enlace.addEventListener('click', cerrar));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(); });
    window.matchMedia('(min-width: 52.0625rem)').addEventListener('change', cerrar);
})();
