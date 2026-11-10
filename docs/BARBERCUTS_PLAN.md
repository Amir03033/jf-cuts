> **Verouderd.** BarberCuts wordt een apart, nieuw project. De actuele opdracht staat in `docs/BARBERCUTS_BOUWOPDRACHT.md`. Alleen §1.1 (bekende problemen in JF Cuts) is nog nuttig voor dit project.

# BarberCuts.nl — Plan & logica

> Doel: JF Cuts (één kapperszaak) uitbreiden naar **barbercuts.nl**, een platform waar kappers uit heel Nederland hun afspraken, klanten en omzet beheren.
>
> **Prio 1: mobile.** Alles wordt eerst ontworpen voor een telefoon van 375px breed, met één hand te bedienen.
> **Prio 2: clean interface.** Rustig, veel witruimte, één primaire actie per scherm, geen overbodige elementen.
>
> Dit document is geschreven als **startpunt voor een nieuwe chat/agent**. Lees eerst §1 (huidige stand), dan §2 (beslissingen), en werk daarna de fases in §8 van boven naar beneden af.

---

## 0. Instructies voor de volgende chat

1. Lees `CLAUDE.md`/`AGENTS.md` en volg de Laravel Boost-setup (`composer require laravel/boost --dev && php artisan boost:install`) vóór je code wijzigt.
2. Draai eerst de bestaande tests (`composer test`) om een nulmeting te hebben.
3. Werk **fase voor fase** (§8). Elke fase = eigen branch/PR, met tests, en de app moet na elke fase werken.
4. Verander de bestaande businessregels in `AppointmentService` / `AvailabilityService` niet stilletjes: die zijn goed. Ze worden alleen *uitgebreid* (multi-tenant, medewerkers, pauzes).
5. Taal in de UI: **Nederlands**. Code, klassen en kolommen: **Engels** (zoals nu).

---

## 1. Huidige stand (wat er al is)

**Stack:** Laravel 13, Livewire 4, PHP 8.3+, SQLite (dev), Tailwind v4 via Vite *geïnstalleerd maar niet gebruikt* — de views laden handgeschreven CSS uit `public/css/customer.css` (2.600 regels) en `public/css/auth.css`.

**Domein (goed opgezet, behouden):**

| Onderdeel | Bestand | Wat het doet |
|---|---|---|
| Boekingsengine | `app/Services/AppointmentService.php` | maken / verplaatsen / annuleren / voltooien / no-show; transacties + `lockForUpdate` op de zaak tegen dubbele boekingen; `BookingException` voor gebruikersfouten |
| Beschikbaarheid | `app/Services/AvailabilityService.php` | weekrooster + uitzonderingen per datum → vrije starttijden op een raster (`booking_interval`) |
| Omzet | `app/Services/RevenueService.php` | som van `amount_paid` van *completed* afspraken per week/maand/jaar |
| Statussen | `app/Enums/AppointmentStatus.php` | `scheduled`, `completed`, `cancelled`, `no_show` |
| Instellingen | `barbershop_settings` | interval, max dagen vooruit, annuleer- en verplaatslimiet (minuten) |
| Rollen | `users.role` | `customer` of `barber` (globaal) |

**Kernregels die al kloppen en mee moeten:**
- Alleen `completed` telt als bezoek én als omzet; omzet = **werkelijk ontvangen bedrag** (`amount_paid`), niet de prijs van de dienst.
- Cancelled / no-show blokkeren geen tijd.
- Klant mag annuleren/verplaatsen tot X minuten vóór aanvang; de kapper altijd (zolang `scheduled`).
- Voltooien / no-show kan pas als de afspraak begonnen is.
- Bij verplaatsen telt de eigen afspraak niet mee als bezet (`$ignore`).
- `status`, `amount_paid`, `barbershop_id`, `owner_id` staan bewust niet in `$fillable`.

### 1.1 Problemen die opgelost moeten worden vóór multi-tenant

Dit zijn concrete bugs/risico's in de huidige code. In een platform met meerdere zaken worden een aantal hiervan **datalekken**.

| # | Probleem | Waar | Ernst |
|---|---|---|---|
| P1 | Klant boekt altijd bij de **eerste** zaak in de database | `Livewire/Appointments/CreateAppointment::barbershop()` (`orderBy('id')->firstOrFail()`) | Blokkerend |
| P2 | Kapper ziet bij "nieuwe afspraak" **alle klanten van het hele platform** (naam + e-mail) | `Livewire/Barber/CreateAppointment::customers()` | **Datalek** |
| P3 | Kapper kan elk klantprofiel openen via `/barber/customers/{id}` (naam/e-mail van elke user) | `BarberCustomersController::show` | **Datalek** |
| P4 | Tabelnaam `Appointments` met hoofdletter — werkt in SQLite, breekt op MySQL (Linux) / Postgres | migratie `2026_09_30_093259` | Hoog |
| P5 | `enum userRole` (kleine u) in bestand `UserRole.php` | `app/Enums/UserRole.php` | Laag |
| P6 | Wachtwoord in plain text in de seeder én een SQLite-bestand `laravel` in de repo | `DatabaseSeeder.php`, `/laravel` | Hoog — wachtwoord wijzigen, bestand uit git, toevoegen aan `.gitignore` |
| P7 | `private function barbershop()` (op `owner_id`) staat 8× gekopieerd | controllers + Livewire | Onderhoud |
| P8 | `days()` berekent slots voor 31 dagen × 2+ queries per dag (N+1) bij elke render | alle boek/verplaats-componenten | Traag op mobiel |
| P9 | Openingstijden zijn op twee plekken te bewerken (`BarberSettingsController` én `Livewire/Barber/Availability`) | — | Verwarrend |
| P10 | Barber-Livewire-schermen gebruiken `layouts.customer` | `Availability`, `Barber/CreateAppointment`, `Barber/RescheduleAppointment` | UI-fout |
| P11 | Eén pauze per dag is niet mogelijk (unique op `barbershop_id, day_of_week`) | `availability_rules` | Functioneel gemis |
| P12 | Kapper kan geen afspraak maken voor iemand **zonder account** (telefoon/walk-in) | `createAppointment` vereist `User` met rol customer | Functioneel gemis, kern voor kappers |
| P13 | `lockForUpdate` doet niets op SQLite | — | Productie moet MySQL 8 of Postgres zijn |

---

## 2. Architectuurbeslissingen

### 2.1 Multi-tenancy: één database, scopen op `barbershop_id`
- **Tenant = `Barbershop`.** Bijna alle tabellen hebben al `barbershop_id` — dat is de basis.
- Geen package als `stancl/tenancy` (overkill). Wel:
  - `App\Support\CurrentShop` (singleton) — houdt de actieve zaak vast voor het request.
  - Middleware `ResolveCurrentShop` — bepaalt de zaak (zie 2.2) en controleert lidmaatschap.
  - Trait `BelongsToShop` met een **global scope** `where barbershop_id = CurrentShop::id()` + automatisch invullen bij `creating`. Op: `Service`, `Appointment`, `Customer`, `AvailabilityRule`, `AvailabilityException`, `ShopImage`, `BarbershopSetting`.
  - Route model binding gaat dan automatisch via de scope → een id van een andere zaak geeft **404**, niet 403. Dit sluit P2/P3 structureel af.
- Elke tenant-test krijgt een tweede zaak met data erbij, en checkt dat die nooit zichtbaar is (§9).

### 2.2 URL-structuur (path-based, geen subdomeinen per zaak)
Path-based is simpeler (één SSL-certificaat, werkt overal, geen wildcard-DNS) en goed genoeg.

| URL | Wie | Wat |
|---|---|---|
| `barbercuts.nl/` | iedereen | marketing-landingspagina + "Start gratis" voor kappers |
| `barbercuts.nl/kappers` | klanten | zoeken op plaats/naam (fase 5) |
| `barbercuts.nl/{slug}` | klanten | **publieke boekingspagina** van een zaak (bv. `/jf-cuts`) |
| `barbercuts.nl/{slug}/boeken` | klanten | boekflow |
| `barbercuts.nl/afspraak/{token}` | klanten | afspraak bekijken/annuleren/verplaatsen via link uit e-mail (geen login nodig) |
| `barbercuts.nl/app/...` | kappers/medewerkers | beheeromgeving (dashboard, agenda, klanten, omzet, instellingen) |
| `barbercuts.nl/account/...` | klanten | eigen afspraken over alle zaken heen |
| `barbercuts.nl/admin/...` | platformbeheer | zaken, abonnementen, support |

- Gereserveerde slugs: `app, admin, account, afspraak, kappers, inloggen, registreren, uitloggen, api, up, prijzen, over, contact, privacy, voorwaarden, storage, build, css, images, livewire`.
- In `/app` is de actieve zaak `session('current_shop_id')`; als iemand lid is van meerdere zaken komt er een zaakwisselaar.
- Later optioneel: eigen domein (`jfcuts.nl` → CNAME naar barbercuts) via een `domains`-tabel. Niet nu.

### 2.3 Gebruikers, rollen en klanten (grootste modelwijziging)
De huidige globale rol `customer|barber` werkt niet in een platform. Nieuw:

- **`users`** = iedereen die kan inloggen. `users.role` wordt platformrol: `user` | `admin`.
- **`shop_user`** (lidmaatschap): `barbershop_id`, `user_id`, `role` = `owner` | `staff`. Eén user kan bij meerdere zaken horen.
- **`customers`** (nieuw, per zaak!): het klantenbestand van de kapper.
  - `barbershop_id`, `user_id` (nullable), `name`, `phone`, `email` (nullable), `notes`, `marketing_opt_in`, timestamps.
  - Een klant **zonder account** (telefoon/walk-in) = `user_id null`. Lost P12 op.
  - Boekt een ingelogde user online bij een zaak → `firstOrCreate` customer-record met `user_id` voor die zaak.
  - Kapper ziet **alleen** `customers` van zijn eigen zaak. Lost P2/P3 op.
  - `unique(barbershop_id, user_id)`, index op `(barbershop_id, phone)` en `(barbershop_id, email)`.
- **`appointments.customer_id`** verwijst voortaan naar `customers.id` (niet `users.id`).

Rechten (vervangt `owner_id === auth()->id()` overal):

| Actie | owner | staff | klant |
|---|---|---|---|
| Agenda zien / afspraken maken, verplaatsen, annuleren, voltooien | ✅ | ✅ (eigen + alle, instelbaar) | eigen, binnen limieten |
| Klanten zien | ✅ | ✅ | — |
| Omzet zien | ✅ alles | alleen eigen (instelbaar) | — |
| Diensten, openingstijden, instellingen, medewerkers, abonnement | ✅ | — | — |

Implementatie: `BarbershopPolicy` + `AppointmentPolicy` + `CustomerPolicy`, met helper `User::roleIn(Barbershop): ?ShopRole`. `AppointmentService` krijgt geen `owner_id`-checks meer maar roept `Gate::forUser($actor)->authorize(...)` aan.

### 2.4 Medewerkers (meerdere kappers per zaak)
- `appointments.staff_id` (nullable → user in `shop_user`).
- `availability_rules` en `availability_exceptions` krijgen `staff_id` (nullable = geldt voor de hele zaak).
- Dienst ↔ medewerker: pivot `service_staff` (welke kapper doet welke dienst; leeg = iedereen).
- Klant kiest "Geen voorkeur" of een specifieke kapper.
- Een zaak met één kapper (zoals JF Cuts) merkt hier niets van: de owner is automatisch de enige medewerker en de stap "kies kapper" wordt overgeslagen.

### 2.5 Geld
- Nieuwe geldkolommen in **centen** (`unsignedInteger`): `price_cents`, `amount_paid_cents`, `tip_cents`. Bestaande `decimal`-kolommen migreren (×100). Geen floats meer in `RevenueService`.
- Bij boeken een **snapshot** opslaan: `appointments.service_name`, `appointments.price_cents`, `appointments.duration`. Prijswijzigingen later veranderen oude afspraken/rapportages niet.
- `appointments.payment_method`: `pin` | `cash` | `tikkie` | `online` | `other`.

### 2.6 Tijdzone
- `barbershops.timezone` default `Europe/Amsterdam`. Opslaan in UTC (`config('app.timezone') = 'UTC'` in productie) en per zaak omrekenen. Zolang alles NL is mag het ook op `Europe/Amsterdam` blijven — maar kies het één keer bewust en test zomer/wintertijd (laatste zondag maart/oktober).

### 2.7 Abonnementen (verdienmodel)
- **Mollie** (iDEAL/SEPA-incasso, NL-standaard) via `mollie/laravel-cashier-mollie`.
- 14 dagen gratis proberen, geen creditcard nodig.
- Voorstel tarieven (aan te passen): **Solo** €19/mnd (1 kapper), **Team** €39/mnd (t/m 5 kappers), **Pro** €69/mnd (onbeperkt + SMS-tegoed).
- `barbershops.status`: `trial` | `active` | `past_due` | `suspended`. Bij `suspended`: boekingspagina toont "tijdelijk niet beschikbaar", `/app` alleen lezen + betaalscherm. **Nooit data verwijderen.**

### 2.8 Infrastructuur
- Productie: MySQL 8 of Postgres (P13), Redis voor queue/cache, queue worker + scheduler (Laravel Forge/Ploi of Laravel Cloud).
- Mail: Postmark of Mailgun (EU). SMS/WhatsApp later via MessageBird/Bird of Twilio.
- Bestanden (foto's, logo's): S3-compatible (bv. Cloudflare R2/Scaleway), niet de lokale disk.
- AVG: verwerkersovereenkomst met kappers (zij zijn verwerkingsverantwoordelijke voor hun klanten), privacyverklaring, export/verwijderen van klantdata, data in de EU.

---

## 3. Datamodel (doel)

```
users            id, name, email (unique), phone, password, role[user|admin], email_verified_at, timestamps
barbershops      id, slug (unique), name, description, address, postal_code, city, phone, email,
                 timezone, accent_color, logo_path, is_public, status, trial_ends_at, timestamps
                 (owner_id blijft voor nu, wordt afgeleid uit shop_user role=owner)
shop_user        id, barbershop_id, user_id, role[owner|staff], can_see_all_revenue (bool), timestamps
                 unique(barbershop_id, user_id)
customers        id, barbershop_id, user_id?, name, phone?, email?, notes?, marketing_opt_in, timestamps
                 unique(barbershop_id, user_id)
services         id, barbershop_id, name, description?, duration, price_cents, buffer_after (min), active, sort_order
service_staff    service_id, user_id
appointments     id, barbershop_id, customer_id → customers, staff_id? → users, service_id,
                 service_name, price_cents, duration,               -- snapshot
                 starts_at, ends_at, status, source[online|barber|walk_in],
                 amount_paid_cents?, tip_cents?, payment_method?, completed_at?,
                 cancelled_at?, cancelled_by?, notes?, manage_token (unique), reminder_sent_at?, timestamps
                 index(barbershop_id, starts_at), index(staff_id, starts_at), index(customer_id, starts_at)
availability_rules       id, barbershop_id, staff_id?, day_of_week (1–7), start_time, end_time   -- meerdere blokken per dag = pauzes
availability_exceptions  id, barbershop_id, staff_id?, date, start_time?, end_time?, is_available, reason?
barbershop_settings      + min_notice (min, bv. 60), allow_guest_booking, require_phone, reminder_hours (24)
shop_images      (bestaand)
subscriptions    (Cashier Mollie)
```

Wijziging `availability_rules`: de kolom `is_available` vervalt — een dag zonder regels = gesloten; twee regels op één dag (09:00–12:30, 13:00–18:00) = pauze ertussen.

---

## 4. Kernlogica

### 4.1 Tenant-resolutie (per request)

```
/app/*       → auth → shop = session('current_shop_id') ?? eerste lidmaatschap
               → geen lidmaatschap? redirect naar onboarding
               → CurrentShop::set(shop); policies checken rol
/{slug}/*    → shop = Barbershop::where('slug', slug)->where('is_public', true)->firstOrFail()
               → status suspended? toon "niet beschikbaar"
               → CurrentShop::set(shop)
/account/*   → auth; GEEN tenant-scope: query expliciet via customers.user_id = auth()->id()
               (gebruik Appointment::withoutGlobalScope(...) alleen hier, bewust en getest)
```

### 4.2 Beschikbaarheid (uitbreiding van `AvailabilityService`)

Werk met **intervallen** in plaats van per slot te checken:

```
openingsblokken(staff, dag):
    uitzondering voor (staff, dag)?  → die gebruiken (is_available=false → [])
    anders uitzondering zaak-breed?   → die gebruiken
    anders regels voor (staff, weekdag), anders regels zaak-breed
    → lijst [start, eind] in de tijdzone van de zaak

vrije_intervallen(staff, dag) = openingsblokken − bezette afspraken (+ buffer_after van hun dienst)

slots(dienst, staff, dag):
    voor elk vrij interval [a, b]:
        t = eerste rastertijd ≥ a     (raster = booking_interval, gerekend vanaf blokstart)
        zolang t + duur + buffer ≤ b:
            als t ≥ nu + min_notice en dag ≤ vandaag + max_booking_days → slot
            t += booking_interval

slots(dienst, "geen voorkeur", dag) = unie van slots over alle medewerkers die de dienst doen
```

**Toewijzen bij "geen voorkeur":** kies de medewerker die op dat tijdstip vrij is met de minste geboekte minuten die dag (eerlijk verdelen).

**Performance (P8):** voeg `getAvailableDays(shop, service, staff?, from, to)` toe die regels, uitzonderingen en afspraken voor de hele periode in **3 queries** ophaalt en per dag in geheugen rekent. De kalender toont standaard 14 dagen (horizontaal swipen voor meer), niet 31.

**Dubbele boekingen:** blijft zoals nu — transactie, `lockForUpdate` op de zaak (of op `staff` zodra er medewerkers zijn), daarna opnieuw `assertSlotIsAvailable`. Werkt pas echt op MySQL/Postgres.

### 4.3 Boeken (één service-methode voor alle kanalen)

```
AppointmentService::book(BookingData $data, ?User $actor): Appointment
    BookingData: shop, service, staff|null, startsAt, customer (Customer | {name, phone, email}), source
    1. dienst hoort bij zaak en is actief; staff (indien gekozen) doet de dienst
    2. actor = klant → mag alleen voor zichzelf; actor = owner/staff → mag voor elke customer van de zaak;
       actor = null (gast) → alleen als settings.allow_guest_booking
    3. customer resolven: bestaand record, of firstOrCreate op (shop, user_id) / (shop, phone|email)
    4. transactie + lock → slot nog vrij? → staff toewijzen → snapshot (naam, prijs, duur) → manage_token
    5. event AppointmentBooked → mail naar klant (+ melding naar kapper) via de queue
```

Annuleren / verplaatsen / voltooien / no-show blijven zoals nu, met autorisatie via policies en events (`AppointmentCancelled`, `AppointmentRescheduled`, `AppointmentCompleted`).

### 4.4 Statusmachine

```
scheduled ──(klant/kapper annuleert, binnen limiet voor klant)──▶ cancelled
scheduled ──(kapper, na starttijd, bedrag ingevuld)─────────────▶ completed
scheduled ──(kapper, na starttijd)──────────────────────────────▶ no_show
completed ──(kapper, binnen 24 uur: "ongedaan maken")───────────▶ scheduled   (nieuw, voorkomt typefouten)
```
Alle andere overgangen → `BookingException`.

### 4.5 Omzet & statistieken (`RevenueService` → `ShopStatsService`)

Altijd per zaak, optioneel per medewerker, periode in de tijdzone van de zaak, op basis van `starts_at`:

| KPI | Formule |
|---|---|
| Omzet | Σ `amount_paid_cents` van completed |
| Fooi | Σ `tip_cents` (apart tonen, niet in omzet) |
| Afspraken | aantal completed |
| Gem. besteding | omzet / afspraken |
| No-show % | no_show / (completed + no_show) |
| Bezettingsgraad | geboekte minuten (scheduled+completed) / beschikbare minuten uit rooster |
| Nieuwe klanten | customers met eerste completed afspraak in periode |
| Terugkeer % | klanten met ≥2 completed in laatste 90 dagen / alle klanten in die periode |

- Vergelijking met vorige periode (`+12% t.o.v. vorige week`).
- Uitsplitsing per betaalmethode (handig voor de kassa-afsluiting en de boekhouding).
- Export CSV per maand (datum, klant, dienst, medewerker, bedrag, fooi, betaalmethode) — voor de boekhouder/BTW.
- Queries met `GROUP BY` op datum in de database, geen collections in PHP.

### 4.6 Herinneringen & notificaties
- Scheduler elke 10 min: afspraken met `starts_at` binnen `reminder_hours` en `reminder_sent_at IS NULL` → mail (later SMS), `reminder_sent_at = now()`.
- Mails: bevestiging, herinnering, gewijzigd, geannuleerd — elk met de `manage_token`-link.
- Kapper: dagoverzicht om 07:30 (optioneel), push via PWA later.

---

## 5. Mobile-first (prio 1)

### 5.1 Principes
- Ontwerp op **375×667**, test daarna 390/430 en pas als laatste desktop (desktop = dezelfde kolom, max ~640px breed, of een 2-koloms agenda vanaf `lg`).
- **Duimzone:** primaire acties onderaan. Vaste **bottom navigation** (max 5 items) + een sticky actieknop onderaan formulieren/boekflow.
- Tapdoelen **≥ 44×44px**, minimaal 8px ertussen. Inputs **≥ 16px** font (anders zoomt iOS in).
- `viewport-fit=cover` + `env(safe-area-inset-*)` voor notch/home-indicator (zit al in de layout — behouden).
- Juiste toetsenborden: `type="tel"`, `inputmode="numeric"` / `decimal` voor bedragen, `autocomplete="name|tel|email"`.
- Geen hover-afhankelijke interactie; geen tabellen — **lijsten met kaarten**.
- Bottom sheets i.p.v. modals in het midden (bv. "Afspraak voltooien → bedrag + betaalmethode").
- `wire:navigate` voor app-achtige paginawissels, skeleton/loading states (`wire:loading`) bij elke actie, optimistic feedback.
- **PWA:** `manifest.webmanifest`, icons, `display: standalone`, theme-color per zaak; service worker die alleen de app-shell cachet. Kapper zet "BarberCuts" op zijn beginscherm → voelt als een app, zonder App Store.
- Performance-budget: < 100 KB CSS+JS (gzip), LCP < 2s op 4G. Afbeeldingen als WebP, `loading="lazy"`, vaste afmetingen.

### 5.2 Navigatie

**Kapper (`/app`)** — bottom nav:
`Vandaag` · `Agenda` · **`+`** (nieuwe afspraak, groot, midden) · `Klanten` · `Meer` (omzet, diensten, openingstijden, instellingen, abonnement)

**Klant (publiek/`/account`)** — geen bottom nav in de boekflow (focus!), wel een stappenindicator bovenaan en één knop onderaan.

### 5.3 Belangrijkste schermen

1. **Vandaag** (startscherm kapper) — bovenaan: omzet vandaag + aantal afspraken (2 kleine tegels). Daaronder een tijdlijn van vandaag: elke afspraak als kaart (tijd, naam, dienst, status-chip). Swipe/tap op kaart → acties: *Voltooien* · *No-show* · *Verplaatsen* · *Bellen* · *WhatsApp*. Eerstvolgende afspraak uitgelicht.
2. **Agenda** — horizontale datumstrip (week, swipebaar) + dagweergave als tijdlijn; per medewerker filteren. Lege tijd tappen → nieuwe afspraak op dat tijdstip.
3. **Nieuwe afspraak (kapper)** — 1 scherm, snel: klant zoeken/nieuw (naam + telefoon is genoeg) → dienst (chips) → tijd (voorgestelde vrije tijden) → opslaan. Doel: **< 15 seconden** voor een telefonische boeking.
4. **Voltooien** — bottom sheet: bedrag (voorgevuld met prijs), fooi, betaalmethode (4 grote knoppen), opslaan.
5. **Klanten** — zoekbalk bovenaan (sticky), lijst. Klantdetail: bellen/WhatsApp-knoppen, notities ("fade 1,5 op de zijkant"), bezoekhistorie, totale besteding, no-shows.
6. **Omzet** — segmented control Dag/Week/Maand/Jaar, één groot getal + verschil t.o.v. vorige periode, simpele staafgrafiek, daaronder KPI-tegels (§4.5) en "Exporteer CSV".
7. **Publieke boekingspagina** — logo, naam, adres (tap → kaarten-app), foto's (horizontale carrousel), diensten met prijs/duur, grote knop "Afspraak maken".
8. **Boekflow klant** — `Dienst → (Kapper) → Dag + tijd (op één scherm: datumstrip boven, tijden eronder) → Gegevens (naam, telefoon, e-mail; of inloggen) → Bevestigd`. Max 4 stappen, terugknop altijd linksboven, gekozen keuzes als samenvatting bovenaan.
9. **Afspraak beheren via link** — details, "Toevoegen aan agenda" (.ics), Verplaatsen, Annuleren.

---

## 6. Clean interface (prio 2)

### 6.1 Design system
- Stap over van `public/css/customer.css` naar **Tailwind v4 (al geïnstalleerd) + `@vite`**. Definieer tokens in `resources/css/app.css` onder `@theme`:
  - Kleuren: neutrale basis (`--color-bg`, `--color-surface`, `--color-border`, `--color-text`, `--color-muted`), één **accentkleur** `--color-accent` die per zaak overschreven wordt (`<html style="--color-accent: {{ $shop->accent_color }}">`). Status-kleuren: gepland (blauw), voltooid (groen), geannuleerd (grijs), no-show (rood).
  - Licht én donker thema (`prefers-color-scheme`). De huidige JF Cuts-look (zwart/goud) wordt dan gewoon "donker thema + gouden accent".
  - Spacing-schaal 4/8/12/16/24/32, radius 12px (kaarten) / 999px (chips), één schaduw.
  - Typografie: 1 font (Inter of Instrument Sans), 4 groottes (12/14/16/20) + 1 display (28–32) voor grote getallen.
- **Blade-componenten** in `resources/views/components/ui/`: `button`, `input`, `select`, `card`, `list-item`, `chip`, `stat`, `bottom-sheet`, `empty-state`, `segmented`, `date-strip`, `time-grid`, `avatar`, `toast`. Geen losse styling in pagina's — alleen componenten.
- Iconen: één set (Lucide/Heroicons outline, 24px, stroke 1.75).

### 6.2 Regels
- Eén primaire knop per scherm. Secundaire acties als tekstknop of in een "…"-menu.
- Lege staten met uitleg + actie ("Nog geen diensten — Voeg je eerste dienst toe").
- Getallen groot, labels klein en grijs. Bedragen altijd als `€ 1.234,50` (NL-notatie, helper `money($cents)`).
- Datums menselijk: "Vandaag 14:30", "Morgen", "do 14 nov".
- Foutmeldingen bij het veld, in gewone taal (zoals de huidige `BookingException`-teksten — goed!).
- Toasts voor succes, onderaan boven de bottom nav, 3 seconden.
- Toegankelijkheid: contrast ≥ 4.5:1, focus-states zichtbaar, labels bij alle inputs, `aria-live` voor toasts.

---

## 7. Onboarding nieuwe kapper (moet < 3 minuten)

```
barbercuts.nl → "Start gratis"
1. Account: naam, e-mail, wachtwoord (of magic link)         → user + e-mailverificatie
2. Zaak: naam, plaats (slug wordt voorgesteld: "jf-cuts")     → barbershop + shop_user(owner) + settings
3. Openingstijden: preset "Di–Za 09:00–18:00" + aanpassen
4. Diensten: voorgestelde lijst (Knippen 30 min €25, Baard 15 min €12,50, Knippen+baard 45 min €35) aanvinken/aanpassen
5. Klaar: "Jouw boekingslink: barbercuts.nl/jf-cuts" + Kopieer / Deel via WhatsApp / QR-code
```
Trial start bij stap 2. Checklist op het dashboard ("Foto toevoegen", "Logo", "Link in Instagram-bio zetten").

---

## 8. Fases (in deze volgorde)

Elke fase: eigen branch + PR, migraties omkeerbaar, tests groen, app bruikbaar.

### Fase 0 — Opruimen & fundament (klein, eerst doen)
- [ ] P4: migratie die `Appointments` → `appointments` hernoemt (via tijdelijke naam i.v.m. case-insensitive SQLite/MySQL).
- [ ] P5: `enum UserRole`.
- [ ] P6: seeder-wachtwoord naar `env('SEED_PASSWORD', Str::random())`; `/laravel` uit git (`git rm --cached`) en in `.gitignore`. **Wachtwoord van het echte account wijzigen** (staat in de git-historie).
- [ ] P10: barber-componenten op `layouts.barber`.
- [ ] P9: één plek voor openingstijden (de Livewire-component), de andere weghalen.
- [ ] Tailwind + `@vite` aanzetten, tokens + eerste `x-ui.*` componenten; bestaande schermen nog niet allemaal ombouwen.
- [ ] MySQL/Postgres in CI naast SQLite, zodat P4/P13-achtige fouten opvallen.

### Fase 1 — Multi-tenant kern
- [ ] `barbershops`: `slug`, `city`, `postal_code`, `phone`, `timezone`, `accent_color`, `logo_path`, `is_public`, `status`, `trial_ends_at`. Bestaande zaak → slug `jf-cuts`.
- [ ] `shop_user` + datamigratie (elke `owner_id` → owner-lidmaatschap). `users.role` barber/customer → `user`.
- [ ] `customers` + datamigratie: per (barbershop, customer_id) uit `appointments` een customer-record met `user_id`; `appointments.customer_id` omzetten. **Test deze migratie op een kopie van de productie-DB.**
- [ ] `CurrentShop`, `ResolveCurrentShop`, `BelongsToShop` (global scope), policies; alle 8× `barbershop()` (P7) weg.
- [ ] Routes naar `/app/...`, `/{slug}/...`, `/account/...` (oude `/barber/*` en `/customer/*` → 301 redirect).
- [ ] P1, P2, P3, P12 opgelost: kapper kan klant zonder account aanmaken; klantzoeker toont alleen eigen klanten.
- [ ] Geld naar centen + snapshot-kolommen op appointments.
- [ ] Tenant-isolatietests (§9).

### Fase 2 — Publieke boekingspagina & gastboeken (mobile-first)
- [ ] `/{slug}` pagina + boekflow (§5.3 punt 7–8), gastboeken met naam/telefoon/e-mail.
- [ ] `manage_token`-link: bekijken / verplaatsen / annuleren zonder login, `.ics`-download.
- [ ] Bevestigingsmails via queue.
- [ ] P8: `getAvailableDays` in 3 queries; kalender 14 dagen.
- [ ] Rate limiting op boeken (per IP + per telefoonnummer), honeypot tegen spam.

### Fase 3 — Kapper-app herbouwen (mobile-first)
- [ ] Schermen "Vandaag", "Agenda", "Nieuwe afspraak" (snel), bottom-sheet "Voltooien" (bedrag, fooi, betaalmethode), klantdetail met notities.
- [ ] Openingstijden met pauzes (meerdere blokken per dag, P11) en vrije dagen/vakantie (periode).
- [ ] `buffer_after` per dienst, `min_notice` instelling.
- [ ] PWA (manifest, icons, service worker voor de shell).
- [ ] "Voltooien ongedaan maken" binnen 24 uur.

### Fase 4 — Onboarding & abonnementen
- [ ] Self-service aanmelden (§7) met e-mailverificatie.
- [ ] Mollie-abonnementen, trial, statussen, betaalscherm, facturen.
- [ ] Platform-admin (`/admin`): zaken, status, impersonate (met logging), basis-KPI's (MRR, actieve zaken, boekingen/week).
- [ ] Marketing-landingspagina + prijzen, privacy, voorwaarden, verwerkersovereenkomst.

### Fase 5 — Groei
- [ ] Medewerkers (§2.4): uitnodigen per e-mail, eigen rooster, "geen voorkeur".
- [ ] Omzet/statistieken volledig (§4.5) + CSV-export.
- [ ] Herinneringen (mail, daarna SMS/WhatsApp als betaalde add-on).
- [ ] Kappersgids `barbercuts.nl/kappers?plaats=utrecht` (SEO: per stad een pagina, schema.org `HairSalon`).
- [ ] Reviews na een voltooide afspraak, wachtlijst bij volle dagen, no-show-beleid (aanbetaling via Mollie).

---

## 9. Tests (verplicht per fase)

- **Tenant-isolatie** (belangrijkste testset): maak zaak A en zaak B met elk diensten, klanten en afspraken. Als lid van A:
  - `GET /app/customers/{klant-van-B}` → 404
  - `POST /app/appointments/{afspraak-van-B}/cancel` → 404
  - klantzoeker bevat geen klanten van B
  - omzet bevat geen bedragen van B
  - Livewire-acties met id's van B (`selectCustomer`, `selectService`) → geweigerd
- **Beschikbaarheid:** pauzes, uitzondering gaat voor regel, staff-regel gaat voor zaakregel, buffer, `min_notice`, `max_booking_days`, zomer-/wintertijd, afspraak over blokgrens, verplaatsen negeert zichzelf.
- **Boeken:** gast, ingelogde klant, kapper voor klant zonder account, dubbele boeking (twee keer achter elkaar hetzelfde slot → tweede faalt), inactieve dienst, dienst van andere zaak.
- **Omzet:** alleen completed, centen kloppen, periodegrenzen in tijdzone van de zaak, per medewerker.
- **Mobile:** Playwright-screenshots van de hoofdschermen op 375px (iPhone SE) en 430px; check dat er geen horizontale scroll is.
- Bestaande tests (`tests/Feature/AppointmentEngineTest.php`, `AppointmentRulesTest.php`, …) blijven groen; pas ze aan op het nieuwe customer-model.

---

## 10. Open vragen voor de eigenaar

> De volledige, bijgewerkte vragenlijst (met advies en ruimte voor antwoorden) staat in `docs/START_HIER.md` §3. Die lijst is leidend.

1. Blijft **jfcuts.nl** een eigen domein (doorverwijzen naar `barbercuts.nl/jf-cuts`), of wordt JF Cuts gewoon de eerste klant op het platform?
2. Tarieven en trialduur (§2.7) — akkoord of anders?
3. Gastboeken (zonder account) standaard aan? (Advies: ja, verhoogt conversie flink op mobiel.)
4. Moeten klanten online kunnen **betalen/aanbetalen**, of blijft betalen in de zaak (pin/contant)?
5. SMS/WhatsApp-herinneringen: inbegrepen in een pakket of los tegoed?
6. Licht of donker als standaardthema voor het platform? (Advies: licht voor `barbercuts.nl`/beheer, zaak kiest zelf voor de boekingspagina.)
