# Lightweight Consent Mode

Version: **0.3.5**

## Installation
1. Copy plugin folder to `wp-content/plugins/lightweight-consent-mode/`.
2. Activate **Lightweight Consent Mode**.
3. Open **Settings → Lightweight Consent Mode**.

## What is configurable
- Banner header/title text (EN/HU fields, EN fallback)
- Banner body text, panel intro, category descriptions, policy link text
- Preset-based buttons (`universal`, `kk`) with action-based behavior (`data-consent-action`)
- Banner button order (`accept_all,reject_all,settings` default)
- Per-action button colors, hover colors, and button radius
- Header/body/button font presets and safe custom font-family fields
- Border color + border width
- Banner padding (default 10px)
- Desktop/mobile layout and position settings
- GTM injection and Consent Mode defaults

## Version tracking
- The plugin header and `LCM_VERSION` constant are kept in sync.
- CSS/JS enqueue versions use `LCM_VERSION` for cache busting.

## Consent / GTM behavior
Consent Mode v2, GTM behavior, and consent storage remain focused on consent state:
- `gtag('consent','default', ...)`
- `gtag('consent','update', ...)`
- Storage: `localStorage` + first-party cookie key `kk_consent_<version>`

### dataLayer events
- `kk_consent_default` is pushed when the default consent state is set. It includes the current analytics, marketing, and personalization states.
- `kk_consent_update` is pushed when the visitor changes consent by accepting, rejecting, or saving choices.
- `kk_consent_ready` is pushed when the current consent state is known and can be used for GTM triggers, including returning visitors with saved consent and new choices after interaction.

For most GA4 setups, the normal trigger is still **All Pages** with consent checks. `kk_consent_ready` can be useful for stricter non-Google marketing tag setups or returning-visitor flows where tags should wait until the saved state is known.

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
- Desktop: banner and panel action rows are centered and remain horizontal.
- Mobile (max-width: 767px): action buttons stack vertically and use full width with centered text.
- Sheet layout aligns the banner to the bottom on mobile; box layout centers it.

## GTM setup quick guide
- Add GTM Container ID (for example GTM-XXXXXXX).
- Enable GTM injection only if GTM is not installed elsewhere.
- Verify consent events in GTM Preview (`kk_consent_default`, `kk_consent_update`, `kk_consent_ready`).
- Check `window.dataLayer` in browser console and confirm consent state changes.
- Avoid duplicate GTM containers and publish GTM changes after updates.

## GTM setup (short guide)
- Set GTM Container ID and enable GTM injection only if GTM is not already installed elsewhere.
- Use GTM Preview in an incognito session and verify `kk_consent_default` before interaction and `kk_consent_update` after user choices.
- Typical GA4 trigger is **All Pages** with consent checks.
- Consent Initialization is for consent-setup tags, not normal GA4/Ads/marketing tags.
- Use `kk_consent_update` Custom Event trigger only for tags that should fire immediately after consent changes.
- Use `kk_consent_ready` for stricter setups where a non-Google tag should wait until the current state is known.
- Check `window.dataLayer` in browser console for consent events and state changes.

## Reopen button
- The reopen button is icon-only by default with accessible label support.
- Reopen button styling is independent from main action button colors.
