import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'
import deleteModal from './components/delete-modal'
import resetAkunModal from './components/reset-akun-modal'
import statusModal from './components/status-modal'

Alpine.plugin(persist)
Alpine.plugin(focus)

Alpine.data('deleteModal', deleteModal)
Alpine.data('resetAkunModal', resetAkunModal)
Alpine.data('statusModal', statusModal)

window.Alpine = Alpine

Alpine.start()