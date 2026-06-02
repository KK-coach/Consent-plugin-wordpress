___INFO___
{
  "type": "TAG",
  "id": "kk_consent_banner_consent_mode_v2",
  "displayName": "KK Consent Banner - Consent Mode v2",
  "version": 1,
  "description": "Applies Consent Mode v2 defaults and updates from a saved consent object created by the WordPress plugin.",
  "categories": ["CONSENT_MANAGEMENT"]
}
___TEMPLATE_PARAMETERS___
[
  {"type":"GROUP","name":"defaultsGroup","displayName":"Default consent states","subParams":[
    {"type":"SELECT","name":"default_ad_storage","displayName":"ad_storage","simpleValueType":true,"defaultValue":"denied","selectItems":[{"value":"granted","displayValue":"granted"},{"value":"denied","displayValue":"denied"}]},
    {"type":"SELECT","name":"default_analytics_storage","displayName":"analytics_storage","simpleValueType":true,"defaultValue":"denied","selectItems":[{"value":"granted","displayValue":"granted"},{"value":"denied","displayValue":"denied"}]},
    {"type":"SELECT","name":"default_ad_user_data","displayName":"ad_user_data","simpleValueType":true,"defaultValue":"denied","selectItems":[{"value":"granted","displayValue":"granted"},{"value":"denied","displayValue":"denied"}]},
    {"type":"SELECT","name":"default_ad_personalization","displayName":"ad_personalization","simpleValueType":true,"defaultValue":"denied","selectItems":[{"value":"granted","displayValue":"granted"},{"value":"denied","displayValue":"denied"}]},
    {"type":"SELECT","name":"default_functionality_storage","displayName":"functionality_storage","simpleValueType":true,"defaultValue":"denied","selectItems":[{"value":"granted","displayValue":"granted"},{"value":"denied","displayValue":"denied"}]},
    {"type":"SELECT","name":"default_personalization_storage","displayName":"personalization_storage","simpleValueType":true,"defaultValue":"denied","selectItems":[{"value":"granted","displayValue":"granted"},{"value":"denied","displayValue":"denied"}]},
    {"type":"SELECT","name":"default_security_storage","displayName":"security_storage","simpleValueType":true,"defaultValue":"granted","selectItems":[{"value":"granted","displayValue":"granted"},{"value":"denied","displayValue":"denied"}]}
  ]},
  {"type":"TEXT","name":"wait_for_update","displayName":"wait_for_update (ms)","simpleValueType":true,"valueValidators":[{"type":"NON_EMPTY"}],"defaultValue":"500"},
  {"type":"TEXT","name":"consent_cookie_name","displayName":"Consent cookie name","simpleValueType":true,"defaultValue":"kk_consent"},
  {"type":"SELECT","name":"consent_source","displayName":"Consent source","simpleValueType":true,"defaultValue":"cookie","selectItems":[{"value":"cookie","displayValue":"cookie"},{"value":"window","displayValue":"window object"},{"value":"dataLayer","displayValue":"dataLayer variable"}]},
  {"type":"TEXT","name":"window_object_path","displayName":"Window object path","simpleValueType":true,"defaultValue":"kkConsentState","enablingConditions":[{"paramName":"consent_source","type":"EQUALS","value":"window"}]},
  {"type":"TEXT","name":"datalayer_key","displayName":"dataLayer key","simpleValueType":true,"defaultValue":"kkConsentState","enablingConditions":[{"paramName":"consent_source","type":"EQUALS","value":"dataLayer"}]},
  {"type":"CHECKBOX","name":"debug_mode","displayName":"Enable debug mode","simpleValueType":true,"defaultValue":false}
]
___SANDBOXED_JS_FOR_WEB_TEMPLATE___
const setDefaultConsentState = require('setDefaultConsentState');
const updateConsentState = require('updateConsentState');
const getCookieValues = require('getCookieValues');
const copyFromWindow = require('copyFromWindow');
const copyFromDataLayer = require('copyFromDataLayer');
const createQueue = require('createQueue');
const log = require('logToConsole');

const pushDataLayer = createQueue('dataLayer');

const grantedOrDenied = (value) => value === 'granted' ? 'granted' : 'denied';

const defaults = {
  ad_storage: grantedOrDenied(data.default_ad_storage),
  analytics_storage: grantedOrDenied(data.default_analytics_storage),
  ad_user_data: grantedOrDenied(data.default_ad_user_data),
  ad_personalization: grantedOrDenied(data.default_ad_personalization),
  functionality_storage: grantedOrDenied(data.default_functionality_storage),
  personalization_storage: grantedOrDenied(data.default_personalization_storage),
  security_storage: grantedOrDenied(data.default_security_storage),
  wait_for_update: Number(data.wait_for_update) || 500
};

setDefaultConsentState(defaults);

const parseJson = (raw) => {
  if (!raw) return null;
  if (typeof raw === 'object') return raw;
  try { return JSON.parse(raw); } catch (e) { return null; }
};

const resolveSavedConsent = () => {
  if (data.consent_source === 'window') {
    return parseJson(copyFromWindow(data.window_object_path || 'kkConsentState'));
  }
  if (data.consent_source === 'dataLayer') {
    return parseJson(copyFromDataLayer(data.datalayer_key || 'kkConsentState'));
  }
  const name = data.consent_cookie_name || 'kk_consent';
  const values = getCookieValues(name);
  if (!values || !values.length) return null;
  return parseJson(values[0]);
};

const boolFrom = (value) => value === true || value === 'true' || value === 'granted';

const saved = resolveSavedConsent();
if (saved && saved.choices) {
  const analytics = boolFrom(saved.choices.analytics);
  const marketing = boolFrom(saved.choices.marketing);
  const preferences = boolFrom(saved.choices.preferences);

  const update = {
    analytics_storage: analytics ? 'granted' : 'denied',
    ad_storage: marketing ? 'granted' : 'denied',
    ad_user_data: marketing ? 'granted' : 'denied',
    ad_personalization: marketing ? 'granted' : 'denied',
    functionality_storage: preferences ? 'granted' : 'denied',
    personalization_storage: preferences ? 'granted' : 'denied',
    security_storage: 'granted'
  };

  updateConsentState(update);

  pushDataLayer({
    event: 'kk_consent_update',
    consent_source: data.consent_source || 'cookie',
    consent_analytics: analytics,
    consent_marketing: marketing,
    consent_preferences: preferences
  });

  if (data.debug_mode) {
    log('KK Consent Template defaults', defaults);
    log('KK Consent Template update', update);
  }
}

data.gtmOnSuccess();
___WEB_PERMISSIONS___
[
  {"instance":"access_consent","consentTypes":["ad_storage","analytics_storage","ad_user_data","ad_personalization","functionality_storage","personalization_storage","security_storage"],"isWrite":true},
  {"instance":"get_cookies","name":"{{consent_cookie_name}}"},
  {"instance":"access_globals","key":"{{window_object_path}}","read":true},
  {"instance":"read_data_layer","key":"{{datalayer_key}}"},
  {"instance":"access_globals","key":"dataLayer","read":true,"write":true}
]
