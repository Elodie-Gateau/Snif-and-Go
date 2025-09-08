const toggle = document.querySelector(".site-header__burger");
const nav = document.querySelector(".site-header__nav");

if (toggle) {
    console.log("JS burger chargé");
    toggle.addEventListener("click", () => {
        nav.classList.toggle("hidden");
    });
} else {
    console.log("JS burger chargé mais pas de toggle trouvé");
}
