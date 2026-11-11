import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// SweetAlert2 (from npm) - expose globally for Blade inline scripts
import './swal';
