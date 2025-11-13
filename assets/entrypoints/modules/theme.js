"use strict";

// Définition des variables
const themeToggle = document.getElementById("themeToggle");
const themeToggleOn = document.getElementById("iconOn");
const themeToggleOff = document.getElementById("iconOff");
const sun = document.getElementById("sun");
const moon = document.getElementById("moon");
const html = document.documentElement;
const badgeWSCarbon = document.getElementById("wcb");
const badgeEcoindex = document.getElementById("ecoindex-badge");
const logoImg = document.querySelector(".site-header__logo-img");
const logoSource = document.querySelector(".site-header__logo source");

// Fonction pour mettre à jour les logos
function updateLogos(isEco) {
    if (logoImg) {
        const imgSrc = isEco ? logoImg.dataset.eco : logoImg.dataset.normal;
        logoImg.src = imgSrc;
    }
    if (logoSource) {
        const sourceSrc = isEco ? logoSource.dataset.eco : logoSource.dataset.normal;
        logoSource.srcset = sourceSrc;
    }
}

// Fonction pour appliquer le thème eco
function applyEcoTheme() {
    html.classList.add("eco");
    themeToggleOn.classList.remove("hidden");
    themeToggleOff.classList.add("hidden");
    sun.classList.add("hidden");
    moon.classList.remove("hidden");
    updateLogos(true);
    if (badgeWSCarbon) {
        badgeWSCarbon.classList.add("wcb-d");
    }
    if (badgeEcoindex) {
        badgeEcoindex.setAttribute("data-theme", "dark");
    }
}

// Fonction pour retirer le thème eco
function removeEcoTheme() {
    html.classList.remove("eco");
    themeToggleOn.classList.add("hidden");
    themeToggleOff.classList.remove("hidden");
    sun.classList.remove("hidden");
    moon.classList.add("hidden");
    updateLogos(false);
    if (badgeWSCarbon) {
        badgeWSCarbon.classList.remove("wcb-d");
    }
    if (badgeEcoindex) {
        badgeEcoindex.removeAttribute("data-theme");
    }
}

// Récupération du thème stockée dans le local Storage
const themeStorage = localStorage.getItem("theme");

// Si l'utilisateur a fait un choix manuel, on le respecte
if (themeStorage === "eco") {
    applyEcoTheme();
} else if (themeStorage === null) {
    // Pas de préférence enregistrée : on vérifie les préférences du navigateur
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    if (prefersDark.matches) {
        applyEcoTheme();
    }
}

// Écouter les changements de préférences du navigateur en temps réel
// Ne s'active que si l'utilisateur n'a pas fait de choix manuel
const prefersDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');
prefersDarkQuery.addEventListener('change', (e) => {
    // Ne changer automatiquement que si aucune préférence manuelle n'est enregistrée
    if (localStorage.getItem("theme") === null) {
        if (e.matches) {
            applyEcoTheme();
        } else {
            removeEcoTheme();
        }
    }
});

// Application du thème selon le click du toggle
if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        const eco = html.classList.toggle("eco");
        themeToggleOn.classList.toggle("hidden", !eco);
        themeToggleOff.classList.toggle("hidden", eco);
        sun.classList.toggle("hidden", eco);
        moon.classList.toggle("hidden", !eco);
        updateLogos(eco);
        if (badgeWSCarbon) {
            badgeWSCarbon.classList.toggle("wcb-d", eco);
        }
        if (badgeEcoindex) {
            if (eco) {
                badgeEcoindex.setAttribute("data-theme", "dark");
            } else {
                badgeEcoindex.removeAttribute("data-theme");
            }
        }
        localStorage.setItem("theme", eco ? "eco" : "default");
    });
}
