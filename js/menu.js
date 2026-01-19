


const openMenu = document.querySelector("#open-menu");
const closeMenu = document.querySelector("#close-menu");
const aside = document.querySelector("aside");
const botonesMenu = document.querySelectorAll(".boton-menu");

openMenu.addEventListener("click", () => {
    aside.classList.add("aside-visible");
});

closeMenu.addEventListener("click", () => {
    aside.classList.remove("aside-visible");
});

// 🔥 CLAVE: cerrar menú al tocar cualquier opción
botonesMenu.forEach(boton => {
    boton.addEventListener("click", () => {
        aside.classList.remove("aside-visible");
    });
});
