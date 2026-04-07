/// <reference path="./types.d.ts" />

"use strict";

/**
 * @typedef {import('jquery')} jQuery
 */

(($) => {
  /**
   * A simple Cookie helper class
   */
  class Cookie {
    constructor() {}

    static getAll() {
      /** @type {Record<string, string>} */
      const cookies = {};
      if (!document.cookie) return cookies;
      const rows = document.cookie.split("; ");
      for (const row of rows) {
        const [key, value] = row.split("=");
        cookies[key] = value;
      }
      return cookies;
    }

    /**
     * @param {string} name
     * @param {string | null} fallback
     * @returns {string | null}
     */
    static get(name, fallback = null) {
      var v = document.cookie.match("(^|;) ?" + name + "=([^;]*)(;|$)");
      return v ? v[2] : fallback;
    }

    /**
     * @param {string} name
     * @param {string} value
     * @param {number} minutes
     */
    static set(name, value, minutes = 1) {
      var d = new Date();
      d.setTime(d.getTime() + minutes * 1000 * 60);
      document.cookie =
        name + "=" + value + ";path=/;expires=" + d.toUTCString();
    }

    /**
     * @param {string} name
     */
    static delete(name) {
      Cookie.set(name, "", 0);
    }
  }

  /**
   * The main ACFML admin script
   */
  class ACFML {
    constructor() {
      this.injectColorThemeVars();
      this.initMultilingualWysiwyg();
      this.initMultilingualPostTitle();
      this.initMultilingualTermName();
      this.initLanguageTabs();
      this.initValidationHandling();
    }

    /**
     * Inject custom color theme css variables
     */
    injectColorThemeVars() {
      const button = document.createElement("a");
      button.classList.add("button", "button-primary");
      document.body.prepend(button);
      const buttonStyle = window.getComputedStyle(button);
      this.setCssVar("--acfml-button-primary-color", buttonStyle.color);
      this.setCssVar(
        "--acfml-button-primary-background",
        buttonStyle.backgroundColor,
      );
      button.remove();
      const bodyStyle = window.getComputedStyle(document.body);
      this.setCssVar("--acfml-body-background", bodyStyle.backgroundColor);
    }

    /**
     * Set a css var on the documentElement
     *
     * @param {string} name
     * @param {string} value
     */
    setCssVar(name, value) {
      document.documentElement.style.setProperty(name, value);
    }

    /**
     * Setup language switchers for multilingual acf-fields
     */
    initLanguageTabs() {
      acf.addAction("acfml/switch_language", ($field, language) => {
        // don't do anything if listening to anotherfield
        if ($field.data("acfml-ui-listen-to")) return;
        // switch possibly listening other fields
        this.switchLanguage(
          $(`[data-acfml-ui-listen-to="${$field.data("name")}"]`),
          language,
        );
      });
      $(document).on("click", ".acfml-tab", (e) => {
        e.preventDefault();
        const $el = $(e.target);
        const language = $el.attr("data-language");

        this.switchLanguage(
          $el.parents(".acfml-ui-style--tabs:first"),
          language,
        );
      });
      $(document).on("dblclick", ".acfml-tab", (e) => {
        e.preventDefault();
        const $el = $(e.target);
        const language = $el.attr("data-language");
        this.switchLanguage(
          $(".acfml-ui-style--tabs:not([data-acfml-ui-listen-to])"),
          language,
        );
      });
      // store active language tabs before submitting a form or reloading the page
      window.addEventListener("beforeunload", () =>
        this.storeActiveLanguageTabs(),
      );
      this.deleteLanguageTabsCookies();
    }

    /**
     * Switche the language for an .acfml-multilingual-field
     * @param {JQuery} $fields
     * @param {string|undefined} language
     */
    switchLanguage($fields, language) {
      if (!language) return;

      $fields.each((i, el) => {
        const $el = $(el);
        const $childFields = $el.find(".acf-input:first").find(".acfml-field");
        const $tabs = $el.find(".acfml-tab");

        $tabs.removeClass("is-active");
        $tabs.filter(`[data-language=${language}]`).addClass("is-active");
        $childFields.removeClass("acfml-is-visible");

        // find the active field
        const $activeField = $childFields.filter(
          `[data-name=${language}]:first`,
        );
        $activeField.addClass("acfml-is-visible");

        // initializes delayed WYSIWYG fields
        $activeField.find(".acf-editor-wrap.delay").trigger("mousedown");

        $el.attr("data-acfml-language", language);
        acf.doAction("acfml/switch_language", $el, language);
      });
    }

    /**
     * Prepare multilingual WYSIWYG fields
     */
    initMultilingualWysiwyg() {
      acf.addFilter("wysiwyg_tinymce_settings", (init, id, field) => {
        const $parent = field.$el.parents(".acfml-multilingual-field");
        if (!$parent.length) return init;
        const fieldNameClass = $parent.attr("data-name").split("_").join("-");
        init.body_class += ` acf-wysiwyg--${fieldNameClass}`;
        // https://www.tiny.cloud/docs-3x/reference/Configuration3x/Configuration3x@directionality/
        const textDirection = field.get("acfmlTextDirection");
        init.directionality = textDirection;
        return init;
      });
    }

    /**
     * Multilingual Post Titles
     */
    initMultilingualPostTitle() {
      acf.addAction(`ready_field/name=acfml_post_title`, (field) => {
        $("#titlediv").remove();
        $('[data-setting="title"]').remove();
      });
      acf.addAction(
        `ready_field/key=field_acfml_post_title_${ACFMultilingual.defaultLanguage}`,
        (field) => {
          if (!ACFMultilingual.isMobile && !field.val()) field.$input().focus();
        },
      );
      acf.addAction(`ready_field/key=field_acfml_slug`, ($field) => {
        // $('.postbox#slugdiv').remove();
      });
    }

    /**
     * Multilingual Term Names
     */
    initMultilingualTermName() {
      acf.addAction("ready_field/key=field_acfml_term_name", ($field) => {
        $(".form-field.term-name-wrap").remove();
      });
    }

    /**
     * Store active language tabs for acf fields
     */
    storeActiveLanguageTabs() {
      /** @type {Record<string, string | undefined>} */
      let acfml_language_tabs = {};
      $(".acfml-multilingual-field.acfml-ui-style--tabs").each((i, el) => {
        const key = $(el).attr("data-key");
        const language = $(el)
          .find(".acfml-field.acfml-is-visible")
          .attr("data-name");
        if (key) {
          acfml_language_tabs[key] = language;
        }
      });
      this.addToStore("acfml_language_tabs", acfml_language_tabs);
    }

    /**
     * Switch to the default language for required fields on validation error
     */
    initValidationHandling() {
      acf.addAction("invalid_field", (field) => {
        if (
          field.data.required &&
          field.$el.hasClass("acfml-field") &&
          !field.val()
        ) {
          this.switchLanguage(
            field.$el.parents(".acfml-multilingual-field.acfml-ui-style--tabs"),
            field.data.name,
          );
        }
      });
    }

    /**
     * Delete all language tabs Cookies
     */
    deleteLanguageTabsCookies() {
      const storedLanguageTabs = Object.keys(Cookie.getAll()).filter((key) =>
        key.startsWith("acfml_language_tabs_"),
      );
      for (const key of storedLanguageTabs) {
        Cookie.delete(key);
      }
    }

    /**
     * Store something. We are using good old Cookies, since we need
     * the information in PHP as well
     *
     * @param {string} key
     * @param {any} value
     */
    addToStore(key, value) {
      Cookie.set(this.getStorageKey(key), JSON.stringify(value), 1);
      // sessionStorage.setItem(this.getStorageKey(key), JSON.stringify(value));
    }

    /**
     * Remove something from the store
     * @param {string} key
     */
    removeFromStore(key) {
      Cookie.delete(this.getStorageKey(key));
      // sessionStorage.removeItem(this.getStorageKey(key));
    }

    /**
     * Get something from the store
     * @param {string} key
     */
    getFromStore(key) {
      // let value = sessionStorage.getItem(this.getStorageKey(key));
      let value = Cookie.get(this.getStorageKey(key));
      return value ? JSON.parse(value) : value;
    }

    /**
     * Get the storage key
     * @param {string} key
     * @returns {string}
     */
    getStorageKey(key) {
      return `${key}_${ACFMultilingual.cookieHashForCurrentUri}`;
    }
  }

  new ACFML();
})(/** @type {jQuery} */ jQuery);
