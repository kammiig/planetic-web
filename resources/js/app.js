import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;

// focus → accessible modal focus-trapping (x-trap)
// collapse → smooth, accessible accordion sections (x-collapse)
Alpine.plugin(focus);
Alpine.plugin(collapse);

Alpine.start();
