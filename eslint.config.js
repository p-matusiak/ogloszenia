import js from '@eslint/js'
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript'
import pluginVue from 'eslint-plugin-vue'
import globals from 'globals'

export default defineConfigWithVueTs(
  {
    ignores: ['public/**', 'vendor/**', 'node_modules/**', 'storage/**', 'bootstrap/**'],
  },

  js.configs.recommended,
  pluginVue.configs['flat/recommended'],
  vueTsConfigs.recommendedTypeChecked,

  {
    files: ['resources/js/**/*.{ts,vue}'],
    languageOptions: {
      globals: { ...globals.browser },
      parserOptions: {
        projectService: true,
        tsconfigRootDir: import.meta.dirname,
        extraFileExtensions: ['.vue'],
      },
    },
    rules: {
      // Project rules: no untyped public API, no escape hatches.
      '@typescript-eslint/explicit-module-boundary-types': 'error',
      '@typescript-eslint/no-explicit-any': 'error',
      'vue/multi-word-component-names': 'off',
    },
  },

  {
    files: ['resources/js/**/*.spec.ts'],
    languageOptions: {
      globals: { ...globals.node },
    },
    rules: {
      '@typescript-eslint/explicit-module-boundary-types': 'off',
      '@typescript-eslint/unbound-method': 'off',
      '@typescript-eslint/no-unsafe-assignment': 'off',
      '@typescript-eslint/no-unsafe-argument': 'off',
    },
  },
)
