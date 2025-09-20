const el = document.getElementById("map");

if (el) {
    document.addEventListener("DOMContentLoaded", () => {
        if (typeof window.L === "undefined") return;

        const el = document.getElementById("map");
        if (!el) return;

        const gpxUrl = el.dataset.gpx;
        if (!gpxUrl) return;

        const map = L.map(el);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors",
        }).addTo(map);

        new L.GPX(gpxUrl, {
            async: true,
            marker_options: {
                startIconUrl:
                    "https://unpkg.com/leaflet-gpx/pin-icon-start.png",
                endIconUrl: "https://unpkg.com/leaflet-gpx/pin-icon-end.png",
                shadowUrl: "https://unpkg.com/leaflet-gpx/pin-shadow.png",
            },
        })
            .on("loaded", (e) => map.fitBounds(e.target.getBounds()))
            .addTo(map);
    });
}
