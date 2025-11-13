document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById("map");

    if (!el) {
        return;
    }

    if (typeof window.L === "undefined") {
        return;
    }

    const gpxUrl = el.dataset.gpx;

    if (!gpxUrl) {
        return;
    }

    // Centre par défaut sur la France en attendant le chargement du GPX
    const map = L.map(el).setView([46.603354, -1.888334], 13);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
        maxZoom: 19
    }).addTo(map);

    // Créer des icônes personnalisées avec les couleurs pour début/fin/waypoints
    const greenIcon = new L.Icon({
        iconUrl: '/images/markers/marker-green.png',
        shadowUrl: '/images/markers/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    const redIcon = new L.Icon({
        iconUrl: '/images/markers/marker-red.png',
        shadowUrl: '/images/markers/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    const blueIcon = new L.Icon({
        iconUrl: '/images/markers/marker-blue.png',
        shadowUrl: '/images/markers/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    new L.GPX(gpxUrl, {
        async: true,
        marker_options: {
            startIcon: greenIcon,
            endIcon: redIcon,
            wptIcons: {
                '': blueIcon  // Icône bleue pour tous les waypoints
            }
        }
    })
        .on("loaded", e => {
            map.fitBounds(e.target.getBounds());
        })
        .on("error", e => {
            console.error("Erreur de chargement du GPX:", e);
        })
        .addTo(map);
});
