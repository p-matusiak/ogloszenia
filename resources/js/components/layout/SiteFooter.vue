<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const year = new Date().getFullYear()
const newsletterEmail = ref('')
const logoSrc = '/logo.png'
</script>

<template>
  <footer class="site-footer">
    <div class="shell site-footer__inner">
      <div class="site-footer__brand">
        <RouterLink
          :to="{ name: 'landing' }"
          class="site-footer__logo"
        >
          <img
            :src="logoSrc"
            alt=""
            width="48"
            height="42"
          >
          <span>{{ t('nav.siteName') }}<span class="site-footer__tld">.pl</span></span>
        </RouterLink>
        <p class="site-footer__tagline">
          {{ t('landing.footer.tagline') }}
        </p>
      </div>

      <div class="site-footer__columns">
        <div>
          <h3 class="site-footer__heading">
            {{ t('landing.footer.categories') }}
          </h3>
          <nav class="site-footer__links">
            <RouterLink :to="{ name: 'categories.show', params: { slug: 'motoryzacja' } }">
              {{ t('landing.footer.linkMotoryzacja') }}
            </RouterLink>
            <RouterLink :to="{ name: 'categories.show', params: { slug: 'nieruchomosci' } }">
              {{ t('landing.footer.linkNieruchomosci') }}
            </RouterLink>
            <RouterLink :to="{ name: 'categories.show', params: { slug: 'elektronika' } }">
              {{ t('landing.footer.linkElektronika') }}
            </RouterLink>
            <RouterLink :to="{ name: 'listings' }">
              {{ t('landing.footer.linkAll') }}
            </RouterLink>
          </nav>
        </div>

        <div>
          <h3 class="site-footer__heading">
            {{ t('landing.footer.help') }}
          </h3>
          <nav class="site-footer__links">
            <RouterLink :to="{ name: 'terms' }">
              {{ t('footer.terms') }}
            </RouterLink>
            <RouterLink :to="{ name: 'privacy' }">
              {{ t('footer.privacy') }}
            </RouterLink>
            <RouterLink :to="{ name: 'login' }">
              {{ t('nav.login') }}
            </RouterLink>
          </nav>
        </div>

        <div>
          <h3 class="site-footer__heading">
            {{ t('landing.footer.company') }}
          </h3>
          <nav class="site-footer__links">
            <RouterLink :to="{ name: 'ads.create' }">
              {{ t('nav.addAd') }}
            </RouterLink>
            <RouterLink :to="{ name: 'register' }">
              {{ t('nav.register') }}
            </RouterLink>
          </nav>
        </div>

        <div class="site-footer__newsletter">
          <h3 class="site-footer__heading">
            {{ t('landing.footer.newsletter') }}
          </h3>
          <p class="site-footer__newsletter-text">
            {{ t('landing.footer.newsletterHint') }}
          </p>
          <form
            class="site-footer__newsletter-form"
            @submit.prevent
          >
            <InputText
              v-model="newsletterEmail"
              type="email"
              :placeholder="t('landing.footer.newsletterPlaceholder')"
              :aria-label="t('landing.footer.newsletterPlaceholder')"
            />
            <Button
              type="submit"
              :label="t('landing.footer.newsletterSubmit')"
              class="site-footer__newsletter-btn"
            />
          </form>
        </div>
      </div>

      <div class="site-footer__bottom">
        <p>{{ t('footer.copyright', { year }) }}</p>
        <nav
          class="site-footer__legal"
          :aria-label="t('footer.legalNav')"
        >
          <RouterLink :to="{ name: 'terms' }">
            {{ t('footer.terms') }}
          </RouterLink>
          <RouterLink :to="{ name: 'privacy' }">
            {{ t('footer.privacy') }}
          </RouterLink>
        </nav>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  background: var(--brand-blue);
  color: rgb(255 255 255 / 92%);
}

.site-footer__inner {
  padding-block: 2.5rem 1.5rem;
}

.site-footer__brand {
  margin-bottom: 2rem;
}

.site-footer__logo {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  color: #fff;
  font-size: 1.25rem;
  font-weight: 700;
}

.site-footer__tld {
  color: rgb(255 255 255 / 70%);
}

.site-footer__tagline {
  margin: 0.75rem 0 0;
  max-width: 24rem;
  font-size: 0.875rem;
  color: rgb(255 255 255 / 78%);
  line-height: 1.5;
}

.site-footer__columns {
  display: grid;
  gap: 1.5rem;
}

@media (width >= 48rem) {
  .site-footer__columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (width >= 75rem) {
  .site-footer__columns {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.site-footer__heading {
  margin: 0 0 0.75rem;
  font-size: 0.9375rem;
  font-weight: 700;
  color: #fff;
}

.site-footer__links {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.site-footer__links a {
  color: rgb(255 255 255 / 82%);
  text-decoration: none;
  font-size: 0.875rem;
}

.site-footer__links a:hover {
  color: var(--brand-orange);
}

.site-footer__newsletter-text {
  margin: 0 0 0.75rem;
  font-size: 0.8125rem;
  color: rgb(255 255 255 / 78%);
}

.site-footer__newsletter-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.site-footer__newsletter-btn :deep(.p-button) {
  background: var(--brand-orange);
  border-color: var(--brand-orange);
}

.site-footer__bottom {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 2rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgb(255 255 255 / 16%);
  font-size: 0.8125rem;
  color: rgb(255 255 255 / 72%);
}

@media (width >= 48rem) {
  .site-footer__bottom {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

.site-footer__bottom p {
  margin: 0;
}

.site-footer__legal {
  display: flex;
  gap: 1rem;
}

.site-footer__legal a {
  color: inherit;
  text-decoration: none;
}

.site-footer__legal a:hover {
  color: #fff;
}
</style>