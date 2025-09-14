const adminUsers = document.getElementById("users-list");
const adminDogs = document.getElementById("dogs-list");
const adminTrails = document.getElementById("trails-list");
const adminWalks = document.getElementById("walks-list");
const btnUsersAdmin = document.getElementById("btn-users-list");
const btnDogsAdmin = document.getElementById("btn-dogs-list");
const btnTrailsAdmin = document.getElementById("btn-trails-list");
const btnWalksAdmin = document.getElementById("btn-walks-list");

if (btnUsersAdmin && btnDogsAdmin && btnTrailsAdmin && btnWalksAdmin) {
    btnUsersAdmin.addEventListener("click", (e) => {
        adminUsers.classList.remove("hidden");
        adminDogs.classList.add("hidden");
        adminTrails.classList.add("hidden");
        adminWalks.classList.add("hidden");
    });
    btnDogsAdmin.addEventListener("click", (e) => {
        adminUsers.classList.add("hidden");
        adminDogs.classList.remove("hidden");
        adminTrails.classList.add("hidden");
        adminWalks.classList.add("hidden");
    });
    btnTrailsAdmin.addEventListener("click", (e) => {
        adminUsers.classList.add("hidden");
        adminDogs.classList.add("hidden");
        adminTrails.classList.remove("hidden");
        adminWalks.classList.add("hidden");
    });
    btnWalksAdmin.addEventListener("click", (e) => {
        adminUsers.classList.add("hidden");
        adminDogs.classList.add("hidden");
        adminTrails.classList.add("hidden");
        adminWalks.classList.remove("hidden");
    });
}
