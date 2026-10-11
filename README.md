<div align="center">

<a href="https://www.datafirefly.com/en/product/header-footer-code-injector/"><img src="logo.png" alt="Header/Footer Code Injector for PrestaShop - DataFirefly" width="96" height="96"></a>

# Header/Footer Code Injector for PrestaShop 8 & 9

**Free PrestaShop module to inject custom CSS, JavaScript and HTML into the `<head>`, right after `<body>` or right before `</body>` — without editing your theme.**

[![PrestaShop 8 & 9](https://img.shields.io/badge/PrestaShop-8.0%20%E2%86%92%209.x-DF0067?logo=prestashop&logoColor=white)](https://www.datafirefly.com/en/product/header-footer-code-injector/)
[![PHP 7.2+ / 8.x](https://img.shields.io/badge/PHP-7.2%2B%20%7C%208.x-777BB4?logo=php&logoColor=white)](#requirements)
[![Version 1.1.0](https://img.shields.io/badge/version-1.1.0-2ea44f)](CHANGELOG.md)
[![Price: Free](https://img.shields.io/badge/price-free-brightgreen)](https://www.datafirefly.com/en/product/header-footer-code-injector/)
[![Multistore](https://img.shields.io/badge/multistore-yes-0a7bbb)](#features)

[**Product page**](https://www.datafirefly.com/en/product/header-footer-code-injector/) ·
[**Documentation**](https://www.datafirefly.com/en/documentation/dfcodeinjector/) ·
[**Download the latest release**](https://github.com/datafirefly-ltd/dfcodeinjector/releases/latest) ·
[**More PrestaShop modules by DataFirefly**](https://www.datafirefly.com/en/)

</div>

---

<a href="https://www.datafirefly.com/en/product/header-footer-code-injector/"><img src="https://www.datafirefly.com/wp-content/uploads/2026/06/injecteur-de-code-header-footer-css-js-df-codeinjector-fr-23b023-1.webp" alt="Injecting CSS and JS code into the PrestaShop header and footer with the DataFirefly Header/Footer Code Injector module" width="100%"></a>

## Why this module?

Adding a **Google Tag Manager** container, a **Google Analytics 4** tag, a **Meta (Facebook) Pixel**, a **Google Search Console** verification meta tag or a few lines of custom CSS to a PrestaShop store usually means editing `head.tpl` or `layout-both-columns.tpl` — changes that are lost at the next theme update.

**Header/Footer Code Injector** (`dfcodeinjector`) by [DataFirefly](https://www.datafirefly.com/en/) gives you a clean back-office screen to manage all those snippets instead. The code is injected through PrestaShop hooks, exactly as you pasted it, and survives theme and core updates.

## Features

- **Three injection points**: inside the `<head>` tag, right after the opening `<body>` tag, right before the closing `</body>` tag.
- **Works with every theme**: when a theme does not call `displayAfterBodyOpeningTag` / `displayBeforeBodyClosingTag`, the code is spliced directly into the page HTML (automatic fallback, new in 1.1.0).
- **CSS, JavaScript and HTML**: `<style>`, `<script>`, `<link>`, `<meta>`, `<noscript>`… injected untouched — no HTMLPurifier, no escaping.
- **Unlimited snippets**, each with its own on/off switch and **injection order**.
- **Page targeting**: all pages, *only* selected pages, or all pages *except* selected ones (home, category, product, CMS, cart, checkout, order confirmation, my account, 404… plus any module page such as `module-modulename-controller`).
- **Test mode**: inject a snippet only for the maintenance IP addresses (CIDR ranges supported) to check it on the live store before going public.
- **Duplicate** a snippet in one click (the copy is created disabled, for review).
- **Multistore**: a snippet can target all stores or a single store.
- **Fast**: one SQL query per page view, whatever the number of snippets and locations.
- **Back office in 8 languages**: English, French, Spanish, German, Italian, Dutch, Polish, Portuguese.
- **Clean uninstall**: the module removes its table and admin tab.

## Typical use cases

| Need | Location |
| --- | --- |
| Google Tag Manager (script part) | Header (`<head>`) |
| Google Tag Manager (`<noscript>` iframe) | Body start |
| Google Analytics 4 / gtag.js | Header |
| Meta Pixel, TikTok Pixel, Pinterest Tag, LinkedIn Insight | Header or body end |
| Google Search Console / Bing / Pinterest site verification `<meta>` | Header |
| Conversion tracking on the thank-you page | Body end, targeted to `order-confirmation` |
| Custom CSS fixes or a promo banner style | Header |
| Live chat, review or consent widgets | Body end |
| JSON-LD structured data | Header or body end |

## Requirements

- PrestaShop **8.0 to 9.x** (multistore supported)
- PHP version supported by your PrestaShop release (PHP 7.2+ for PrestaShop 8, PHP 8.1+ for PrestaShop 9)

## Installation

**From the back office (recommended)**

1. Download `dfcodeinjector-x.y.z.zip` from the [latest release](https://github.com/datafirefly-ltd/dfcodeinjector/releases/latest) or from the [DataFirefly product page](https://www.datafirefly.com/en/product/header-footer-code-injector/).
2. Go to **Modules > Module Manager > Upload a module** and drop the zip.
3. Click **Configure** to open the snippet manager.

**With Git**

```bash
cd /path/to/prestashop/modules
git clone https://github.com/datafirefly-ltd/dfcodeinjector.git
```

Then install **Header/Footer Code Injector** from **Modules > Module Manager**.

## Usage

1. **Configure** the module, then **Add a snippet**.
2. Give it a name (e.g. *Google Tag Manager*), choose the **location** and paste the complete code, tags included.
3. Optionally restrict it to some pages, set its order, or enable **test mode**.
4. Save. The snippet is live on the next page view.

Full guide: [Header/Footer Code Injector documentation](https://www.datafirefly.com/en/documentation/dfcodeinjector/).

## How it works

| Location | Hook | Fallback |
| --- | --- | --- |
| Header | `displayHeader` | — |
| Body start | `displayAfterBodyOpeningTag` | inserted after `<body …>` via `actionOutputHTMLBefore` |
| Body end | `displayBeforeBodyClosingTag` | inserted before `</body>` via `actionOutputHTMLBefore` |

Snippets are stored in the `ps_df_code_snippet` table. Page names match the theme's `{$page.page_name}` value (`FrontController::getPageName()`).

## FAQ

**Does the module modify my theme files?**
No. Code is injected through PrestaShop hooks; no theme file is touched and your snippets survive theme updates.

**Can I add Google Tag Manager or a tracking pixel?**
Yes. Paste the code supplied by the service into a snippet, choose the location (usually the header and the body start for GTM) and enable it.

**Do the body locations work with custom themes?**
Yes. Since 1.1.0, if the theme does not call the body hooks, the module inserts the code directly into the page HTML.

**Can I test a snippet before showing it to customers?**
Yes. Turn on **Test mode**: the snippet is only injected for the IP addresses listed in *Shop Parameters > General > Maintenance*.

**Is it really free?**
Yes, this module is offered free of charge by [DataFirefly](https://www.datafirefly.com/en/).

## Available in your language

| Language | Product page | Documentation |
| --- | --- | --- |
| 🇬🇧 English | [Header/Footer Code Injector](https://www.datafirefly.com/en/product/header-footer-code-injector/) | [Docs](https://www.datafirefly.com/en/documentation/dfcodeinjector/) |
| 🇫🇷 Français | [Injecteur de code Header/Footer pour PrestaShop](https://www.datafirefly.com/product/injecteur-code-header-footer/) | [Documentation](https://www.datafirefly.com/documentation/dfcodeinjector/) |
| 🇪🇸 Español | [Inyector de código Header/Footer para PrestaShop](https://www.datafirefly.com/es/producto/inyector-codigo-header-footer/) | [Documentación](https://www.datafirefly.com/es/documentation/dfcodeinjector/) |
| 🇩🇪 Deutsch | [Header/Footer Code-Injektor für PrestaShop](https://www.datafirefly.com/de/produkt/header-footer-code-injektor/) | [Dokumentation](https://www.datafirefly.com/de/documentation/dfcodeinjector/) |
| 🇮🇹 Italiano | [Iniettore di codice Header/Footer per PrestaShop](https://www.datafirefly.com/it/product/iniettore-codice-header-footer/) | [Documentazione](https://www.datafirefly.com/it/documentation/dfcodeinjector/) |
| 🇳🇱 Nederlands | [Code-injector Header/Footer voor PrestaShop](https://www.datafirefly.com/nl/product/injecteur-code-header-footer/) | [Documentatie](https://www.datafirefly.com/nl/documentation/dfcodeinjector/) |
| 🇵🇱 Polski | [Wstrzykiwanie kodu w nagłówku i stopce PrestaShop](https://www.datafirefly.com/pl/produkt/wstrzykiwanie-kodu-naglowek-stopka-prestashop/) | [Dokumentacja](https://www.datafirefly.com/pl/documentation/dfcodeinjector/) |
| 🇵🇹 Português | [Injetor de código no cabeçalho e rodapé PrestaShop](https://www.datafirefly.com/pt/produto/injetor-codigo-cabecalho-rodape/) | [Documentação](https://www.datafirefly.com/pt/documentation/dfcodeinjector/) |

> 🇫🇷 **En bref** — Module PrestaShop 8 et 9 gratuit pour ajouter du code CSS, JavaScript ou HTML dans le `<head>`, après `<body>` ou avant `</body>` sans modifier le thème : Google Tag Manager, Google Analytics, Pixel Meta, balises de vérification, CSS personnalisé. Ciblage par page, ordre d'injection, mode test et multiboutique. [Voir la fiche produit](https://www.datafirefly.com/product/injecteur-code-header-footer/).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Support

- Documentation: <https://www.datafirefly.com/en/documentation/dfcodeinjector/>
- Bug reports and feature requests: [GitHub issues](https://github.com/datafirefly-ltd/dfcodeinjector/issues)
- Other modules for PrestaShop, WooCommerce and Shopware: <https://www.datafirefly.com/en/>

## License

Free module, © [DataFirefly Limited](https://www.datafirefly.com/en/). All rights reserved.

---

<div align="center">

Made by <a href="https://www.datafirefly.com/en/"><b>DataFirefly</b></a> — PrestaShop, WooCommerce and Shopware modules.

</div>
