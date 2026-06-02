# KK Consent Banner - Consent Mode v2 (GTM Custom Tag Template)

This GTM **Tag Template** connects the WordPress consent plugin with Google Consent Mode v2.

It does **not** render a banner UI and does **not** manipulate the DOM.

## Files

- `template.tpl`
- `metadata.yaml`
- `README.md`

## What the template does

1. Runs on **Consent Initialization**.
2. Calls `setDefaultConsentState()` using configured default consent values.
3. Reads saved consent from one source:
   - cookie
   - window object
   - dataLayer variable
4. If saved consent is found, calls `updateConsentState()`.
5. Pushes `kk_consent_update` to `dataLayer` with:
   - `consent_source`
   - `consent_analytics`
   - `consent_marketing`
   - `consent_preferences`

## Import and setup

1. Open GTM → **Templates** → **Tag Templates** → **New**.
2. Import `template.tpl`.
3. Create a new tag from template: **KK Consent Banner - Consent Mode v2**.
4. Set defaults (recommended):
   - `security_storage`: `granted`
   - others: `denied`
   - `wait_for_update`: `500`
5. Set consent source and key/name.
6. Trigger: **Consent Initialization - All Pages**.
7. Save and publish container.

## Testing

1. Open GTM Preview mode.
2. Open site in incognito.
3. Confirm default consent appears before regular tags.
4. Accept/reject in the WordPress banner.
5. Verify consent changes and `kk_consent_update` in `window.dataLayer`.

## Notes

- This template focuses only on consent signaling and `dataLayer` integration.
- Keep banner UI, cookie preferences, and user interaction in WordPress plugin.
