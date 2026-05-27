# KK Lightweight Consent

## Setup
1. Másold a mappát: `wp-content/plugins/kk-lightweight-consent/`.
2. Aktiváld a plugint WordPress adminban.
3. Menj: **Settings → KK Consent**.
4. Állítsd be a `Banner preset` mezőt:
   - `universal`: szabványos gombsorrend (Reject all, Customize, Accept all)
   - `kk`: kk.coach jellegű viselkedés (Accept all, Customize)
5. Mentsd el.

## Preset rendszer
A preset csak a gombok renderelését vezérli (sorrend, akció, stílus). A consent logika ugyanaz marad.

- Banner gombok presetből jönnek.
- Settings panel gombok presetből jönnek.
- A JS nem gomb osztályokra köt, hanem `data-consent-action` attribútumra.

### Támogatott actionök
- `accept_all`
- `reject_all`
- `settings`
- `save_choices`

### Támogatott button style osztályok
- `lcm-btn`
- `lcm-btn--primary`
- `lcm-btn--secondary`
- `lcm-btn--outline`
- `lcm-btn--text`

## Új preset hozzáadása
1. PHP-ben bővítsd a `get_presets()` metódust egy új kulccsal.
2. Definiáld a `banner_buttons` és `panel_buttons` tömböket (`action`, `label_key`, `style`).
3. Add hozzá a presetet az admin `Banner preset` select mezőjéhez.
4. Ha új `label_key` kell, add hozzá a label defaultokat és admin mezőket.

## GTM integráció
- A plugin `gtag('consent','default', ...)` és `gtag('consent','update', ...)` hívásokat ad.
- DataLayer események:
  - `kk_consent_default`
  - `kk_consent_update`
- DataLayer mezők:
  - `kk_consent_analytics`
  - `kk_consent_marketing`
  - `kk_consent_personalization`

## Consent storage
- localStorage kulcs: `kk_consent_{version}`
- cookie név: `kk_consent_{version}`
- SameSite=Lax, HTTPS esetén Secure, path=/
