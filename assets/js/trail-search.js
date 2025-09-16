const toggleButton = document.getElementById("toggle-advanced");
const advancedPanel = document.getElementById("trail-advanced");

if (toggleButton && advancedPanel) {
    toggleButton.addEventListener("click", () => {
        const hidden = advancedPanel.classList.toggle("hidden");
        const open = !hidden;
        console.log(open);
        if (open) {
            toggleButton.textContent = "Masquer les critères";
        } else {
            toggleButton.textContent = "Afficher plus de critères";
        }
    });
}

// const inputSearch = document.getElementById("search-trails-home");
// const countSearch = document.getElementById("search-result-count");
// if (inputSearch) {
//     inputSearch.addEventListener("keyup", function () {
//         console.log(inputSearch.value);
//         const query = this.value;
//         const baseUrl = inputSearch.dataset.url;
//         const url = `${baseUrl}?q=${encodeURIComponent(query)}`;
//         if (query.length >= 3) {
//             fetch(url, { headers: { Accept: "application/json" } })
//                 .then((res) => res.json())
//                 .then((results) => {
//                     const list = document.querySelector(
//                         ".home-user__trail-search--results"
//                     );
//                     list.innerHTML = "";
//                     countSearch.textContent = `${results.length} itinéraire${
//                         results.length > 1 ? "s" : ""
//                     } trouvé${results.length > 1 ? "s" : ""}`;
//                     console.log(results);
//                 });
//         }
//     });
// }
