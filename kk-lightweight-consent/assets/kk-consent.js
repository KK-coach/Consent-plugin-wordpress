(function () {
  'use strict';

  var config = window.kkConsentConfig || {};
  var root = document.getElementById('kk-consent-root');
  if (!root) return;

  var banner = root.querySelector('.kk-consent-banner');
  var panel = root.querySelector('.kk-consent-panel');
  var reopenBtn = root.querySelector('.kk-consent-reopen');
  var chkAnalytics = root.querySelector('.kk-analytics');
  var chkMarketing = root.querySelector('.kk-marketing');
  var chkPersonalization = root.querySelector('.kk-personalization');
  var policyLink = root.querySelector('.kk-consent-policy');
  var logo = root.querySelector('.kk-consent-logo');

  var labels = config.labels || {};
  var defaultLabels = {
    accept_all: 'Accept all',
    reject_all: 'Reject all',
    customize: 'Customize',
    save_choices: 'Save choices'
  };

  root.querySelector('.kk-necessary-label').textContent = labels.necessary || 'Necessary cookies';
  root.querySelector('.kk-analytics-label').textContent = labels.analytics || 'Analytics';
  root.querySelector('.kk-marketing-label').textContent = labels.marketing || 'Marketing';
  root.querySelector('.kk-personalization-label').textContent = labels.personalization || 'Personalization';

  root.querySelectorAll('[data-label-key]').forEach(function (button) {
    var key = button.getAttribute('data-label-key');
    button.textContent = labels[key] || defaultLabels[key] || key;
  });

  reopenBtn.textContent = config.reopenIconOnly ? '⚙' : (labels.reopen || 'Cookie settings');
  if (!config.reopenIconOnly) reopenBtn.classList.add('kk-consent-reopen-text');

  if (config.policyUrl) {
    policyLink.href = config.policyUrl;
    policyLink.textContent = labels.more_info || 'More information';
  }

  if (config.logoUrl) {
    logo.src = config.logoUrl;
    logo.hidden = false;
  }

  function debugLog() {
    if (config.debug) console.log.apply(console, arguments);
  }

  function setCookie(name, value, days) {
    var maxAge = days * 86400;
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
  }

  function consentUpdate(choices) {
    var analytics = choices.analytics ? 'granted' : 'denied';
    var marketing = choices.marketing ? 'granted' : 'denied';
    var personalization = choices.personalization ? 'granted' : 'denied';

    var updatePayload = {
      analytics_storage: analytics,
      ad_storage: marketing,
      ad_user_data: marketing,
      ad_personalization: marketing,
      functionality_storage: 'granted',
      security_storage: 'granted',
      personalization_storage: personalization
    };

    if (typeof window.gtag === 'function') {
      window.gtag('consent', 'update', updatePayload);
    }

    var eventPayload = {
      event: 'kk_consent_update',
      kk_consent_analytics: analytics,
      kk_consent_marketing: marketing,
      kk_consent_personalization: personalization
    };

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(eventPayload);

    debugLog('[KK Consent] update payload', updatePayload);
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

  function setChoiceCheckboxes(choices) {
    chkAnalytics.checked = !!choices.analytics;
    chkMarketing.checked = !!choices.marketing;
    chkPersonalization.checked = !!choices.personalization;
  }

  var existing = readSaved();
  if (existing && existing.choices) {
    setChoiceCheckboxes(existing.choices);
    closeBanner();
  } else {
    setChoiceCheckboxes({ analytics: true, marketing: true, personalization: true });
    openBanner(false);
  }

  root.addEventListener('click', function (event) {
    var actionEl = event.target.closest('[data-consent-action]');
    if (!actionEl || !root.contains(actionEl)) return;

    var action = actionEl.getAttribute('data-consent-action');
    var choices;

    if (action === 'accept_all') {
      choices = { analytics: true, marketing: true, personalization: true };
      persist(choices);
      consentUpdate(choices);
      closeBanner();
      return;
    }

    if (action === 'reject_all') {
      choices = { analytics: false, marketing: false, personalization: false };
      setChoiceCheckboxes(choices);
      persist(choices);
      consentUpdate(choices);
      closeBanner();
      return;
    }

    if (action === 'settings') {
      panel.hidden = false;
      return;
    }

    if (action === 'save_choices') {
      choices = {
        analytics: !!chkAnalytics.checked,
        marketing: !!chkMarketing.checked,
        personalization: !!chkPersonalization.checked
      };
      persist(choices);
      consentUpdate(choices);
      closeBanner();
    }
  });

  reopenBtn.addEventListener('click', function () {
    openBanner(true);
  });
})();
