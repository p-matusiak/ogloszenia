import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import { createApp } from 'vue'

import App from '@/App.vue'
import { router } from '@/router'
import { OgloszeniaPreset } from '@/theme'

import 'primeicons/primeicons.css'

createApp(App)
  .use(createPinia())
  .use(router)
  .use(PrimeVue, {
    theme: {
      preset: OgloszeniaPreset,
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
  .mount('#app')
