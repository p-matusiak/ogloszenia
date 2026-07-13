/// <reference types="vite/client" />

/**
 * Vite wstrzykuje te zmienne w czasie budowania. Deklaracja jest po to, żeby
 * `import.meta.env.VITE_*` nie był typu `any` — indeks w typach Vite jest
 * otwarty i przepuściłby każdą literówkę w nazwie.
 */
interface ImportMetaEnv {
  readonly VITE_APP_NAME?: string
  readonly VITE_API_BASE_URL?: string

  /**
   * Pokazuje na ekranie logowania poświadczenia konta z seedera. Wyłącznie
   * dla środowiska lokalnego: aplikacja jest serwowana ze zbudowanych
   * assetów, więc `import.meta.env.DEV` nie odróżni lokalnego builda
   * od produkcyjnego.
   */
  readonly VITE_SHOW_SEED_CREDENTIALS?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
