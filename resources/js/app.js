import 'cropperjs/dist/cropper.css'
import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'
import deleteModal from './components/delete-modal'
import resetAkunModal from './components/reset-akun-modal'
import statusModal from './components/status-modal'
import logoutModal from './components/logout-modal'
import rolePermissionForm from './components/role-permission-form';
import logoCropField from './components/logo-crop-field';
import currencyInput from './components/currency-input';

Alpine.plugin(persist);
Alpine.plugin(focus);

Alpine.data('deleteModal', deleteModal)
Alpine.data('resetAkunModal', resetAkunModal)
Alpine.data('statusModal', statusModal)
Alpine.data('logoutModal', logoutModal)
Alpine.data('rolePermissionForm', rolePermissionForm);
Alpine.data('logoCropField', logoCropField);
Alpine.data('currencyInput', currencyInput);

window.Alpine = Alpine;


Alpine.start();