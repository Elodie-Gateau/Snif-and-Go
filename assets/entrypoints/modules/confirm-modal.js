document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("confirm-modal");
    const message = document.getElementById("confirm-message");
    const form = document.getElementById("confirm-form");
    const tokenEl = form.querySelector('input[name="_token"]');

    // Gestion focus (accessibilité)
    let lastActive = null;
    const open = () => {
        lastActive = document.activeElement;
        modal.classList.remove("hidden");
        modal.querySelector('[data-close="cancel"]').focus();
    };
    const close = () => {
        modal.classList.add("hidden");
        if (lastActive) lastActive.focus();
    };

    // Ouvrir depuis n’importe quel bouton/lien ayant .js-delete
    document.body.addEventListener("click", (e) => {
        const btn = e.target.closest(".js-delete");
        if (!btn) return;

        e.preventDefault();
        // Récupère les données
        const url = btn.dataset.url;
        const token = btn.dataset.token;
        const name = btn.dataset.name || "";

        // Injecte dans la modale
        message.textContent = name
            ? `Voulez-vous vraiment supprimer ${name} ?`
            : `Voulez-vous vraiment supprimer cet élément ?`;

        form.action = url;
        tokenEl.value = token;

        open();
    });

    // Fermer modale (overlay / bouton Annuler / Échap)
    modal.addEventListener("click", (e) => {
        if (
            e.target.dataset.close === "overlay" ||
            e.target.dataset.close === "cancel"
        )
            close();
    });
    document.addEventListener("keydown", (e) => {
        if (!modal.classList.contains("hidden") && e.key === "Escape") close();
    });
});
