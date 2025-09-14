const themeToggle = document.getElementById("themeToggle");
const themeToggleOn = document.getElementById("iconOn");
const themeToggleOff = document.getElementById("iconOff");
const sun = document.getElementById("sun");
const moon = document.getElementById("moon");
const html = document.documentElement;

const themeStorage = localStorage.getItem("theme");
if (themeStorage === "eco") {
    html.classList.add("eco");
    themeToggleOn.classList.remove("hidden");
    themeToggleOff.classList.add("hidden");
    sun.classList.add("hidden");
    moon.classList.remove("hidden");
}

if (themeToggle) {
    console.log("JS theme chargé");
    themeToggle.addEventListener("click", () => {
        const eco = html.classList.toggle("eco");
        themeToggleOn.classList.toggle("hidden", !eco);
        themeToggleOff.classList.toggle("hidden", eco);
        sun.classList.toggle("hidden", eco);
        moon.classList.toggle("hidden", !eco);

        localStorage.setItem("theme", eco ? "eco" : "default");
    });
} else {
    console.log("JS theme chargé mais pas de bouton trouvé");
}
