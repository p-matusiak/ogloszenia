<script setup lang="ts">
import { RouterLink } from 'vue-router'

import LegalPage from '@/components/legal/LegalPage.vue'
import { AD_RULES, OPERATOR, SITE } from '@/config/legal'
</script>

<template>
  <LegalPage title="Regulamin serwisu">
    <h2>§ 1. Postanowienia ogólne</h2>
    <p>
      Niniejszy regulamin określa zasady korzystania z serwisu {{ SITE.name }}, dostępnego
      pod adresem {{ SITE.domain }} (dalej „Serwis”), i stanowi regulamin świadczenia usług
      drogą elektroniczną w rozumieniu art. 8 ustawy z dnia 18 lipca 2002 r. o świadczeniu
      usług drogą elektroniczną.
    </p>
    <p>
      Usługodawcą i właścicielem Serwisu jest {{ OPERATOR.name }}, {{ OPERATOR.legalForm }},
      {{ OPERATOR.street }}, {{ OPERATOR.city }}, NIP {{ OPERATOR.nip }},
      REGON {{ OPERATOR.regon }} (dalej „Usługodawca”). Kontakt z Usługodawcą jest możliwy
      pod adresem e-mail <span class="legal__todo">{{ OPERATOR.email }}</span> oraz pisemnie
      na adres wskazany powyżej. Adres e-mail jest równocześnie elektronicznym punktem
      kontaktowym w rozumieniu art. 11 i 12 rozporządzenia (UE) 2022/2065 (akt o usługach
      cyfrowych, dalej „DSA”). Językiem komunikacji jest język polski.
    </p>
    <p>
      Korzystanie z Serwisu oznacza akceptację regulaminu. Zasady przetwarzania danych
      osobowych opisuje odrębny dokument —
      <RouterLink :to="{ name: 'privacy' }">
        Polityka prywatności
      </RouterLink>.
    </p>

    <h2>§ 2. Definicje</h2>
    <dl>
      <dt>Użytkownik</dt>
      <dd>Osoba fizyczna, prawna lub jednostka organizacyjna korzystająca z Serwisu.</dd>
      <dt>Konto</dt>
      <dd>Zbiór zasobów Użytkownika w Serwisie, dostępny po zalogowaniu.</dd>
      <dt>Ogłoszenie</dt>
      <dd>
        Treść zamieszczana przez Użytkownika, stanowiąca zaproszenie do zawarcia umowy
        w rozumieniu art. 71 Kodeksu cywilnego, a nie ofertę.
      </dd>
      <dt>Konsument</dt>
      <dd>
        Użytkownik będący osobą fizyczną, dokonujący czynności niezwiązanej bezpośrednio
        z jego działalnością gospodarczą lub zawodową.
      </dd>
    </dl>

    <h2>§ 3. Rodzaj i zakres usług</h2>
    <p>Usługodawca świadczy drogą elektroniczną następujące usługi, wszystkie nieodpłatnie:</p>
    <ul>
      <li>udostępnianie i wyszukiwanie Ogłoszeń — bez konieczności zakładania Konta;</li>
      <li>prowadzenie Konta;</li>
      <li>publikowanie, edytowanie, odświeżanie i usuwanie Ogłoszeń — po zalogowaniu;</li>
      <li>zgłaszanie Ogłoszeń naruszających prawo lub regulamin — bez konieczności zakładania Konta.</li>
    </ul>
    <p>
      <strong>
        Usługodawca nie jest stroną umów zawieranych między Użytkownikami i nie pośredniczy
        w ich zawieraniu ani wykonaniu.
      </strong>
      Serwis udostępnia wyłącznie przestrzeń techniczną na treści pochodzące od Użytkowników.
      Usługodawca nie weryfikuje istnienia, stanu ani legalności przedmiotów opisanych
      w Ogłoszeniach, nie uczestniczy w płatnościach ani w dostawie.
    </p>

    <h2>§ 4. Wymagania techniczne</h2>
    <p>
      Do korzystania z Serwisu potrzebne są: urządzenie z dostępem do internetu, aktualna
      przeglądarka internetowa z obsługą JavaScriptu i plików cookies oraz — w przypadku
      zakładania Konta — czynny adres poczty elektronicznej. Usługodawca nie ponosi
      odpowiedzialności za problemy wynikające z niespełnienia tych wymagań.
    </p>
    <p>
      Zakazane jest dostarczanie treści o charakterze bezprawnym, a także korzystanie
      z Serwisu w sposób zakłócający jego działanie, w szczególności przy użyciu
      zautomatyzowanego pobierania danych (scrapingu), omijania limitów zapytań
      lub prób nieuprawnionego dostępu.
    </p>

    <h2>§ 5. Konto</h2>
    <p>
      Rejestracja wymaga podania nazwy Użytkownika, adresu e-mail i hasła. Umowa o prowadzenie
      Konta zostaje zawarta z chwilą rejestracji, na czas nieoznaczony.
    </p>
    <p>
      Użytkownik zobowiązuje się nie udostępniać danych logowania osobom trzecim oraz podawać
      dane prawdziwe. Użytkownik może w każdej chwili usunąć Konto, co jest równoznaczne
      z wypowiedzeniem umowy ze skutkiem natychmiastowym. Usunięcie Konta powoduje usunięcie
      opublikowanych przez niego Ogłoszeń.
    </p>
    <p>
      Usługodawca może zawiesić lub usunąć Konto, jeżeli Użytkownik rażąco lub uporczywie
      narusza regulamin albo przepisy prawa, po uprzednim bezskutecznym wezwaniu do zaprzestania
      naruszeń — chyba że naruszenie polega na publikowaniu treści stanowiących przestępstwo.
      Użytkownikowi przysługuje odwołanie w trybie § 10.
    </p>

    <h2>§ 6. Zasady publikowania Ogłoszeń</h2>
    <ul>
      <li>Jedno Ogłoszenie może dotyczyć tylko jednego przedmiotu lub usługi.</li>
      <li>Ogłoszenie musi być przypisane do właściwej kategorii, a jego treść zgodna ze stanem faktycznym.</li>
      <li>
        Użytkownik może opublikować maksymalnie {{ AD_RULES.dailyLimitPerUser }} Ogłoszenia
        w ciągu doby.
      </li>
      <li>
        Do Ogłoszenia można dołączyć maksymalnie {{ AD_RULES.maxImages }} zdjęć w formacie
        {{ AD_RULES.imageFormats }}, każde o rozmiarze do {{ AD_RULES.maxImageMb }} MB.
      </li>
      <li>
        Ogłoszenie musi zawierać co najmniej jedną formę kontaktu: adres e-mail albo numer
        telefonu.
      </li>
    </ul>
    <p>
      <strong>Dane kontaktowe podane w Ogłoszeniu są jawne.</strong> Adres e-mail jest widoczny
      dla każdego odwiedzającego, a numer telefonu jest ujawniany po wyraźnym żądaniu
      (kliknięciu przycisku „Pokaż numer”). Publikując Ogłoszenie, Użytkownik godzi się
      na takie udostępnienie tych danych.
    </p>
    <p>
      Ogłoszenie pozostaje widoczne przez {{ AD_RULES.lifetimeDays }} dni od publikacji,
      po czym wygasa. Wygasłe Ogłoszenie Użytkownik może bezpłatnie odświeżyć, co uruchamia
      kolejny {{ AD_RULES.lifetimeDays }}-dniowy okres.
    </p>
    <p>
      Publikując Ogłoszenie, Użytkownik oświadcza, że jest uprawniony do rozporządzania
      przedmiotem Ogłoszenia oraz że przysługują mu prawa do zamieszczonych zdjęć i opisów.
      Użytkownik udziela Usługodawcy nieodpłatnej, niewyłącznej licencji na zwielokrotnianie
      i publiczne udostępnianie tych treści w zakresie niezbędnym do prowadzenia Serwisu,
      na czas publikacji Ogłoszenia.
    </p>

    <h2>§ 7. Treści zabronione</h2>
    <p>Zabronione jest publikowanie Ogłoszeń, które:</p>
    <ul>
      <li>dotyczą przedmiotów, których obrót jest zakazany lub reglamentowany, w szczególności narkotyków, broni, materiałów wybuchowych, leków na receptę, żywych zwierząt objętych ochroną, organów ludzkich, danych dostępowych i dokumentów tożsamości;</li>
      <li>naruszają prawa autorskie lub prawa ochronne na znaki towarowe, w tym oferują podróbki;</li>
      <li>zawierają dane osobowe osób trzecich udostępnione bez ich zgody;</li>
      <li>mają charakter dyskryminujący, nawołują do nienawiści lub przemocy, zawierają treści pornograficzne;</li>
      <li>stanowią spam, oszustwo, piramidę finansową lub inną próbę wyłudzenia;</li>
      <li>wprowadzają w błąd co do przedmiotu, ceny lub tożsamości ogłoszeniodawcy.</li>
    </ul>

    <h2>§ 8. Moderacja i uzasadnienie decyzji</h2>
    <p>
      Każde Ogłoszenie przed publikacją trafia do weryfikacji przez Usługodawcę. Weryfikacja
      jest prowadzona <strong>ręcznie, przez człowieka</strong> — Usługodawca nie stosuje
      zautomatyzowanego podejmowania decyzji ani profilowania w rozumieniu art. 22 RODO.
    </p>
    <p>
      W razie odrzucenia lub usunięcia Ogłoszenia Użytkownik otrzymuje uzasadnienie decyzji
      ze wskazaniem podstawy — przepisu prawa albo postanowienia regulaminu — zgodnie z art. 17
      DSA. Uzasadnienie jest widoczne przy Ogłoszeniu w panelu Użytkownika.
    </p>

    <h2>§ 9. Zgłaszanie treści nielegalnych</h2>
    <p>
      Zgodnie z art. 16 DSA każdy może zgłosić Usługodawcy Ogłoszenie, które uważa za nielegalne.
      Zgłoszenia dokonuje się przyciskiem „Zgłoś ogłoszenie” dostępnym przy każdym Ogłoszeniu,
      także bez zakładania Konta, albo wiadomością na adres
      <span class="legal__todo">{{ OPERATOR.email }}</span>.
    </p>
    <p>
      Zgłoszenie powinno wskazywać powód, adres Ogłoszenia oraz wyjaśnienie, dlaczego treść jest
      nielegalna. Usługodawca rozpatruje zgłoszenia bez zbędnej zwłoki, w sposób niearbitralny
      i obiektywny, i informuje zgłaszającego o podjętej decyzji, jeżeli zgłaszający podał adres
      e-mail.
    </p>
    <p>
      Usługodawca jest mikroprzedsiębiorcą w rozumieniu art. 3 zalecenia 2003/361/WE, wobec czego
      na podstawie art. 19 DSA nie stosuje się do niego obowiązków z sekcji 3 rozdziału III DSA,
      w tym obowiązku prowadzenia wewnętrznego systemu rozpatrywania skarg. Nie uchybia to prawu
      do reklamacji na zasadach § 10 ani do drogi sądowej.
    </p>

    <h2>§ 10. Reklamacje</h2>
    <p>
      Reklamacje dotyczące działania Serwisu, w tym decyzji moderacyjnych, można składać na adres
      <span class="legal__todo">{{ OPERATOR.email }}</span>. Reklamacja powinna zawierać dane
      umożliwiające kontakt oraz opis zastrzeżeń. Usługodawca rozpatruje reklamację i udziela
      odpowiedzi w terminie 14 dni od jej otrzymania.
    </p>

    <h2>§ 11. Odpowiedzialność</h2>
    <p>
      Usługodawca nie ponosi odpowiedzialności za treść Ogłoszeń ani za skutki umów zawartych
      między Użytkownikami. Zgodnie z art. 14 ustawy o świadczeniu usług drogą elektroniczną
      oraz art. 6 DSA Usługodawca nie odpowiada za przechowywane treści, o ile nie wie o ich
      bezprawnym charakterze, a po uzyskaniu wiarygodnej wiadomości niezwłocznie uniemożliwia
      do nich dostęp.
    </p>
    <p>
      Usługodawca dokłada starań, aby Serwis działał nieprzerwanie, zastrzega jednak możliwość
      przerw technicznych. Wobec Użytkowników niebędących Konsumentami odpowiedzialność
      Usługodawcy z tytułu niewykonania lub nienależytego wykonania usług jest ograniczona
      do szkody rzeczywistej i wyłączona w zakresie utraconych korzyści. Ograniczenia te nie
      dotyczą szkody wyrządzonej umyślnie ani odpowiedzialności, której nie można wyłączyć
      na podstawie bezwzględnie obowiązujących przepisów.
    </p>

    <h2>§ 12. Odstąpienie od umowy</h2>
    <p>
      Konsument, który zawarł umowę o prowadzenie Konta, może od niej odstąpić bez podania
      przyczyny w terminie 14 dni od jej zawarcia, składając oświadczenie na adres e-mail
      Usługodawcy. Usługi są nieodpłatne, więc odstąpienie nie wiąże się z rozliczeniami
      finansowymi. Uprawnienie to nie wyłącza prawa do usunięcia Konta w każdym czasie
      na zasadach § 5.
    </p>

    <h2>§ 13. Pozasądowe rozwiązywanie sporów</h2>
    <p>
      Konsument może skorzystać z pozasądowych sposobów rozpatrywania reklamacji i dochodzenia
      roszczeń, w szczególności zwrócić się do stałego polubownego sądu konsumenckiego
      lub do powiatowego (miejskiego) rzecznika konsumentów. Szczegółowe informacje udostępnia
      Urząd Ochrony Konkurencji i Konsumentów.
    </p>

    <h2>§ 14. Zmiany regulaminu</h2>
    <p>
      Usługodawca może zmienić regulamin z ważnych przyczyn, w szczególności zmiany przepisów
      prawa lub zakresu świadczonych usług. O zmianie Użytkownicy posiadający Konto zostaną
      poinformowani na adres e-mail przypisany do Konta, z wyprzedzeniem co najmniej 14 dni.
      Brak wypowiedzenia umowy przed wejściem zmiany w życie oznacza jej akceptację.
    </p>

    <h2>§ 15. Prawo właściwe</h2>
    <p>
      W sprawach nieuregulowanych stosuje się prawo polskie, w szczególności Kodeks cywilny,
      ustawę o świadczeniu usług drogą elektroniczną i ustawę o prawach konsumenta. Wybór prawa
      polskiego nie pozbawia Konsumenta ochrony wynikającej z bezwzględnie obowiązujących
      przepisów prawa państwa jego zwykłego pobytu.
    </p>
  </LegalPage>
</template>
