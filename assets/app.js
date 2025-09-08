import "./bootstrap.js";
import { initCityAutocomplete } from "./js/cities.js";
import "./js/newTrail.js";
import "./styles/app.css";
import "./js/trail-search.js";
import "./js/burger.js";
import "./js/flatpickr.js";

function init() {
    // ne bind que si le formulaire est présent
    const start = document.getElementById("trail_startCode");
    const end = document.getElementById("trail_endCode");
    if (start || end) {
        try {
            initCityAutocomplete();
            console.log("[cities] init ok");
        } catch (e) {
            console.error("[cities] init failed:", e);
        }
    } else {
        console.log("[cities] no form on page");
    }
}

// lance immédiatement si le DOM est déjà prêt
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
} else {
    init();
}

// Événements Turbo (au cas où)
document.addEventListener("turbo:render", init);
document.addEventListener("turbo:load", init);

// Avant mise en cache Turbo (safe)
document.addEventListener("turbo:before-cache", () => {
    ["trail_startCode", "trail_endCode"].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.replaceWith(el.cloneNode(true));
    });
});
