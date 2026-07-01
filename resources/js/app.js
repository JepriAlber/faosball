import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'
import deleteModal from './components/delete-modal'
import resetAkunModal from './components/reset-akun-modal'

Alpine.plugin(persist)
Alpine.plugin(focus)

Alpine.data('deleteModal', deleteModal)
Alpine.data('resetAkunModal', resetAkunModal)

window.Alpine = Alpine

Alpine.start()