(function () {
  'use strict';

  var config = window.kkConsentConfig || {};
  var root = document.getElementById('kk-consent-root');
  if (!root) return;

  var banner = root.querySelector('.kk-consent-banner');
  var panel = root.querySelector('.kk-consent-panel');
  var reopenBtn = root.querySelector('.kk-consent-reopen');
  var btnAccept = root.querySelector('.kk-accept');
  var btnReject = root.querySelector('.kk-reject');
  var btnSettings = root.querySelector('.kk-settings');
  var btnSave = root.querySelector('.kk-save');
  var chkAnalytics = root.querySelector('.kk-analytics');
  var chkMarketing = root.querySelector('.kk-marketing');
  var policyLink = root.querySelector('.kk-consent-policy');

  var labels = config.labels || {};
  btnAccept.textContent = labels.accept || 'Accept';
  btnReject.textContent = labels.reject || 'Reject';
  btnSettings.textContent = labels.settings || 'Settings';
  btnSave.textContent = labels.save || 'Save';
  reopenBtn.textContent = labels.reopen || 'Cookie settings';
  root.querySelector('.kk-necessary-label').textContent = labels.necessary || 'Necessary cookies';
  root.querySelector('.kk-analytics-label').textContent = labels.analytics || 'Analytics';
  root.querySelector('.kk-marketing-label').textContent = labels.marketing || 'Marketing measurement';

  if (config.policyUrl) {
    policyLink.hidden = false;
    policyLink.href = config.policyUrl;
    policyLink.textContent = config.policyUrl;
  }

  function debugLog() {
    if (config.debug) console.log.apply(console, arguments);
  }

  function setCookie(name, value, days) {
    var maxAge = days * 24 * 60 * 60;
    var secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + maxAge + '; SameSite=Lax' + secure;
  }

  function readSaved() {
    var raw = null;
    try { raw = localStorage.getItem(config.storageKey); } catch (e) {}
    if (!raw) {
      var m = document.cookie.match(new RegExp('(^| )' + config.storageKey + '=([^;]+)'));
      if (m && m[2]) raw = decodeURIComponent(m[2]);
    }
    if (!raw) return null;
    try { return JSON.parse(raw); } catch (e) { return null; }
  }

  function persist(choices) {
    var payload = {
      version: (config.storageKey || '').replace('kk_consent_', ''),
      choices: choices,
      created_at: new Date().toISOString()
    };
    var raw = JSON.stringify(payload);
    try { localStorage.setItem(config.storageKey, raw); } catch (e) {}
    setCookie(config.storageKey, raw, config.cookieDays || 180);
    return payload;
  }

  function consentUpdate(choices) {
    var analytics = choices.analytics ? 'granted' : 'denied';
    var marketing = choices.marketing ? 'granted' : 'denied';

    var updatePayload = {
      analytics_storage: analytics,
      ad_storage: marketing,
      ad_user_data: marketing,
      ad_personalization: marketing,
      functionality_storage: 'granted',
      security_storage: 'granted',
      personalization_storage: 'denied'
    };

    if (typeof window.gtag === 'function') {
      window.gtag('consent', 'update', updatePayload);
      debugLog('[KK Consent] update payload', updatePayload);
    }

    var eventPayload = {
      event: 'kk_consent_update',
      kk_consent_analytics: analytics,
      kk_consent_marketing: marketing
    };
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(eventPayload);
    debugLog('[KK Consent] update event', eventPayload);
  }

  function openBanner(showPanel) {
    banner.hidden = false;
    reopenBtn.hidden = true;
    panel.hidden = !showPanel;
  }

  function closeBanner() {
    banner.hidden = true;
    reopenBtn.hidden = false;
    panel.hidden = true;
  }

  var existing = readSaved();
  if (existing && existing.choices) {
    chkAnalytics.checked = !!existing.choices.analytics;
    chkMarketing.checked = !!existing.choices.marketing;
    closeBanner();
  } else {
    openBanner(false);
  }

  btnAccept.addEventListener('click', function () {
    var choices = { analytics: true, marketing: true };
    persist(choices);
    consentUpdate(choices);
    closeBanner();
  });

  btnReject.addEventListener('click', function () {
    var choices = { analytics: false, marketing: false };
    persist(choices);
    consentUpdate(choices);
    closeBanner();
  });

  btnSettings.addEventListener('click', function () { panel.hidden = false; });
  btnSave.addEventListener('click', function () {
    var choices = { analytics: !!chkAnalytics.checked, marketing: !!chkMarketing.checked };
    persist(choices);
    consentUpdate(choices);
    closeBanner();
  });
  reopenBtn.addEventListener('click', function () { openBanner(true); });
})();
