import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import Tooltip from 'primevue/tooltip'
import { createApp } from 'vue'

import App from '@/App.vue'
import { readAppBootstrap } from '@/bootstrap/appBootstrap'
import { initialiseAppLocale } from '@/composables/useAppLocale'
import { useTheme } from '@/composables/useTheme'
import { i18n } from '@/i18n/index'
import { router } from '@/router'
import { useAuthStore } from '@/stores/auth'
import { ZuntoPreset } from '@/theme'

import 'primeicons/primeicons.css'

initialiseAppLocale()
useTheme().initialise()

const pinia = createPinia()
const auth = useAuthStore(pinia)
auth.hydrate(readAppBootstrap().user)

createApp(App)
  .use(pinia)
  .use(i18n)
  .use(router)
  .use(PrimeVue, {
    theme: {
      preset: ZuntoPreset,
      options: {
        darkModeSelector: '.dark',
        // Keep PrimeVue's generated CSS below Tailwind's utilities so utility
        // classes can still override component styles.
        cssLayer: { name: 'primevue', order: 'theme, base, primevue' },
      },
    },
  })
  .use(ToastService)
  .use(ConfirmationService)
  .directive('tooltip', Tooltip)
  .mount('#app')
