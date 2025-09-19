const toggle = document.querySelector(".site-header__burger");
const nav = document.querySelector(".site-header__nav");

if (toggle) {
    toggle.addEventListener("click", () => {
        nav.classList.toggle("hidden");
    });
}
