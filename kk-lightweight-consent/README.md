# Lightweight Consent Mode

## Installation
1. Copy the folder to `wp-content/plugins/lightweight-consent-mode/` (or keep folder name and activate in Plugins).
2. Activate **Lightweight Consent Mode** in WordPress admin.
3. Open **Settings → Lightweight Consent Mode**.

## Settings overview
### General
- Banner enabled
- Banner preset (`universal`, `kk`)
- Consent version
- Cookie days
- Policy URL
- Logo URL
- Reopen icon toggle

### Texts
- HU/EN banner texts
- HU/EN labels for `accept_all`, `reject_all`, `customize`, `save_choices`

### Design
- Background color
- Text color
- Primary button background/text
- Secondary button background/text
- Border color
- Border radius
- Max width
- Font preset (`inherit`, `system`, `arial`, `georgia`, `custom`)
- Custom font family

### Layout
- Desktop position (`center`, `bottom_center`, `bottom_left`, `bottom_right`)
- Desktop layout (`box`, `sheet`)
- Mobile layout (`sheet`, `box`)

### GTM / Consent Mode
- GTM Container ID
- GTM snippet injection
- Default checkbox states for analytics/marketing/personalization
- Debug mode

## GTM integration
- Plugin sets Consent Mode v2 default and update via `gtag('consent', ...)`.
- Plugin pushes `kk_consent_default` and `kk_consent_update` events to `dataLayer`.
- Use GTM triggers/conditions on:
  - `kk_consent_analytics`
  - `kk_consent_marketing`
  - `kk_consent_personalization`

## Styling options
Frontend styling uses CSS variables generated from admin settings via `wp_add_inline_style()`.
No build step or external CSS dependencies are required.

## Button presets
Presets define button rendering only; consent behavior stays action-based.
- `kk`: banner shows **Accept all** + **Customize**
- `universal`: banner shows **Reject all**, **Customize**, **Accept all**

Actions are bound via `data-consent-action`:
- `accept_all`
- `reject_all`
- `settings`
- `save_choices`

## Migration note
This version uses `lcm_options` as the main settings key.
Backward compatibility is preserved: if `lcm_options` does not exist but `kk_lwc_options` exists, legacy options are migrated automatically.


## Frontend language selection
- Default mode is `browser`.
- Browser language is detected using `navigator.languages` with fallback to `navigator.language`.
- Supported frontend languages: `en`, `hu`.
- Resolution order: first `hu*`, then `en*`, else fallback to `en`.
- English is the global fallback when browser language is unsupported.
- English is also used when a Hungarian translation field is empty.
