// Carrega idioma PT do Flatpickr
import { Portuguese } from "flatpickr/dist/l10n/pt.js";

if (window.flatpickr) {
    window.flatpickr.localize(Portuguese);

    // Forçar padrão BR em todos os datepickers
    document.addEventListener("DOMContentLoaded", function () {
        const elements = document.querySelectorAll("input[type=date], .datepicker");
        elements.forEach(el => {
            flatpickr(el, {
                locale: "pt",
                dateFormat: "d/m/Y", // formato BR: 30/08/2025
            });
        });
    });
}
