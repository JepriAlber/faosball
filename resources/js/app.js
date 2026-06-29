import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'
import deleteModal from './components/delete-modal'

Alpine.plugin(persist)
Alpine.plugin(focus)

Alpine.data('deleteModal', deleteModal)

window.Alpine = Alpine

Alpine.start()