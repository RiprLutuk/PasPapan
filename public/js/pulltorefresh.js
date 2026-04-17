/*!
 * App pull-to-refresh helper
 * Refactored for the absensi-gps-barcode UI theme, accessibility, and touch safety.
 */
(function (global, factory) {
  if (typeof exports === 'object' && typeof module !== 'undefined') {
    module.exports = factory();
    return;
  }

  if (typeof define === 'function' && define.amd) {
    define(factory);
    return;
  }

  global = global || self;
  global.PullToRefresh = factory();
}(this, function () {
  'use strict';

  var shared = {
    handlers: [],
    active: null,
    events: null,
    passive: false,
    pointerEventsEnabled: false,
    supportsPassive: false,
    supportsPointerEvents: typeof window !== 'undefined' && 'PointerEvent' in window,
    styleRegistry: {}
  };

  try {
    window.addEventListener('ptr-passive-test', null, {
      get passive() {
        shared.supportsPassive = true;
        return true;
      }
    });
  } catch (error) {
    // Passive listener support is optional.
  }

  function getDocumentLanguage() {
    if (typeof document === 'undefined' || !document.documentElement) {
      return 'en';
    }

    return (document.documentElement.getAttribute('lang') || 'en').toLowerCase();
  }

  function getDefaultMessages() {
    var isIndonesian = getDocumentLanguage().indexOf('id') === 0;

    if (isIndonesian) {
      return {
        pull: 'Tarik untuk memuat ulang',
        release: 'Lepas untuk memuat ulang',
        refreshing: 'Memuat ulang',
        hintPull: 'Tarik perlahan dari bagian atas halaman.',
        hintRelease: 'Lepaskan sekarang untuk memperbarui halaman.',
        hintRefreshing: 'Konten sedang disegarkan, mohon tunggu sebentar.'
      };
    }

    return {
      pull: 'Pull to refresh',
      release: 'Release to refresh',
      refreshing: 'Refreshing',
      hintPull: 'Pull down gently from the top of the page.',
      hintRelease: 'Release now to reload the current page.',
      hintRefreshing: 'Content is refreshing. Please wait a moment.'
    };
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function noop() {}

  function getScrollTop(element) {
    if (!element || element === document.body || element === document.documentElement) {
      return Math.max(window.scrollY || 0, document.documentElement.scrollTop || 0, document.body.scrollTop || 0);
    }

    return element.scrollTop;
  }

  function resolveElement(value) {
    if (typeof value === 'string') {
      return document.querySelector(value);
    }

    return value || null;
  }

  function getEventScreenY(event) {
    if (shared.pointerEventsEnabled && shared.supportsPointerEvents && typeof event.screenY === 'number') {
      return event.screenY;
    }

    if (event.touches && event.touches[0]) {
      return event.touches[0].screenY;
    }

    if (event.changedTouches && event.changedTouches[0]) {
      return event.changedTouches[0].screenY;
    }

    return 0;
  }

  function shouldIgnoreStartEvent(event) {
    if (!event) {
      return true;
    }

    if (typeof event.button === 'number' && event.button !== 0) {
      return true;
    }

    if (typeof event.pointerType === 'string' && event.pointerType === 'mouse') {
      return true;
    }

    return false;
  }

  function normalizePrefix(prefix) {
    return String(prefix || 'ptr--').replace(/[^a-z0-9_-]/gi, '-');
  }

  function buildStyleId(prefix) {
    return 'pull-to-refresh-style-' + normalizePrefix(prefix);
  }

  function getMarkup() {
    return [
      '<div class="__PREFIX__surface" role="status" aria-live="polite" aria-atomic="true">',
      '  <div class="__PREFIX__indicator" aria-hidden="true">',
      '    <span class="__PREFIX__indicator-shell">',
      '      <span class="__PREFIX__brand"></span>',
      '      <span class="__PREFIX__motion"></span>',
      '      <span class="__PREFIX__icon"></span>',
      '    </span>',
      '  </div>',
      '  <div class="__PREFIX__copy">',
      '    <p class="__PREFIX__title"></p>',
      '    <p class="__PREFIX__hint"></p>',
      '  </div>',
      '</div>'
    ].join('');
  }

  function getStyles() {
    return [
      '.__PREFIX__ptr {',
      '  --ptr-progress: 0;',
      '  --ptr-border: rgba(87, 148, 74, 0.14);',
      '  --ptr-border-strong: rgba(87, 148, 74, 0.3);',
      '  --ptr-surface: rgba(255, 255, 255, 0.97);',
      '  --ptr-surface-alt: rgba(226, 240, 223, 0.88);',
      '  --ptr-surface-dark: rgba(17, 24, 39, 0.96);',
      '  --ptr-surface-dark-alt: rgba(21, 35, 18, 0.78);',
      '  --ptr-text: #163020;',
      '  --ptr-text-muted: #4b6354;',
      '  --ptr-accent: #57944a;',
      '  --ptr-accent-strong: #44733a;',
      '  --ptr-accent-soft: rgba(106, 180, 91, 0.18);',
      '  --ptr-shadow: 0 24px 54px -36px rgba(34, 64, 41, 0.38);',
      '  pointer-events: none;',
      '  position: relative;',
      '  z-index: 30;',
      '  display: flex;',
      '  width: 100%;',
      '  min-height: 0;',
      '  max-height: 0;',
      '  overflow: hidden;',
      '  align-items: flex-end;',
      '  justify-content: center;',
      '  transition: min-height 220ms ease, max-height 220ms ease;',
      '}',
      '.__PREFIX__surface {',
      '  box-sizing: border-box;',
      '  position: relative;',
      '  width: calc(100% - 1rem);',
      '  max-width: 48rem;',
      '  margin: 0.5rem auto 0;',
      '  display: grid;',
      '  grid-template-columns: auto minmax(0, 1fr);',
      '  align-items: center;',
      '  gap: 0.875rem;',
      '  border-radius: 1.4rem;',
      '  border: 1px solid var(--ptr-border);',
      '  background: linear-gradient(150deg, var(--ptr-surface-alt), var(--ptr-surface) 58%, rgba(255,255,255,0.98));',
      '  box-shadow: var(--ptr-shadow);',
      '  padding: 0.95rem 1rem;',
      '  backdrop-filter: blur(12px);',
      '  overflow: hidden;',
      '}',
      '.__PREFIX__surface::before {',
      '  content: "";',
      '  position: absolute;',
      '  inset: 0;',
      '  background: radial-gradient(circle at top right, rgba(87, 148, 74, 0.18), transparent 34%), radial-gradient(circle at bottom left, rgba(87, 148, 74, 0.12), transparent 28%);',
      '  pointer-events: none;',
      '}',
      '.__PREFIX__indicator {',
      '  display: inline-flex;',
      '  position: relative;',
      '  z-index: 1;',
      '  height: 3.2rem;',
      '  width: 3.2rem;',
      '  align-items: center;',
      '  justify-content: center;',
      '}',
      '.__PREFIX__indicator-shell {',
      '  position: relative;',
      '  display: inline-flex;',
      '  height: 100%;',
      '  width: 100%;',
      '  align-items: center;',
      '  justify-content: center;',
      '  border-radius: 1.15rem;',
      '  background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,255,255,0.9));',
      '  border: 1px solid rgba(87, 148, 74, 0.16);',
      '  box-shadow: 0 18px 34px -24px rgba(68, 115, 58, 0.5);',
      '}',
      '.__PREFIX__brand {',
      '  display: inline-flex;',
      '  height: 2.35rem;',
      '  width: 2.35rem;',
      '  border-radius: 9999px;',
      '  background-color: #ffffff;',
      '  background-image: url("/images/icons/logo.jpeg");',
      '  background-position: center;',
      '  background-repeat: no-repeat;',
      '  background-size: cover;',
      '  box-shadow: 0 12px 24px -20px rgba(34, 64, 41, 0.45);',
      '}',
      '.__PREFIX__motion {',
      '  position: absolute;',
      '  inset: 0.18rem;',
      '  border-radius: 1rem;',
      '  border: 2px solid transparent;',
      '  border-top-color: var(--ptr-accent);',
      '  border-right-color: rgba(87, 148, 74, 0.26);',
      '  opacity: 0;',
      '  transform: scale(0.9);',
      '  transition: opacity 180ms ease, transform 180ms ease;',
      '}',
      '.__PREFIX__icon {',
      '  position: absolute;',
      '  right: -0.12rem;',
      '  bottom: -0.12rem;',
      '  display: inline-flex;',
      '  height: 1.15rem;',
      '  width: 1.15rem;',
      '  align-items: center;',
      '  justify-content: center;',
      '  border-radius: 9999px;',
      '  background: linear-gradient(180deg, var(--ptr-accent), var(--ptr-accent-strong));',
      '  color: #ffffff;',
      '  box-shadow: 0 10px 20px -16px rgba(68, 115, 58, 0.85);',
      '  font-size: 0.65rem;',
      '  line-height: 1;',
      '  transform: rotate(calc(var(--ptr-progress) * 180deg));',
      '  transition: transform 180ms ease, opacity 180ms ease;',
      '}',
      '.__PREFIX__copy {',
      '  position: relative;',
      '  z-index: 1;',
      '  min-width: 0;',
      '}',
      '.__PREFIX__title {',
      '  margin: 0;',
      '  font-size: 0.95rem;',
      '  font-weight: 700;',
      '  line-height: 1.35;',
      '  color: var(--ptr-text);',
      '}',
      '.__PREFIX__hint {',
      '  margin: 0.2rem 0 0;',
      '  font-size: 0.8rem;',
      '  line-height: 1.45;',
      '  color: var(--ptr-text-muted);',
      '}',
      '.__PREFIX__pull {',
      '  transition: none;',
      '}',
      '.__PREFIX__release .__PREFIX__surface {',
      '  border-color: var(--ptr-border-strong);',
      '}',
      '.__PREFIX__release .__PREFIX__icon {',
      '  transform: rotate(180deg);',
      '}',
      '.__PREFIX__refresh .__PREFIX__icon {',
      '  opacity: 0;',
      '}',
      '.__PREFIX__refresh .__PREFIX__motion {',
      '  opacity: 1;',
      '  transform: scale(1);',
      '  animation: __PREFIX__spin 900ms linear infinite;',
      '}',
      '.__PREFIX__top {',
      '  touch-action: pan-x pan-down pinch-zoom;',
      '}',
      '@keyframes __PREFIX__spin {',
      '  from { transform: rotate(0deg); }',
      '  to { transform: rotate(360deg); }',
      '}',
      'html.dark .__PREFIX__surface {',
      '  background: linear-gradient(150deg, var(--ptr-surface-dark-alt), var(--ptr-surface-dark) 60%, rgba(17,24,39,0.98));',
      '  border-color: rgba(132, 193, 120, 0.16);',
      '  box-shadow: 0 18px 40px -32px rgba(0, 0, 0, 0.78);',
      '}',
      'html.dark .__PREFIX__surface::before {',
      '  background: radial-gradient(circle at top right, rgba(132, 193, 120, 0.14), transparent 34%), radial-gradient(circle at bottom left, rgba(87, 148, 74, 0.12), transparent 28%);',
      '}',
      'html.dark .__PREFIX__indicator-shell {',
      '  background: linear-gradient(180deg, rgba(31,41,55,0.96), rgba(17,24,39,0.96));',
      '  border-color: rgba(132, 193, 120, 0.16);',
      '  box-shadow: 0 18px 34px -24px rgba(0, 0, 0, 0.75);',
      '}',
      'html.dark .__PREFIX__title {',
      '  color: #f8fafc;',
      '}',
      'html.dark .__PREFIX__hint {',
      '  color: #cbd5e1;',
      '}',
      '@media (max-width: 639px) {',
      '  .__PREFIX__surface {',
      '    width: calc(100% - 0.75rem);',
      '    gap: 0.75rem;',
      '    padding: 0.8rem 0.875rem;',
      '    border-radius: 1.1rem;',
      '  }',
      '  .__PREFIX__indicator {',
      '    height: 2.9rem;',
      '    width: 2.9rem;',
      '  }',
      '  .__PREFIX__brand {',
      '    height: 2.05rem;',
      '    width: 2.05rem;',
      '  }',
      '  .__PREFIX__title {',
      '    font-size: 0.9rem;',
      '  }',
      '}',
      '@media (prefers-reduced-motion: reduce) {',
      '  .__PREFIX__ptr,',
      '  .__PREFIX__icon {',
      '    transition: none !important;',
      '    animation: none !important;',
      '  }',
      '}',
      '@media (forced-colors: active) {',
      '  .__PREFIX__surface {',
      '    border: 1px solid CanvasText;',
      '    background: Canvas;',
      '    box-shadow: none;',
      '  }',
      '  .__PREFIX__indicator {',
      '    background: ButtonText;',
      '    color: ButtonFace;',
      '    box-shadow: none;',
      '  }',
      '  .__PREFIX__title,',
      '  .__PREFIX__hint {',
      '    color: CanvasText;',
      '  }',
      '}'
    ].join('\n');
  }

  var localizedMessages = getDefaultMessages();

  var defaults = {
    distThreshold: 72,
    distMax: 108,
    distReload: 64,
    distIgnore: 8,
    mainElement: 'body',
    triggerElement: 'body',
    ptrElement: null,
    classPrefix: 'ptr--',
    cssProp: 'min-height',
    iconArrow: '<svg viewBox="0 0 24 24" width="10" height="10" fill="none" aria-hidden="true"><path d="M12 5.25v10.5m0 0-4.25-4.25M12 15.75l4.25-4.25" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    iconRefreshing: '<svg viewBox="0 0 24 24" width="10" height="10" fill="none" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M20 4v5h-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    instructionsPullToRefresh: localizedMessages.pull,
    instructionsReleaseToRefresh: localizedMessages.release,
    instructionsRefreshing: localizedMessages.refreshing,
    instructionsPullHint: localizedMessages.hintPull,
    instructionsReleaseHint: localizedMessages.hintRelease,
    instructionsRefreshingHint: localizedMessages.hintRefreshing,
    refreshTimeout: 240,
    getMarkup: getMarkup,
    getStyles: getStyles,
    onInit: noop,
    onRefresh: function onRefresh() {
      return location.reload();
    },
    resistanceFunction: function resistanceFunction(value) {
      return Math.min(1, value / 2.15);
    },
    shouldPullToRefresh: function shouldPullToRefresh() {
      if (document.body && document.body.classList.contains('is-native-scanning')) {
        return false;
      }

      return getScrollTop(this.mainElement) <= 0;
    }
  };

  function ensureStyles(handler) {
    var styleId = buildStyleId(handler.classPrefix);

    if (shared.styleRegistry[styleId] || document.getElementById(styleId)) {
      shared.styleRegistry[styleId] = true;
      return;
    }

    var style = document.createElement('style');
    style.id = styleId;
    style.textContent = handler.getStyles(handler).replace(/__PREFIX__/g, handler.classPrefix);
    document.head.appendChild(style);
    shared.styleRegistry[styleId] = true;
  }

  function getStateCopy(handler, state) {
    if (state === 'refreshing') {
      return {
        title: handler.instructionsRefreshing,
        hint: handler.instructionsRefreshingHint,
        icon: handler.iconRefreshing
      };
    }

    if (state === 'releasing') {
      return {
        title: handler.instructionsReleaseToRefresh,
        hint: handler.instructionsReleaseHint,
        icon: handler.iconArrow
      };
    }

    return {
      title: handler.instructionsPullToRefresh,
      hint: handler.instructionsPullHint,
      icon: handler.iconArrow
    };
  }

  function updateUI(handler) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    var state = handler.state || 'pending';
    var copy = getStateCopy(handler, state);
    var root = handler.ptrElement;
    var iconEl = root.querySelector('.' + handler.classPrefix + 'icon');
    var titleEl = root.querySelector('.' + handler.classPrefix + 'title');
    var hintEl = root.querySelector('.' + handler.classPrefix + 'hint');
    var surfaceEl = root.querySelector('.' + handler.classPrefix + 'surface');

    if (iconEl) {
      iconEl.innerHTML = copy.icon;
    }

    if (titleEl) {
      titleEl.textContent = copy.title;
    }

    if (hintEl) {
      hintEl.textContent = copy.hint;
    }

    if (surfaceEl) {
      surfaceEl.setAttribute('aria-busy', state === 'refreshing' ? 'true' : 'false');
    }
  }

  function setState(handler, nextState) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    handler.state = nextState;
    handler.ptrElement.classList.toggle(handler.classPrefix + 'pull', nextState === 'pulling');
    handler.ptrElement.classList.toggle(handler.classPrefix + 'release', nextState === 'releasing');
    handler.ptrElement.classList.toggle(handler.classPrefix + 'refresh', nextState === 'refreshing');
    updateUI(handler);
  }

  function setVisualDistance(handler, distance) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    var safeDistance = Math.max(0, Math.round(distance));
    var progress = clamp(safeDistance / handler.distThreshold, 0, 1);

    handler.ptrElement.style[handler.cssProp] = safeDistance + 'px';
    handler.ptrElement.style.maxHeight = safeDistance + 'px';
    handler.ptrElement.style.setProperty('--ptr-progress', progress.toFixed(3));
  }

  function teardownDOM(handler) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    var element = handler.ptrElement;
    handler.ptrElement = null;

    if (element.parentNode) {
      element.parentNode.removeChild(element);
    }
  }

  function resetActiveState() {
    shared.active = null;
  }

  function scheduleTeardown(handler, delay) {
    window.setTimeout(function () {
      if (shared.active && shared.active.handler === handler && handler.state === 'refreshing') {
        return;
      }

      teardownDOM(handler);

      if (!shared.active || shared.active.handler === handler) {
        resetActiveState();
      }
    }, typeof delay === 'number' ? delay : 240);
  }

  function collapse(handler) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    setVisualDistance(handler, 0);
    setState(handler, 'pending');
    scheduleTeardown(handler, 220);
  }

  function finishRefresh(handler) {
    if (!handler) {
      return;
    }

    collapse(handler);
  }

  function beginRefresh(handler) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    setState(handler, 'refreshing');
    setVisualDistance(handler, handler.distReload);

    window.setTimeout(function () {
      var doneCalled = false;

      function done() {
        if (doneCalled) {
          return;
        }

        doneCalled = true;
        finishRefresh(handler);
      }

      var result = handler.onRefresh(done);

      if (result && typeof result.then === 'function') {
        result.then(done, done);
        return;
      }

      if (!result && handler.onRefresh.length === 0) {
        done();
      }
    }, handler.refreshTimeout);
  }

  function setupDOM(handler) {
    if (handler.ptrElement) {
      return handler;
    }

    var container = document.createElement('div');
    var parent = handler.mainElement === document.body ? document.body : handler.mainElement.parentNode;

    if (!parent) {
      return handler;
    }

    container.className = handler.classPrefix + 'ptr';
    container.innerHTML = handler.getMarkup(handler).replace(/__PREFIX__/g, handler.classPrefix);

    if (handler.mainElement !== document.body) {
      parent.insertBefore(container, handler.mainElement);
    } else {
      parent.insertBefore(container, parent.firstChild);
    }

    handler.ptrElement = container;
    ensureStyles(handler);
    handler.onInit(handler);
    updateUI(handler);

    return handler;
  }

  function findHandler(target) {
    var index;

    for (index = 0; index < shared.handlers.length; index += 1) {
      if (shared.handlers[index].contains(target)) {
        return shared.handlers[index];
      }
    }

    return null;
  }

  function createEventBindings() {
    var passiveOptions = shared.supportsPassive ? { passive: shared.passive } : false;

    function onStart(event) {
      if (shouldIgnoreStartEvent(event)) {
        return;
      }

      var handler = findHandler(event.target);

      if (!handler || handler.state === 'refreshing') {
        return;
      }

      setupDOM(handler);

      shared.active = {
        handler: handler,
        startY: handler.shouldPullToRefresh() ? getEventScreenY(event) : null,
        currentY: null,
        dist: 0,
        distResisted: 0
      };

      handler.ptrElement.classList.toggle(handler.classPrefix + 'top', handler.shouldPullToRefresh());
      setState(handler, 'pending');
      setVisualDistance(handler, 0);
    }

    function onMove(event) {
      if (!shared.active || !shared.active.handler) {
        return;
      }

      var active = shared.active;
      var handler = active.handler;

      if (!handler.ptrElement) {
        return;
      }

      if (!active.startY && handler.shouldPullToRefresh()) {
        active.startY = getEventScreenY(event);
      }

      if (!active.startY) {
        return;
      }

      active.currentY = getEventScreenY(event);

      if (active.currentY <= active.startY) {
        return;
      }

      active.dist = active.currentY - active.startY;

      if (handler.state === 'refreshing') {
        if (event.cancelable && handler.shouldPullToRefresh()) {
          event.preventDefault();
        }

        return;
      }

      if (!handler.shouldPullToRefresh()) {
        return;
      }

      var extraDistance = active.dist - handler.distIgnore;

      if (extraDistance <= 0) {
        return;
      }

      active.distResisted = handler.resistanceFunction(extraDistance / handler.distThreshold) * Math.min(handler.distMax, extraDistance);

      if (event.cancelable) {
        event.preventDefault();
      }

      setVisualDistance(handler, active.distResisted);

      if (active.distResisted >= handler.distThreshold) {
        setState(handler, 'releasing');
      } else {
        setState(handler, 'pulling');
      }
    }

    function onEnd() {
      if (!shared.active || !shared.active.handler) {
        return;
      }

      var handler = shared.active.handler;
      var distResisted = shared.active.distResisted || 0;

      if (handler.state === 'releasing' && distResisted >= handler.distThreshold) {
        beginRefresh(handler);
      } else if (handler.state !== 'refreshing') {
        collapse(handler);
      }

      if (!shared.active || shared.active.handler === handler) {
        shared.active = handler.state === 'refreshing' ? { handler: handler } : null;
      }
    }

    function onScroll() {
      var index;

      for (index = 0; index < shared.handlers.length; index += 1) {
        var handler = shared.handlers[index];

        if (handler.ptrElement) {
          handler.ptrElement.classList.toggle(handler.classPrefix + 'top', handler.shouldPullToRefresh());
        }
      }
    }

    if (shared.pointerEventsEnabled && shared.supportsPointerEvents) {
      window.addEventListener('pointerdown', onStart, passiveOptions);
      window.addEventListener('pointermove', onMove, passiveOptions);
      window.addEventListener('pointerup', onEnd, passiveOptions);
      window.addEventListener('pointercancel', onEnd, passiveOptions);
    } else {
      window.addEventListener('touchstart', onStart, passiveOptions);
      window.addEventListener('touchmove', onMove, passiveOptions);
      window.addEventListener('touchend', onEnd, passiveOptions);
      window.addEventListener('touchcancel', onEnd, passiveOptions);
    }

    window.addEventListener('scroll', onScroll, passiveOptions);

    return {
      destroy: function destroy() {
        if (shared.pointerEventsEnabled && shared.supportsPointerEvents) {
          window.removeEventListener('pointerdown', onStart, passiveOptions);
          window.removeEventListener('pointermove', onMove, passiveOptions);
          window.removeEventListener('pointerup', onEnd, passiveOptions);
          window.removeEventListener('pointercancel', onEnd, passiveOptions);
        } else {
          window.removeEventListener('touchstart', onStart, passiveOptions);
          window.removeEventListener('touchmove', onMove, passiveOptions);
          window.removeEventListener('touchend', onEnd, passiveOptions);
          window.removeEventListener('touchcancel', onEnd, passiveOptions);
        }

        window.removeEventListener('scroll', onScroll, passiveOptions);
      }
    };
  }

  function setupHandler(options) {
    var handler = {};
    var key;

    options = options || {};

    for (key in defaults) {
      if (Object.prototype.hasOwnProperty.call(defaults, key)) {
        handler[key] = Object.prototype.hasOwnProperty.call(options, key) ? options[key] : defaults[key];
      }
    }

    handler.classPrefix = normalizePrefix(handler.classPrefix);
    handler.refreshTimeout = typeof options.refreshTimeout === 'number' ? options.refreshTimeout : defaults.refreshTimeout;
    handler.mainElement = resolveElement(handler.mainElement);
    handler.triggerElement = resolveElement(handler.triggerElement);
    handler.ptrElement = resolveElement(handler.ptrElement);
    handler.state = 'pending';

    if (!handler.mainElement || !handler.triggerElement) {
      throw new Error('PullToRefresh could not resolve mainElement or triggerElement.');
    }

    if (!shared.events) {
      shared.events = createEventBindings();
    }

    handler.contains = function contains(target) {
      return !!(handler.triggerElement && target && handler.triggerElement.contains(target));
    };

    handler.destroy = function destroy() {
      teardownDOM(handler);

      var index = shared.handlers.indexOf(handler);

      if (index >= 0) {
        shared.handlers.splice(index, 1);
      }

      if (shared.active && shared.active.handler === handler) {
        resetActiveState();
      }
    };

    return handler;
  }

  var api = {
    setPassiveMode: function setPassiveMode(isPassive) {
      shared.passive = !!isPassive;
    },

    setPointerEventsMode: function setPointerEventsMode(isEnabled) {
      shared.pointerEventsEnabled = !!isEnabled;
    },

    destroyAll: function destroyAll() {
      while (shared.handlers.length) {
        shared.handlers[0].destroy();
      }

      if (shared.events) {
        shared.events.destroy();
        shared.events = null;
      }

      resetActiveState();
    },

    init: function init(options) {
      var handler = setupHandler(options);
      shared.handlers.push(handler);
      return handler;
    },

    defaults: defaults,

    _: {
      setupHandler: setupHandler,
      setupDOM: setupDOM,
      updateUI: updateUI,
      collapse: collapse,
      finishRefresh: finishRefresh
    }
  };

  return api;
}));
