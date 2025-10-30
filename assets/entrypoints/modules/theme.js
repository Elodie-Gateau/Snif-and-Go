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

// Récupération du thème stockée dans le local Storage
const themeStorage = localStorage.getItem("theme");
// Si le thème enregistré était "eco" application du thème eco
if (themeStorage === "eco") {
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
