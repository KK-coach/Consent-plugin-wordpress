# KK Lightweight Consent

## Setup
1. Másold a mappát: `wp-content/plugins/kk-lightweight-consent/`.
2. Aktiváld a plugint WordPress adminban.
3. Menj: **Settings → KK Consent**.
4. Állítsd be:
   - Banner engedélyezve
   - Consent version
   - GTM Container ID
   - GTM snippet injektálása (ha nem más tölti be)
   - Logó URL
   - Banner szövegek, link URL, cookie napok
5. Mentsd el.

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

## Megjegyzés
A plugin nem injektál GA4/Ads/Meta tracking kódokat. Ezeket GTM-ben kell konfigurálni consent feltételekkel.
