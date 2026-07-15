<script setup lang="ts">
import { RouterLink } from 'vue-router'

import LegalPage from '@/components/legal/LegalPage.vue'
import { AD_RULES, OPERATOR, SITE } from '@/config/legal'
</script>

<template>
  <LegalPage title="Polityka prywatności">
    <h2>1. Administrator danych</h2>
    <p>
      Administratorem Twoich danych osobowych jest {{ OPERATOR.name }}, {{ OPERATOR.legalForm }},
      {{ OPERATOR.street }}, {{ OPERATOR.city }}, NIP {{ OPERATOR.nip }},
      REGON {{ OPERATOR.regon }} — właściciel serwisu {{ SITE.name }} ({{ SITE.domain }}).
    </p>
    <p>
      We wszystkich sprawach dotyczących danych osobowych skontaktujesz się pod adresem
      <span class="legal__todo">{{ OPERATOR.email }}</span> lub pisemnie na adres wskazany wyżej.
      Administrator nie wyznaczył inspektora ochrony danych, ponieważ nie zachodzi żadna
      z przesłanek art. 37 ust. 1 RODO.
    </p>
    <p>
      Zasady korzystania z serwisu opisuje
      <RouterLink :to="{ name: 'terms' }">
        Regulamin
      </RouterLink>.
    </p>

    <h2>2. Jakie dane przetwarzamy i na jakiej podstawie</h2>
    <p>
      Poniższa tabela wymienia wszystkie kategorie danych zbieranych przez serwis. Podstawy prawne
      odnoszą się do art. 6 ust. 1 rozporządzenia (UE) 2016/679 (RODO).
    </p>

    <div class="legal__table-scroll">
      <table>
        <thead>
          <tr>
            <th>Dane</th>
            <th>Cel</th>
            <th>Podstawa prawna</th>
            <th>Okres przechowywania</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Nazwa użytkownika, adres e-mail, hasło (przechowywane wyłącznie jako skrót kryptograficzny bcrypt), opcjonalnie zdjęcie profilowe i opis</td>
            <td>Założenie i prowadzenie konta</td>
            <td>lit. b — wykonanie umowy</td>
            <td>Do usunięcia konta</td>
          </tr>
          <tr>
            <td>Tytuł, opis, cena, stan, sposoby dostawy, miasto i dzielnica, zdjęcia</td>
            <td>Publikacja ogłoszenia</td>
            <td>lit. b — wykonanie umowy</td>
            <td>Do usunięcia ogłoszenia lub konta</td>
          </tr>
          <tr>
            <td>Adres e-mail i numer telefonu podane w ogłoszeniu</td>
            <td>Umożliwienie kontaktu zainteresowanym; <strong>dane jawne</strong></td>
            <td>lit. b — wykonanie umowy</td>
            <td>Do usunięcia ogłoszenia lub konta</td>
          </tr>
          <tr>
            <td>Adres IP, identyfikator przeglądarki (user agent), czas ostatniej aktywności</td>
            <td>Utrzymanie sesji, bezpieczeństwo, przeciwdziałanie nadużyciom i spamowi</td>
            <td>lit. f — prawnie uzasadniony interes</td>
            <td>Czas trwania sesji, nie dłużej niż 30 dni</td>
          </tr>
          <tr>
            <td>Licznik wyświetleń ogłoszenia i licznik ujawnień numeru telefonu</td>
            <td>Statystyka dla autora ogłoszenia; dane zagregowane, bez powiązania z osobą oglądającą</td>
            <td>lit. f — prawnie uzasadniony interes</td>
            <td>Do usunięcia ogłoszenia</td>
          </tr>
          <tr>
            <td>Treść zgłoszenia nadużycia, powód, identyfikator zgłaszającego (jeśli był zalogowany)</td>
            <td>Rozpatrzenie zgłoszenia nielegalnej treści; obowiązek z art. 16 DSA</td>
            <td>lit. c — obowiązek prawny; lit. f — obrona przed roszczeniami</td>
            <td>3 lata od rozpatrzenia</td>
          </tr>
          <tr>
            <td>Adres e-mail i token resetu hasła</td>
            <td>Odzyskanie dostępu do konta</td>
            <td>lit. b — wykonanie umowy</td>
            <td>60 minut od wygenerowania</td>
          </tr>
        </tbody>
      </table>
    </div>

    <p>
      Podanie danych jest dobrowolne, ale niezbędne: bez adresu e-mail i hasła nie założysz konta,
      a bez co najmniej jednej formy kontaktu nie opublikujesz ogłoszenia.
    </p>

    <h2>3. Dane widoczne publicznie</h2>
    <p>
      Treść ogłoszenia, zdjęcia, miasto, dzielnica oraz nazwa użytkownika są dostępne dla każdego
      odwiedzającego, także dla wyszukiwarek internetowych. Podany w ogłoszeniu adres e-mail jest
      widoczny wraz z ogłoszeniem. Numer telefonu jest domyślnie ukryty i ujawniany dopiero
      po wyraźnym kliknięciu przycisku „Pokaż numer”; liczba takich żądań jest ograniczona,
      co utrudnia masowe pobieranie numerów, ale nie eliminuje tego ryzyka całkowicie.
    </p>
    <p>
      Zanim opublikujesz zdjęcie lub opis, sprawdź, czy nie zawiera danych, których nie chcesz
      ujawniać — na przykład tablicy rejestracyjnej, adresu zamieszkania albo wizerunku osób trzecich.
    </p>

    <h2>4. Odbiorcy danych</h2>
    <p>
      Dane nie są przekazywane zewnętrznym dostawcom hostingu ani zewnętrznym operatorom poczty
      wyłącznie w celu utrzymania serwisu, ponieważ {{ SITE.name }} działa na własnej infrastrukturze
      administratora.
    </p>
    <p>
      <strong>Serwis nie korzysta z narzędzi analitycznych, reklamowych ani z wtyczek serwisów
        społecznościowych.</strong> Nie sprzedajemy ani nie udostępniamy danych w celach marketingowych
      i nie przekazujemy ich poza Europejski Obszar Gospodarczy.
    </p>
    <p>
      Dane mogą zostać udostępnione organom publicznym wyłącznie wtedy, gdy obowiązek taki wynika
      z przepisów prawa.
    </p>

    <h2>5. Brak profilowania</h2>
    <p>
      Nie podejmujemy decyzji opartych wyłącznie na zautomatyzowanym przetwarzaniu, w tym profilowaniu,
      które wywoływałyby wobec Ciebie skutki prawne. Decyzje o zatwierdzeniu lub odrzuceniu ogłoszenia
      podejmuje człowiek.
    </p>

    <h2>6. Twoje prawa</h2>
    <p>W związku z przetwarzaniem danych przysługuje Ci prawo do:</p>
    <ul>
      <li>dostępu do danych i otrzymania ich kopii (art. 15 RODO);</li>
      <li>sprostowania danych nieprawidłowych (art. 16 RODO);</li>
      <li>usunięcia danych — „prawo do bycia zapomnianym” (art. 17 RODO);</li>
      <li>ograniczenia przetwarzania (art. 18 RODO);</li>
      <li>przeniesienia danych do innego administratora (art. 20 RODO);</li>
      <li>
        wniesienia sprzeciwu wobec przetwarzania opartego na prawnie uzasadnionym interesie
        (art. 21 RODO).
      </li>
    </ul>
    <p>
      Żądanie zgłoś na adres <span class="legal__todo">{{ OPERATOR.email }}</span>. Odpowiadamy
      bez zbędnej zwłoki, najpóźniej w ciągu miesiąca. Konto wraz z ogłoszeniami możesz też usunąć
      samodzielnie w ustawieniach profilu.
    </p>
    <p>
      Jeżeli uważasz, że przetwarzamy Twoje dane niezgodnie z prawem, masz prawo wnieść skargę
      do Prezesa Urzędu Ochrony Danych Osobowych, ul. Stawki 2, 00-193 Warszawa.
    </p>

    <h2>7. Pliki cookies i pamięć przeglądarki</h2>
    <p>
      Serwis używa wyłącznie plików cookies niezbędnych do jego działania. Nie stosujemy cookies
      analitycznych ani marketingowych, dlatego nie prosimy o zgodę na ich zapis — art. 173 ust. 3
      Prawa telekomunikacyjnego takiej zgody dla cookies niezbędnych nie wymaga.
    </p>
    <dl>
      <dt>Ciasteczko sesyjne</dt>
      <dd>
        Utrzymuje zalogowanie. Zapisywane z flagą <code>httpOnly</code>, więc jest niedostępne
        dla skryptów. Wygasa po zamknięciu przeglądarki lub po 120 minutach bezczynności.
      </dd>
      <dt>XSRF-TOKEN</dt>
      <dd>Chroni przed atakami typu cross-site request forgery. Wygasa wraz z sesją.</dd>
      <dt>Ciasteczko „zapamiętaj mnie”</dt>
      <dd>Zapisywane tylko, gdy sam zaznaczysz tę opcję przy logowaniu.</dd>
      <dt>Pamięć lokalna przeglądarki (localStorage)</dt>
      <dd>
        Przechowuje wybrany motyw kolorystyczny (jasny lub ciemny). Nie zawiera danych osobowych
        i nie jest wysyłana na serwer.
      </dd>
    </dl>
    <p>
      Cookies możesz usunąć lub zablokować w ustawieniach przeglądarki. Zablokowanie ciasteczek
      niezbędnych uniemożliwi zalogowanie się i publikowanie ogłoszeń.
    </p>

    <h2>8. Bezpieczeństwo</h2>
    <p>
      Hasła przechowujemy wyłącznie w postaci skrótu kryptograficznego (bcrypt) — nie znamy ich
      i nie jesteśmy w stanie ich odtworzyć. Transmisja odbywa się szyfrowanym połączeniem HTTPS.
      Liczba prób logowania, rejestracji, resetu hasła, zgłoszeń i ujawnień numeru telefonu jest
      ograniczona, co ogranicza ryzyko ataków automatycznych.
    </p>

    <h2>9. Okres przechowywania po usunięciu</h2>
    <p>
      Usunięcie konta powoduje trwałe usunięcie danych profilowych oraz opublikowanych ogłoszeń
      wraz ze zdjęciami. Ogłoszenie wygasa automatycznie po {{ AD_RULES.lifetimeDays }} dniach
      od publikacji, ale pozostaje w Twoim panelu do czasu odświeżenia lub usunięcia. Dane
      zawarte w zgłoszeniach nadużyć zachowujemy niezależnie od usunięcia konta zgłaszającego,
      w zakresie niezbędnym do obrony przed roszczeniami.
    </p>

    <h2>10. Zmiany polityki</h2>
    <p>
      O istotnych zmianach niniejszej polityki poinformujemy zarejestrowanych użytkowników
      pocztą elektroniczną z wyprzedzeniem co najmniej 14 dni. Aktualna wersja jest zawsze
      dostępna pod tym adresem.
    </p>
  </LegalPage>
</template>
