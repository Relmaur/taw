// 1. Import Styles (so Vite knows to compile them)
import '../css/app.css';   // Tailwind v4 utilities
import '../scss/app.scss'; // Custom SCSS (fonts, etc.)

// 2. Import Alpine
import Alpine from 'alpinejs';

// 3. Setup Alpine
window.Alpine = Alpine;

// 4. Start Alpine
Alpine.start();

console.log('Vite is running. Alpine is active.');