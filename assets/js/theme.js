// Définition des variables
const themeToggle = document.getElementById("themeToggle");
const themeToggleOn = document.getElementById("iconOn");
const themeToggleOff = document.getElementById("iconOff");
const sun = document.getElementById("sun");
const moon = document.getElementById("moon");
const html = document.documentElement;
const badgeWSCarbon = document.getElementById("wcb");

// Récupération du thème stockée dans le local Storage
const themeStorage = localStorage.getItem("theme");

// Si le thème enregistré était "eco" application du thème eco
if (themeStorage === "eco") {
    html.classList.add("eco");
    themeToggleOn.classList.remove("hidden");
    themeToggleOff.classList.add("hidden");
    sun.classList.add("hidden");
    moon.classList.remove("hidden");
    badgeWSCarbon.classList.add("wcb-d");
}

// Application du thème selon le click du toggle
if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        const eco = html.classList.toggle("eco");
        themeToggleOn.classList.toggle("hidden", !eco);
        themeToggleOff.classList.toggle("hidden", eco);
        sun.classList.toggle("hidden", eco);
        moon.classList.toggle("hidden", !eco);
        badgeWSCarbon.classList.toggle("wcb-d", eco);
        localStorage.setItem("theme", eco ? "eco" : "default");
    });
}
