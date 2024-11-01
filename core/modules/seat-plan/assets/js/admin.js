'use strict';
jQuery(document).ready(function ($) {

    // Seat plan tooltip for canvas button
    const seatPlanPanel = document.getElementById('visual_seat_map');
    const goToCanvasBtn = document.querySelector('.go_to_canvas');
    const canvasButtonURL = goToCanvasBtn.getAttribute('href');
    const tooltipContent = 'In order to get the seat map, you must publish the event and reload the page.';
    const seatMapTooltip = document.createElement('div');
    seatMapTooltip.classList.add('seat-map-tooltip');
    seatMapTooltip.innerText = tooltipContent;

    // Show tooltip if the canvas button url is '#'
    goToCanvasBtn.addEventListener('click', (event) => {
        event.preventDefault();
        if (canvasButtonURL === '#') {
            seatPlanPanel.appendChild(seatMapTooltip);
            seatMapTooltip.style.visibility = 'visible';
        } else {
            window.location.href = canvasButtonURL;
        }
    });

    // Remove tooltip if the user clicks outside the tooltip
    document.addEventListener('click', (event) => {
        if (!goToCanvasBtn.contains(event.target)) {
            seatMapTooltip.remove();
        }
    });

});