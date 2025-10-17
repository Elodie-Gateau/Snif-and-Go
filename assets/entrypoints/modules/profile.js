const profileDogs = document.getElementById("dogs");
const profileTrails = document.getElementById("trails");
const profileWalks = document.getElementById("walks");
const btnDogs = document.getElementById("btn-dogs");
const btnTrails = document.getElementById("btn-trails");
const btnWalks = document.getElementById("btn-walks");

if (btnDogs && btnTrails && btnWalks) {
    btnDogs.addEventListener("click", (e) => {
        profileDogs.classList.remove("hidden");
        profileTrails.classList.add("hidden");
        profileWalks.classList.add("hidden");
    });
    btnTrails.addEventListener("click", (e) => {
        profileDogs.classList.add("hidden");
        profileTrails.classList.remove("hidden");
        profileWalks.classList.add("hidden");
    });
    btnWalks.addEventListener("click", (e) => {
        profileDogs.classList.add("hidden");
        profileTrails.classList.add("hidden");
        profileWalks.classList.remove("hidden");
    });
}
