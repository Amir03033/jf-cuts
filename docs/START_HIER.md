# START HIER — opdracht voor de nieuwe chat

> Plak in een nieuwe chat: **"Lees `docs/START_HIER.md` en voer het uit."**
>
> Dit bestand is de opdracht. `docs/BARBERCUTS_PLAN.md` is de uitgebreide achtergrond (architectuur, datamodel, logica). Bij twijfel geldt: **dit bestand > het plan > je eigen aannames.**

---

## 1. Wat je bouwt

JF Cuts is nu een afsprakenapp voor één kapperszaak (Laravel 13 + Livewire 4). Jij bouwt die om tot **barbercuts.nl**: een platform waar kappers uit heel Nederland zich zelf aanmelden en hun **afspraken, klanten, omzet en openingstijden** beheren. Klanten boeken via een eigen pagina per zaak (`barbercuts.nl/{zaak}`).

**Prioriteiten, in deze volgorde:**
1. **Mobile.** Elk scherm wordt eerst gebouwd en getest op 375px breed (iPhone SE). Desktop komt daarna.
2. **Clean interface.** Rustig, veel witruimte, één primaire knop per scherm, lijsten met kaarten in plaats van tabellen.
3. **Veilig per zaak.** Een kapper ziet nooit gegevens van een andere zaak. Dit wordt per fase getest.

---

## 2. Eerst doen (setup)

1. Volg `CLAUDE.md`: controleer `php -v` en `composer -V`, en installeer daarna Laravel Boost:
   ```bash
   composer require laravel/boost --dev
   php artisan boost:install
   ```
   Lees daarna de gegenereerde richtlijnen (`AGENTS.md`) en houd je eraan.
2. `composer install`, `npm install`, `.env` maken, `php artisan key:generate`, `php artisan migrate --seed`.
3. Draai `composer test` en noteer het resultaat als nulmeting.
4. Lees `docs/BARBERCUTS_PLAN.md` helemaal, vooral §1.1 (bekende problemen P1–P13), §3 (datamodel) en §4 (logica).
5. Lees §3 van dit bestand (de beslissingen van de eigenaar). Lege antwoorden → gebruik het **advies** dat erbij staat.

---

## 3. Beslissingen van de eigenaar

> Eigenaar: vul hieronder je antwoord in achter **Antwoord:**. Leeg laten = het advies wordt gevolgd.

### Merk & domein
**V1. Wat gebeurt er met jfcuts.nl?**
Advies: JF Cuts wordt gewoon de eerste zaak op het platform; `jfcuts.nl` stuurt door naar `barbercuts.nl/jf-cuts`.
Antwoord:

**V2. Licht of donker als standaardthema?**
Advies: licht voor de beheeromgeving en `barbercuts.nl`; elke zaak kiest zelf licht/donker + accentkleur voor de eigen boekingspagina (JF Cuts = donker + goud).
Antwoord:

**V3. Logo/huisstijl voor BarberCuts zelf: heb je die al, of mag de chat een simpel woordmerk maken?**
Advies: simpel woordmerk ("barbercuts" in het basisfont + een accentkleur), later vervangen.
Antwoord:

### Klanten & boeken
**V4. Mogen klanten boeken zonder account (alleen naam, telefoon, e-mail)?**
Advies: ja, standaard aan, per zaak uit te zetten.
Antwoord:

**V5. Welke gegevens zijn verplicht bij boeken?**
Advies: naam + telefoon verplicht, e-mail aangeraden (nodig voor bevestiging en herinnering).
Antwoord:

**V6. Moeten klanten online kunnen betalen of aanbetalen (bijv. tegen no-shows)?**
Advies: nu niet; betalen blijft in de zaak (pin/contant/Tikkie). Aanbetaling pas in fase 5.
Antwoord:

**V7. Kan een klant per zaak een voorkeurskapper kiezen (als een zaak meerdere kappers heeft)?**
Advies: ja, met de optie "Geen voorkeur".
Antwoord:

### Kappers & zaken
**V8. Kan elke kapper zich zelf aanmelden, of keur jij nieuwe zaken eerst goed?**
Advies: zelf aanmelden, direct actief in de proefperiode; jij kunt een zaak blokkeren via `/admin`.
Antwoord:

**V9. Mogen medewerkers (niet-eigenaren) de omzet van de hele zaak zien?**
Advies: standaard alleen hun eigen omzet; de eigenaar kan dit per medewerker aanzetten.
Antwoord:

### Geld & abonnementen
**V10. Tarieven en proefperiode?**
Advies: 14 dagen gratis zonder betaalgegevens. Solo €19/mnd (1 kapper), Team €39/mnd (t/m 5), Pro €69/mnd (onbeperkt + SMS-tegoed). Prijzen excl. btw.
Antwoord:

**V11. Betaalprovider?**
Advies: Mollie (iDEAL + SEPA-incasso).
Antwoord:

**V12. Moeten abonnementen al in de eerste versie werken, of eerst gratis lanceren?**
Advies: eerst gratis met een paar kappers (fase 1–3), abonnementen in fase 4.
Antwoord:

### Communicatie
**V13. SMS/WhatsApp-herinneringen?**
Advies: eerst alleen e-mail (gratis). SMS later als betaalde extra of in het Pro-pakket.
Antwoord:

**V14. Hoeveel uur vooraf gaat de herinnering?**
Advies: 24 uur, per zaak instelbaar.
Antwoord:

### Techniek & hosting
**V15. Waar komt het te draaien?**
Advies: Laravel Cloud of Forge/Ploi op een EU-server, MySQL 8, Redis, mail via Postmark (EU).
Antwoord:

**V16. Draait JF Cuts nu al live met echte afspraken die bewaard moeten blijven?**
Advies: als ja → alle migraties moeten bestaande data meenemen en worden eerst getest op een kopie van de productiedatabase.
Antwoord:

### Planning
**V17. Wat moet er als eerste live? (bijv. "JF Cuts werkt op het nieuwe platform" of "eerste 5 kappers kunnen zich aanmelden")**
Advies: mijlpaal 1 = fase 0–2: JF Cuts draait op barbercuts.nl met een publieke boekingspagina die op mobiel perfect werkt.
Antwoord:

---

## 4. Werkwijze (regels voor de chat)

- **Werk fase voor fase** (§5). Begin pas aan een nieuwe fase als de vorige klaar is volgens de "Klaar als"-lijst.
- **Kleine commits** met duidelijke Nederlandse of Engelse berichten. Na elke fase: alle tests groen, `vendor/bin/pint` gedraaid, pushen.
- **Taal:** UI-teksten in het Nederlands, code/klassen/kolommen in het Engels (zoals nu).
- **Hergebruik de boekingsengine.** `AppointmentService` en `AvailabilityService` kloppen; breid ze uit, herschrijf de regels niet. Houd de regels uit §1 van het plan intact (alleen *completed* = omzet, cancelled/no-show blokkeren geen tijd, limieten voor klanten, locks tegen dubbele boekingen).
- **Alle data per zaak** via `CurrentShop` + de `BelongsToShop` global scope (plan §2.1). Nooit meer `Barbershop::where('owner_id', auth()->id())` of `Barbershop::first()`.
- **Autorisatie via policies**, niet via losse `if`-jes in controllers.
- **Geld altijd in centen** (integer). Weergave via één helper `money($cents)` → `€ 1.234,50`.
- **Styling alleen via `x-ui.*` Blade-componenten** + Tailwind-tokens. Geen nieuwe losse CSS-bestanden.
- **Mobiel testen:** maak na elk nieuw scherm een Playwright-screenshot op 375×667 en 430×932, en controleer: geen horizontale scroll, tapdoelen ≥ 44px, inputs ≥ 16px font, primaire knop binnen duimbereik.
- **Geen geheimen in de repo** (wachtwoorden, API-keys). Alles via `.env`.
- Twijfel je over iets wat niet in dit bestand of het plan staat: kies de simpelste oplossing die mobiel goed werkt, noteer de keuze onderaan dit bestand onder "Keuzes gemaakt door de chat", en ga door.

---

## 5. Fases & opleverlijst

### Fase 0 — Opruimen & fundament
Taken (plan §8, fase 0):
- Tabel `Appointments` → `appointments` (P4), `enum UserRole` (P5).
- Seeder-wachtwoord uit de code (P6); bestand `/laravel` (SQLite) uit git en in `.gitignore`.
- Barber-schermen op de juiste layout (P10); openingstijden op één plek (P9).
- Tailwind v4 + `@vite` aanzetten, design tokens in `resources/css/app.css`, eerste componenten: `button`, `input`, `card`, `list-item`, `chip`, `stat`, `bottom-sheet`, `empty-state`, `toast`.
- CI (GitHub Actions) met tests op SQLite én MySQL.

**Klaar als:** alle bestaande tests groen op SQLite en MySQL; geen wachtwoord meer in de code; de componenten staan op een interne pagina `/app/_ui` (alleen in local) zodat je ze kunt bekijken.

### Fase 1 — Meerdere zaken (multi-tenant kern)
Taken (plan §2.1–2.3, §3, fase 1):
- `barbershops` uitbreiden (slug, plaats, tijdzone, accentkleur, logo, status, …). JF Cuts krijgt slug `jf-cuts`.
- `shop_user` (eigenaar/medewerker) en `customers` (klantenbestand per zaak) + datamigraties van bestaande data.
- `CurrentShop`, `ResolveCurrentShop`, `BelongsToShop`, policies.
- Routes: `/app/...` (kapper), `/{slug}/...` (publiek), `/account/...` (klant), `/afspraak/{token}`. Oude `/barber/*` en `/customer/*` → 301-redirect.
- Kapper kan een klant zonder account aanmaken (naam + telefoon).
- Geld naar centen; snapshot van dienstnaam/prijs/duur op elke afspraak.

**Klaar als:** de tenant-isolatietests uit plan §9 bestaan en slagen (zaak A ziet niets van zaak B — klanten, afspraken, omzet, Livewire-acties); P1, P2, P3 en P12 zijn aantoonbaar opgelost met een test per probleem.

### Fase 2 — Publieke boekingspagina (mobiel)
Taken (plan §5.3 punt 7–9, fase 2):
- `barbercuts.nl/{slug}`: logo, naam, adres, foto's, diensten met prijs en duur, knop "Afspraak maken".
- Boekflow in max. 4 stappen: Dienst → (Kapper) → Dag + tijd op één scherm → Gegevens → Bevestigd.
- Gastboeken volgens V4/V5; beheerlink met token (bekijken/verplaatsen/annuleren, `.ics`).
- Bevestigingsmail via de queue.
- Beschikbaarheid voor 14 dagen in max. 3 queries (P8).
- Rate limiting + honeypot op boeken.

**Klaar als:** een klant op een telefoon van 375px in < 60 seconden kan boeken zonder in te zoomen of horizontaal te scrollen; screenshots van elke stap staan in de PR; dubbel boeken van hetzelfde slot faalt in een test.

### Fase 3 — Kapper-app (mobiel)
Taken (plan §5.2–5.3 punt 1–6, fase 3):
- Bottom nav: Vandaag · Agenda · **+** · Klanten · Meer.
- "Vandaag" (tijdlijn + omzet vandaag), "Agenda" (datumstrip + dagweergave), snelle "Nieuwe afspraak", bottom sheet "Voltooien" (bedrag, fooi, betaalmethode), klantdetail met notities en bel/WhatsApp-knoppen.
- Openingstijden met pauzes (meerdere blokken per dag, P11), vakantie/vrije dagen, buffer per dienst, minimale boektermijn.
- Omzetscherm (dag/week/maand/jaar, vergelijking met vorige periode).
- PWA: manifest, icons, service worker voor de app-shell.

**Klaar als:** een telefonische boeking door de kapper in < 15 seconden kan; een afspraak voltooien kost max. 3 taps; de app is op een telefoon aan het beginscherm toe te voegen en opent zonder browserbalk.

### Fase 4 — Aanmelden & abonnementen
Taken (plan §2.7, §7, fase 4):
- Zelf aanmelden als kapper in < 3 minuten (account → zaak → openingstijden → diensten → deelbare link + QR-code).
- Abonnementen volgens V10–V12 (Mollie), proefperiode, statussen (`trial`/`active`/`past_due`/`suspended`); nooit data verwijderen.
- `/admin` voor de platformeigenaar: zaken, status, inloggen-als (met log), MRR en boekingen per week.
- Landingspagina, prijzen, privacyverklaring, voorwaarden, verwerkersovereenkomst.

**Klaar als:** een nieuwe kapper zich op een telefoon kan aanmelden en direct een werkende boekingslink heeft; een verlopen proefperiode zet de zaak correct op alleen-lezen.

### Fase 5 — Groei
Medewerkers met eigen rooster en "geen voorkeur", volledige statistieken + CSV-export, herinneringen (V13/V14), kappersgids per stad (SEO), reviews, wachtlijst, aanbetaling tegen no-shows (V6). Details in plan §4.5, §4.6 en fase 5.

---

## 6. Logica in het kort (spiekbrief)

Volledige uitwerking: plan §4.

- **Welke zaak?** `/app` → `session('current_shop_id')` + lidmaatschap. `/{slug}` → zaak op slug. `/account` → geen scope, expliciet filteren op `customers.user_id`.
- **Vrije tijden:** openingsblokken (uitzondering > regel; medewerker > zaak) − bezette afspraken (+ buffer) → per vrij blok starttijden op het raster `booking_interval`, mits `≥ nu + min_notice` en `≤ vandaag + max_booking_days`. "Geen voorkeur" = alle tijden van alle geschikte kappers samen; bij boeken krijgt de kapper met de minste geboekte minuten die dag de afspraak.
- **Boeken:** één methode `AppointmentService::book(BookingData, ?User)` voor klant, gast en kapper → controles → klantrecord zoeken/aanmaken → transactie + lock → slot opnieuw checken → snapshot → token → event → mail.
- **Statussen:** `scheduled` → `completed` | `cancelled` | `no_show`; `completed` → `scheduled` alleen binnen 24 uur ("ongedaan maken"). Al het andere = `BookingException`.
- **Omzet:** Σ `amount_paid_cents` van completed afspraken, op `starts_at`, in de tijdzone van de zaak. Fooi apart. KPI's: aantal afspraken, gemiddelde besteding, no-show %, bezettingsgraad, nieuwe klanten, terugkeer %.

---

## 7. Keuzes gemaakt door de chat

> De chat vult hier aan wat hij zelf heeft besloten (datum, keuze, reden), zodat de eigenaar het kan nalezen.

-
