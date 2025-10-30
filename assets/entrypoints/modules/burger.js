"use strict";

const toggle = document.querySelector(".site-header__burger");
const nav = document.querySelector(".site-header__nav");

if (toggle) {
    toggle.addEventListener("click", function() {
        const expanded = toggle.getAttribute("aria-expanded") === "true";
        toggle.setAttribute("aria-expanded", !expanded);
        nav.classList.toggle("hidden");
    });
}
