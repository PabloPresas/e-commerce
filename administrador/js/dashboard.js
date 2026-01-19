


document.addEventListener("DOMContentLoaded", () => {

    const contenedor = document.getElementById("contenedor-admin");
    const titulo = document.getElementById("titulo-principal");

    const btnAgregar  = document.getElementById("btn-agregar");
    const btnStock    = document.getElementById("btn-stock");
    const btnUsuarios = document.getElementById("btn-usuarios");
    const btnVentas   = document.getElementById("btn-ventas");

    const modalStock   = document.getElementById("modal-stock");
    const inputCantidad = document.getElementById("modal-stock-cantidad");
    const btnCancelar  = document.getElementById("modal-stock-cancelar");
    const btnConfirmar = document.getElementById("modal-stock-confirmar");

    let productoSeleccionadoId = null;

    const limpiar = () => {
        document
            .querySelectorAll(".boton-categoria")
            .forEach(b => b.classList.remove("active"));
    };

    /* =====================
       MODAL STOCK
    ===================== */
    function abrirModalStock(id) {
        productoSeleccionadoId = id;
        inputCantidad.value = "";
        modalStock.classList.remove("hidden");
    }

    function cerrarModalStock() {
        modalStock.classList.add("hidden");
        productoSeleccionadoId = null;
        inputCantidad.value = "";
    }

    btnCancelar.onclick = cerrarModalStock;

    modalStock.onclick = e => {
        if (e.target === modalStock) cerrarModalStock();
    };

    btnConfirmar.onclick = () => {
        const cantidad = parseInt(inputCantidad.value);
        if (!cantidad || cantidad <= 0) return;

        fetch("/php/productos.php", {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                id: productoSeleccionadoId,
                cantidad
            })
        })
        .then(r => r.json())
        .then(() => {
            cerrarModalStock();
            btnStock.onclick();
        });
    };

    /* =====================
       AGREGAR PRODUCTO
    ===================== */
    btnAgregar.onclick = () => {
        limpiar();
        btnAgregar.classList.add("active");
        titulo.innerText = "Agregar producto";

        contenedor.innerHTML = `
            <form id="form-producto" class="formulario-admin" enctype="multipart/form-data">
                <input name="titulo" placeholder="Título" required>
                <input type="file" name="imagen" required>
                <select name="categoria" required>
                    <option value="">Categoría</option>
                    <option value="camisetas">Camisetas</option>
                    <option value="pantalones">Pantalones</option>
                    <option value="abrigos">Abrigos</option>
                </select>
                <input name="precio" type="number" placeholder="Precio" required>
                <input name="stock" type="number" placeholder="Stock inicial" required>
                <button class="btn-admin">Agregar</button>
            </form>
        `;

        document.getElementById("form-producto").onsubmit = e => {
            e.preventDefault();

            fetch("/php/productos.php", {
                method: "POST",
                body: new FormData(e.target)
            })
            .then(r => r.json())
            .then(resp => {
                if (resp && resp.success) {
                    alert("Producto agregado");
                    e.target.reset();
                } else {
                    alert("Error al agregar");
                }
            });
        };
    };

    /* =====================
       STOCK
    ===================== */
    btnStock.onclick = () => {
        limpiar();
        btnStock.classList.add("active");
        titulo.innerText = "Stock de productos";

        fetch("/php/productos.php")
            .then(r => r.json())
            .then(productos => {
                contenedor.innerHTML = `
                    <div class="stock-contenedor">
                        ${productos.map(p => `
                            <div class="stock-card">
                                <img src="${p.imagen}" alt="${p.titulo}">
                                <h3>${p.titulo}</h3>
                                <p>$${p.precio}</p>
                                <p class="stock-cantidad">Stock: ${p.stock}</p>
                                <small>${p.codigo}</small>
                                <button class="btn-admin btn-agregar-stock" data-id="${p.id}">
                                    Agregar stock
                                </button>
                            </div>
                        `).join("")}
                    </div>
                `;

                document
                    .querySelectorAll(".btn-agregar-stock")
                    .forEach(btn => {
                        btn.onclick = () => abrirModalStock(btn.dataset.id);
                    });
            });
    };

    /* =====================
       USUARIOS Y ROLES
    ===================== */
    btnUsuarios.onclick = () => {
        limpiar();
        btnUsuarios.classList.add("active");
        titulo.innerText = "Usuarios y Roles";

        fetch("/administrador/php/usuarios.php")
            .then(r => r.json())
            .then(usuarios => {
                contenedor.innerHTML = `
                    <div class="admin-card">
                        <h3>Usuarios</h3>
                        <table class="tabla-usuarios">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${usuarios.map(u => `
                                    <tr>
                                        <td data-label="ID">${u.id}</td>
                                        <td data-label="Nombre">${u.nombre}</td>
                                        <td data-label="Email">${u.email}</td>
                                        <td data-label="Rol">${u.rol}</td>
                                        <td class="acciones-cell" data-label="Acciones">
                                            <div class="acciones-botones">
                                                <button class="btn-accion btn-editar" type="button">Editar</button>
                                                <button class="btn-accion btn-borrar" type="button">Eliminar</button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join("")}
                            </tbody>
                        </table>
                    </div>

                    <form id="form-usuario" class="formulario-admin">
                        <h3>Agregar usuario</h3>
                        <input name="nombre" placeholder="Nombre" required>
                        <input name="email" type="email" placeholder="Email" required>
                        <input name="password" type="password" placeholder="Password" required>
                        <select name="rol" required>
                            <option value="admin">Admin</option>
                            <option value="vendedor">Vendedor</option>
                        </select>
                        <button class="btn-admin">Agregar usuario</button>
                    </form>
                `;

                document.getElementById("form-usuario").onsubmit = e => {
                    e.preventDefault();
                    const data = Object.fromEntries(new FormData(e.target));

                    fetch("/administrador/php/usuarios.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(data)
                    }).then(() => btnUsuarios.onclick());
                };
            });
    };

    /* =====================
       VENTAS PRO + FILTROS
    ===================== */
    function cargarVentas(mes, anio) {
        let url = "/administrador/php/ventas.php";
        if (mes && anio) url += `?mes=${encodeURIComponent(mes)}&anio=${encodeURIComponent(anio)}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {

                const resumen = data.resumen || [];
                const top = data.top || [];
                const totales = data.totales || { items: 0, facturado: 0 };

                contenedor.innerHTML = `
                    <div class="admin-card">
                        <h3>Ventas</h3>

                        <form id="form-filtro-ventas" class="formulario-admin" style="max-width: 900px; margin: 0 auto 1.2rem auto;">
                            <label>Filtrar por mes y año</label>

                            <select name="mes" required>
                                <option value="">Mes</option>
                                <option value="1">Enero</option>
                                <option value="2">Febrero</option>
                                <option value="3">Marzo</option>
                                <option value="4">Abril</option>
                                <option value="5">Mayo</option>
                                <option value="6">Junio</option>
                                <option value="7">Julio</option>
                                <option value="8">Agosto</option>
                                <option value="9">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>

                            <input name="anio" type="number" placeholder="Año (ej: 2026)" required>

                            <div style="display:flex; gap:.75rem; justify-content:center;">
                                <button class="btn-admin" type="submit">Aplicar filtro</button>
                                <button class="btn-admin btn-cancelar" type="button" id="btn-ventas-todas">Ver todo</button>
                            </div>
                        </form>

                        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
                            <div style="background: #fafafa; border: 1px solid var(--clr-gray); padding: 1rem; border-radius: .9rem;">
                                <strong style="color: var(--clr-main);">Total vendido</strong>
                                <div style="font-size: 1.2rem; font-weight: 800; color: var(--clr-main);">${totales.items}</div>
                            </div>
                            <div style="background: #fafafa; border: 1px solid var(--clr-gray); padding: 1rem; border-radius: .9rem;">
                                <strong style="color: var(--clr-main);">Total facturado</strong>
                                <div style="font-size: 1.2rem; font-weight: 800; color: var(--clr-main);">$${totales.facturado}</div>
                            </div>
                        </div>

                        <h3 style="margin-top: 1rem;">Top 5</h3>
                        ${top.length ? `
                            <table class="tabla-usuarios">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Vendida</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${top.map(v => `
                                        <tr>
                                            <td data-label="Producto">${v.codigo} - ${v.titulo}</td>
                                            <td data-label="Vendida">${v.total_vendida}</td>
                                            <td data-label="Total">$${v.total_facturado}</td>
                                        </tr>
                                    `).join("")}
                                </tbody>
                            </table>
                        ` : `<p>No hay ventas registradas.</p>`}

                        <h3 style="margin-top: 1rem;">Resumen por producto</h3>
                        ${resumen.length ? `
                            <table class="tabla-usuarios">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Vendida</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${resumen.map(v => `
                                        <tr>
                                            <td data-label="Producto">${v.codigo} - ${v.titulo}</td>
                                            <td data-label="Vendida">${v.total_vendida}</td>
                                            <td data-label="Total">$${v.total_facturado}</td>
                                        </tr>
                                    `).join("")}
                                </tbody>
                            </table>
                        ` : `<p>No hay ventas registradas.</p>`}
                    </div>
                `;

                // setea valores si vinieron en filtro
                const form = document.getElementById("form-filtro-ventas");
                if (data.filtro && data.filtro.mes && data.filtro.anio) {
                    form.mes.value = String(data.filtro.mes);
                    form.anio.value = String(data.filtro.anio);
                }

                form.onsubmit = (e) => {
                    e.preventDefault();
                    const mesSel = form.mes.value;
                    const anioSel = form.anio.value;
                    cargarVentas(mesSel, anioSel);
                };

                document.getElementById("btn-ventas-todas").onclick = () => {
                    cargarVentas(null, null);
                };
            });
    }

    btnVentas.onclick = () => {
        limpiar();
        btnVentas.classList.add("active");
        titulo.innerText = "Ventas";
        cargarVentas(null, null);
    };

});
