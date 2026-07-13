import { definePreset } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'

/**
 * Aura domyślnie ma zielony akcent. Serwis ogłoszeniowy z makiety jest
 * niebieski, więc podmieniamy paletę przez design tokeny, a nie nadpisywaniem
 * CSS komponentów.
 */
export const OgloszeniaPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#eff6ff',
      100: '#dbeafe',
      200: '#bfdbfe',
      300: '#93c5fd',
      400: '#60a5fa',
      500: '#3b82f6',
      600: '#2563eb',
      700: '#1d4ed8',
      800: '#1e40af',
      900: '#1e3a8a',
      950: '#172554',
    },
    colorScheme: {
      light: {
        primary: {
          color: '{primary.600}',
          contrastColor: '#ffffff',
          hoverColor: '{primary.700}',
          activeColor: '{primary.800}',
        },
      },
      dark: {
        primary: {
          color: '{primary.400}',
          contrastColor: '{primary.950}',
          hoverColor: '{primary.300}',
          activeColor: '{primary.200}',
        },
      },
    },
  },
})
