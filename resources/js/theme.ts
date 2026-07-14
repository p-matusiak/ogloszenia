import { definePreset } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'

/**
 * Paleta primary oparta na pomarańczu logo Zunto (#FF9F00).
 * Akcent linków i informacji to granat (#1E3A5F) — tokeny w app.css.
 */
export const ZuntoPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#fff8eb',
      100: '#ffefcc',
      200: '#ffe199',
      300: '#ffd066',
      400: '#ffbc33',
      500: '#ff9f00',
      600: '#e88f00',
      700: '#cc7f00',
      800: '#a66600',
      900: '#804d00',
      950: '#4d2e00',
    },
    colorScheme: {
      light: {
        primary: {
          color: '{primary.500}',
          contrastColor: '#ffffff',
          hoverColor: '{primary.600}',
          activeColor: '{primary.700}',
        },
        formField: {
          borderColor: '#c8cdd6',
          hoverBorderColor: '#9aa3b2',
          focusBorderColor: '{primary.500}',
        },
      },
      dark: {
        primary: {
          color: '{primary.600}',
          contrastColor: '#fff8eb',
          hoverColor: '{primary.500}',
          activeColor: '{primary.700}',
        },
        formField: {
          borderColor: '#3a424f',
          hoverBorderColor: '#4a5568',
          focusBorderColor: '{primary.400}',
        },
      },
    },
  },
})