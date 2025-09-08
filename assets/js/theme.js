const themeToggle = document.getElementById("themeToggle");
const themeToggleOn = document.getElementById("iconOn");
const themeToggleOff = document.getElementById("iconOff");
const html = document.documentElement;

if (themeToggle) {
    console.log("JS theme chargé");
    themeToggle.addEventListener("click", () => {
        const eco = html.classList.toggle("eco");
        themeToggleOn.classList.toggle("hidden", !eco);
        themeToggleOff.classList.toggle("hidden", eco);
    });
} else {
    console.log("JS theme chargé mais pas de bouton trouvé");
}
