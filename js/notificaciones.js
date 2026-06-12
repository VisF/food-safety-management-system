function mostrarToast(mensaje, tipo = "success") {

    const toast = document.createElement("div");

    toast.className = `toast toast-${tipo}`;

    const iconos = {
        success: "✔",
        error: "✖",
        warning: "⚠",
        info: "ℹ"
    };

    toast.innerHTML = `
        ${iconos[tipo] || ""}
        ${mensaje}
    `;

    document
        .getElementById("toast-container")
        .appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 4300);
}

console.log("Notificaciones cargadas");