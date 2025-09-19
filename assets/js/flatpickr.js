import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { French } from "flatpickr/dist/l10n/fr.js";

const sel = ".add-walk__form-input-date";
const inputs = document.querySelectorAll(sel);

if (inputs.length) {
    flatpickr(sel, {
        enableTime: true,
        minTime: "7:00",
        maxTime: "21:00",
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        altInput: true,
        altFormat: "d/m/Y H:i",
        time_24hr: true,
        locale: French,
        disableMobile: true,
    });
}
