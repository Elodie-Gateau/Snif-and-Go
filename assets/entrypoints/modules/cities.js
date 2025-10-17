function postalToCity(postalInputId, cityInputId, cityDatalistId) {
    const postalInput = document.getElementById(postalInputId);
    const cityInput = document.getElementById(cityInputId);
    const cityDatalist = document.getElementById(cityDatalistId);

    if (!postalInput || !cityInput || !cityDatalist) return;
    if (postalInput.dataset.bound === "1") return;
    postalInput.dataset.bound = "1";

    postalInput.addEventListener("input", () => {
        const code = postalInput.value.trim();
        if (code.length !== 5) return;

        fetch(
            `https://geo.api.gouv.fr/communes?codePostal=${code}&fields=nom&format=json`
        )
            .then(response => {
                if (!response.ok)
                    throw new Error(`Erreur API : ${response.status}`);
                return response.json();
            })
            .then(data => {
                cityDatalist.innerHTML = "";

                if (data.length === 0) {
                    const option = document.createElement("option");
                    option.value = "Aucune ville trouvée";
                    cityDatalist.appendChild(option);
                    return;
                }

                data.forEach(city => {
                    const option = document.createElement("option");
                    option.value = city.nom;
                    cityDatalist.appendChild(option);
                });
            })
            .catch(error => {
                console.error("Erreur lors de l'appel API :", error);
            });
    });
}

function initCityAutocomplete() {
    postalToCity("trail_startCode", "trail_startCity", "start-city-list");
    postalToCity("trail_endCode", "trail_endCity", "end-city-list");
}

initCityAutocomplete();
