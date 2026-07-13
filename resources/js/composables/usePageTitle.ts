const DEFAULT_SITE_NAME = 'Ogłoszenia'

const SITE_NAME = import.meta.env.VITE_APP_NAME ?? DEFAULT_SITE_NAME

/**
 * Serwer renderuje `<title>` dla pierwszego żądania, ale nawigacja w SPA nie
 * przeładowuje `<head>`. Bez tego użytkownik przechodzący między widokami
 * zostawał z tytułem strony, z której wszedł — a zakładka i historia
 * przeglądarki kłamały o tym, co ogląda.
 */
export function buildDocumentTitle(pageTitle?: string, siteName: string = SITE_NAME): string {
  const trimmed = pageTitle?.trim()

  return trimmed === undefined || trimmed === '' ? siteName : `${trimmed} | ${siteName}`
}

export function setDocumentTitle(pageTitle?: string): void {
  document.title = buildDocumentTitle(pageTitle)
}
