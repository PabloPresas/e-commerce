


let productosEnCarrito = localStorage.getItem("productos-en-carrito");
productosEnCarrito = JSON.parse(productosEnCarrito) || [];

const contenedorCarritoVacio     = document.querySelector("#carrito-vacio");
const contenedorCarritoProductos = document.querySelector("#carrito-productos");
const contenedorCarritoAcciones  = document.querySelector("#carrito-acciones");
const contenedorCarritoComprado  = document.querySelector("#carrito-comprado");

let botonesEliminar = document.querySelectorAll(".carrito-producto-eliminar");

const botonVaciar    = document.querySelector("#carrito-acciones-vaciar");
const contenedorTotal = document.querySelector("#total");
const botonComprar   = document.querySelector("#carrito-acciones-comprar");

/* =====================
   CARGAR CARRITO
===================== */
function cargarProductosCarrito() {

    if (productosEnCarrito.length > 0) {

        contenedorCarritoVacio.classList.add("disabled");
        contenedorCarritoProductos.classList.remove("disabled");
        contenedorCarritoAcciones.classList.remove("disabled");
        contenedorCarritoComprado.classList.add("disabled");

        contenedorCarritoProductos.innerHTML = "";

        productosEnCarrito.forEach(producto => {

            const div = document.createElement("div");
            div.classList.add("carrito-producto");
            div.innerHTML = `
                <img class="carrito-producto-imagen" src="${producto.imagen}" alt="${producto.titulo}">
                <div class="carrito-producto-titulo">
                    <small>Título</small>
                    <h3>${producto.titulo}</h3>
                </div>
                <div class="carrito-producto-cantidad">
                    <small>Cantidad</small>
                    <p>${producto.cantidad}</p>
                </div>
                <div class="carrito-producto-precio">
                    <small>Precio</small>
                    <p>$${producto.precio}</p>
                </div>
                <div class="carrito-producto-subtotal">
                    <small>Subtotal</small>
                    <p>$${producto.precio * producto.cantidad}</p>
                </div>
                <button class="carrito-producto-eliminar" id="${producto.id}">
                    <i class="bi bi-trash-fill"></i>
                </button>
            `;
            contenedorCarritoProductos.append(div);
        });

        actualizarBotonesEliminar();
        actualizarTotal();

    } else {
        contenedorCarritoVacio.classList.remove("disabled");
        contenedorCarritoProductos.classList.add("disabled");
        contenedorCarritoAcciones.classList.add("disabled");
        contenedorCarritoComprado.classList.add("disabled");
    }
}

cargarProductosCarrito();

/* =====================
   ELIMINAR PRODUCTO
===================== */
function actualizarBotonesEliminar() {
    botonesEliminar = document.querySelectorAll(".carrito-producto-eliminar");
    botonesEliminar.forEach(boton => {
        boton.addEventListener("click", eliminarDelCarrito);
    });
}

function eliminarDelCarrito(e) {
    const idBoton = e.currentTarget.id;
    const index = productosEnCarrito.findIndex(p => p.id == idBoton);

    productosEnCarrito.splice(index, 1);
    localStorage.setItem("productos-en-carrito", JSON.stringify(productosEnCarrito));
    cargarProductosCarrito();
}

/* =====================
   VACIAR CARRITO
===================== */
botonVaciar.addEventListener("click", () => {
    Swal.fire({
        title: "¿Estás seguro?",
        icon: "question",
        html: `Se van a borrar ${productosEnCarrito.reduce(
            (acc, p) => acc + p.cantidad, 0
        )} productos.`,
        showCancelButton: true,
        confirmButtonText: "Sí",
        cancelButtonText: "No"
    }).then(result => {
        if (result.isConfirmed) {
            productosEnCarrito = [];
            localStorage.setItem("productos-en-carrito", "[]");
            cargarProductosCarrito();
        }
    });
});

/* =====================
   TOTAL
===================== */
function actualizarTotal() {
    const totalCalculado = productosEnCarrito.reduce(
        (acc, producto) => acc + (producto.precio * producto.cantidad),
        0
    );
    contenedorTotal.innerText = `$${totalCalculado}`;
}

/* =====================
   COMPRAR (CHECKOUT REAL)
===================== */
botonComprar.addEventListener("click", comprarCarrito);

function comprarCarrito() {

    if (!productosEnCarrito.length) return;

    fetch("/administrador/php/checkout.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            items: productosEnCarrito
        })
    })
    .then(r => r.json())
    .then(res => {

        if (!res.success) {
            Swal.fire(
                "Error",
                res.error || "No se pudo completar la compra",
                "error"
            );
            return;
        }

        // limpiar carrito
        productosEnCarrito = [];
        localStorage.setItem("productos-en-carrito", "[]");

        contenedorCarritoVacio.classList.add("disabled");
        contenedorCarritoProductos.classList.add("disabled");
        contenedorCarritoAcciones.classList.add("disabled");
        contenedorCarritoComprado.classList.remove("disabled");
    })
    .catch(() => {
        Swal.fire("Error", "Error de servidor", "error");
    });
}
