import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto'; // Import Chart.js
import './archive'; // Import archive functionality
import './confirm-dialog';
import './pds-modal';

window.Alpine = Alpine;
window.Chart = Chart; // Make Chart.js globally available

Alpine.start();
