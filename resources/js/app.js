import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'

// Register plugins BEFORE start
Alpine.plugin(persist)
Alpine.plugin(focus)

// Expose Alpine globally (diperlukan oleh beberapa komponen inline)
window.Alpine = Alpine

Alpine.start()