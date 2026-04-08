/// <reference path="./types.d.ts" />

"use strict";

(function () {
  redirectFrontPage();

  /**
   * On the front page, detect the browser language and redirect accordingly
   */
  function redirectFrontPage() {
    const settings = window.ACFMultilingual;
    if (!settings || !settings.isFrontPage) return;

    const { defaultLanguage, currentLanguage, languages } = settings;

    /** @type Record<string, string> */
    const homePaths = {};
    Object.values(languages).forEach(({ slug }) => {
      homePaths[slug] = slug === defaultLanguage ? "/" : `/${slug}/`;
    });

    const browserLanguage = navigator.language.slice(0, 2).toLowerCase();
    const chosenLanguage = !!languages[browserLanguage]
      ? browserLanguage
      : defaultLanguage;

    const storedLanguage = localStorage.getItem("acfml-language");

    // No language stored, or the language doesn't exist anymore
    if (!storedLanguage || !homePaths[storedLanguage]) {
      replacePathname(homePaths[chosenLanguage]);
    }

    // Always store the language
    localStorage.setItem("acfml-language", currentLanguage);
  }

  /**
   * Replace only the pathname in the current URL. Causes a redirect
   *
   * @param {string} pathname
   */
  function replacePathname(pathname) {
    // prevent redirect loops
    if (location.pathname === pathname) {
      return;
    }

    const url = new URL(location.href);
    url.pathname = pathname;
    location.replace(url.toString());
  }
})();
