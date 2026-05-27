# Lightweight Consent Mode

Version: **0.3.1**

## Installation
1. Copy plugin folder to `wp-content/plugins/lightweight-consent-mode/`.
2. Activate **Lightweight Consent Mode**.
3. Open **Settings → Lightweight Consent Mode**.

## What is configurable
- Banner header/title text (EN/HU fields, EN fallback)
- Banner body text, panel intro, category descriptions, policy link text
- Preset-based buttons (`universal`, `kk`) with action-based behavior (`data-consent-action`)
- Banner button order (`accept_all,reject_all,settings` default)
- Per-action button colors (accept/reject/settings/save)
- Header/body/button font presets and custom font-family fields
- Border color + border width
- Banner padding (default 10px)
- Desktop/mobile layout and position settings
- GTM injection and Consent Mode defaults

## Version tracking
- Plugin version is `0.3.0`.
- Internal constant: `LCM_VERSION`.
- CSS/JS enqueue versions use `LCM_VERSION` for cache busting.

## Consent / GTM behavior
Consent Mode v2, GTM behavior, and consent storage remain unchanged:
- `gtag('consent','default', ...)`
- `gtag('consent','update', ...)`
- `dataLayer` events: `kk_consent_default`, `kk_consent_update`
- Storage: `localStorage` + first-party cookie key `kk_consent_<version>`

## Frontend language behavior
- Default language mode: `browser`
- Detection: `navigator.languages`, fallback `navigator.language`
- Supported: `en`, `hu`
- Fallback: English
- If Hungarian fields are empty, English values are used.
- Source code defaults remain English-only.

## Limited HTML formatting
Allowed in long text fields:
- `<strong>`, `<b>`, `<em>`, `<br>`, `<a href target rel>`

Allowed in button labels:
- `<strong>`, `<b>`, `<em>`

All formatted values are sanitized server-side before frontend rendering.

## Plugin list Settings link
The plugin adds a **Settings** link on the WordPress Plugins page via `plugin_action_links_{plugin_basename}`.

## Legacy option migration
- New option key: `lcm_options`
- Legacy key: `kk_lwc_options`
- Legacy values are migrated automatically when needed.


## Responsive button layout
- Desktop: banner and panel action rows are centered.
- Mobile (max-width: 767px): action buttons stack vertically and use full width with centered text.

## GTM setup quick guide
- Add GTM Container ID (for example GTM-XXXXXXX).
- Enable GTM injection only if GTM is not installed elsewhere.
- Verify consent events in GTM Preview (`kk_consent_default`, `kk_consent_update`).
- Check `window.dataLayer` in browser console and confirm consent state changes.
- Avoid duplicate GTM containers and publish GTM changes after updates.
