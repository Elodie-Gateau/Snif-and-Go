const inputModeSelectGpx = document.getElementById("trail_inputMode_0");
const inputModeSelectManuel = document.getElementById("trail_inputMode_1");
const gpxSection = document.querySelector("[data-section='gpx']");
const manualSection = document.querySelector("[data-section='manual']");
const circuitBoucle = document.getElementById("trail_circuit_type_1");

if (inputModeSelectGpx || inputModeSelectManuel) {
    inputModeSelectGpx.addEventListener("click", () => {
        gpxSection.classList.remove("hidden");
        manualSection.classList.add("hidden");
    });

    inputModeSelectManuel.addEventListener("click", () => {
        gpxSection.classList.add("hidden");
        manualSection.classList.remove("hidden");
    });
}

if (circuitBoucle) {
    circuitBoucle.addEventListener("change", () => {
        if (inputModeSelectGpx) {
            inputModeSelectGpx.checked = true;
        }

        gpxSection.classList.remove("hidden");
        manualSection.classList.add("hidden");
        const error = document.createElement("p");
        error.textContent =
            "Pour ajouter un itinéraire en boucle, merci de télécharger un fichier GPX";
        gpxSection.appendChild(error);
    });
}
