(function () {
  'use strict';

  var config = window.kkConsentConfig || {};
  var root = document.getElementById('lcm-consent-root');
  if (!root) return;

  var banner = root.querySelector('.lcm-consent-banner');
  var panel = root.querySelector('.lcm-consent-panel');
  var reopenBtn = root.querySelector('.lcm-consent-reopen');
  var chkAnalytics = root.querySelector('.lcm-analytics');
  var chkMarketing = root.querySelector('.lcm-marketing');
  var chkPersonalization = root.querySelector('.lcm-personalization');
  var policyLink = root.querySelector('.lcm-consent-policy');
  var logo = root.querySelector('.lcm-consent-logo');

  var translations = config.translations || {};

  function detectBrowserLanguage() {
    var langs = Array.isArray(navigator.languages) && navigator.languages.length ? navigator.languages : [navigator.language || 'en'];
    for (var i = 0; i < langs.length; i++) {
      var l = String(langs[i] || '').toLowerCase();
      if (l.indexOf('hu') === 0) return 'hu';
      if (l.indexOf('en') === 0) return 'en';
    }
    return 'en';
  }

  var selectedLang = 'en';
  if (config.languageMode === 'hu' || config.languageMode === 'en') {
    selectedLang = config.languageMode;
  } else {
    selectedLang = detectBrowserLanguage();
  }

  function t(key) {
    if (translations[selectedLang] && translations[selectedLang][key]) return translations[selectedLang][key];
    if (translations.en && translations.en[key]) return translations.en[key];
    return key;
  }

  function tHtml(key) {
    if (translations[selectedLang] && translations[selectedLang][key]) return translations[selectedLang][key];
    if (translations.en && translations.en[key]) return translations.en[key];
    return '';
  }

  banner.setAttribute('aria-label', t('dialog_label'));
  root.querySelector('.lcm-consent-title').innerHTML = tHtml('banner_title');
  root.querySelector('.lcm-consent-text').innerHTML = tHtml('banner_text');
  root.querySelector('.lcm-necessary-label').textContent = t('necessary');
  root.querySelector('.lcm-necessary-desc').innerHTML = tHtml('necessary_desc');
  root.querySelector('.lcm-analytics-label').textContent = t('analytics');
  root.querySelector('.lcm-marketing-label').textContent = t('marketing');
  root.querySelector('.lcm-personalization-label').textContent = t('personalization');
  root.querySelector('.lcm-analytics-desc').innerHTML = tHtml('analytics_desc');
  root.querySelector('.lcm-marketing-desc').innerHTML = tHtml('marketing_desc');
  root.querySelector('.lcm-personalization-desc').innerHTML = tHtml('personalization_desc');
  root.querySelector('.lcm-panel-intro').innerHTML = tHtml('panel_intro');

  root.querySelectorAll('[data-label-key]').forEach(function (button) {
    button.innerHTML = tHtml(button.getAttribute('data-label-key'));
  });

  reopenBtn.setAttribute('aria-label', t('reopen'));
  reopenBtn.setAttribute('title', t('reopen'));
  if (!config.reopenIconOnly) {
    reopenBtn.classList.add('lcm-consent-reopen-text');
    reopenBtn.innerHTML = tHtml('reopen_html');
  }

  if (config.policyUrl) {
    policyLink.href = config.policyUrl;
    policyLink.innerHTML = tHtml('more_info');
  }

  if (config.logoUrl) {
    logo.src = config.logoUrl;
    logo.hidden = false;
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
    var payload = { version: (config.storageKey || '').replace('kk_consent_', ''), choices: choices, created_at: new Date().toISOString() };
    var raw = JSON.stringify(payload);
    try { localStorage.setItem(config.storageKey, raw); } catch (e) {}
    setCookie(config.storageKey, raw, config.cookieDays || 180);
  }

  function consentState(choices) {
    return {
      kk_consent_analytics: choices.analytics ? 'granted' : 'denied',
      kk_consent_marketing: choices.marketing ? 'granted' : 'denied',
      kk_consent_personalization: choices.personalization ? 'granted' : 'denied'
    };
  }

  function pushReady(choices, status) {
    var state = consentState(choices);
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'kk_consent_ready',
      kk_consent_status: status,
      kk_consent_analytics: state.kk_consent_analytics,
      kk_consent_marketing: state.kk_consent_marketing,
      kk_consent_personalization: state.kk_consent_personalization
    });
    window.lcmConsentReadyPushed = true;
  }

  function consentUpdate(choices, status) {
    var state = consentState(choices);
    var analytics = state.kk_consent_analytics;
    var marketing = state.kk_consent_marketing;
    var personalization = state.kk_consent_personalization;

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

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'kk_consent_update',
      kk_consent_analytics: analytics,
      kk_consent_marketing: marketing,
      kk_consent_personalization: personalization
    });
    pushReady(choices, status);
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

  function setChoices(choices) {
    chkAnalytics.checked = !!choices.analytics;
    chkMarketing.checked = !!choices.marketing;
    chkPersonalization.checked = !!choices.personalization;
  }

  var existing = readSaved();
  if (existing && existing.choices) {
    setChoices(existing.choices);
    closeBanner();
    if (!window.lcmConsentReadyPushed) {
      pushReady(existing.choices, 'saved');
    }
  } else {
    setChoices({
      analytics: !!config.defaultAnalytics,
      marketing: !!config.defaultMarketing,
      personalization: !!config.defaultPersonalization
    });
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
      consentUpdate(choices, 'accepted');
      closeBanner();
      return;
    }

    if (action === 'reject_all') {
      choices = { analytics: false, marketing: false, personalization: false };
      setChoices(choices);
      persist(choices);
      consentUpdate(choices, 'rejected');
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
      consentUpdate(choices, 'custom');
      closeBanner();
    }
  });

  reopenBtn.addEventListener('click', function () {
    openBanner(true);
  });
})();
