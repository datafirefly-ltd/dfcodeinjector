# Changelog

All notable changes to [Header/Footer Code Injector for PrestaShop](https://www.datafirefly.com/en/product/header-footer-code-injector/) are documented here.

## [1.1.0]

### Added
- Targeting modes: all pages, only the selected pages, all pages except the selected ones.
- Free page names for module pages (`module-modulename-controller`).
- Test mode: snippets injected only for the maintenance IP addresses (CIDR ranges supported).
- One-click duplication of a snippet (the copy is created disabled).
- Automatic fallback through `actionOutputHTMLBefore` for themes that do not call the body hooks.
- Back-office translations: Dutch, Polish, Portuguese.

### Changed
- All snippets of a page are loaded with a single SQL query.
- Code editor: monospace font, Tab inserts 4 spaces.

## [1.0.0]

- First release: head, body start and body end locations, unlimited snippets, page targeting, ordering, multistore.

[1.1.0]: https://github.com/datafirefly-ltd/dfcodeinjector/releases/tag/v1.1.0
[1.0.0]: https://www.datafirefly.com/en/product/header-footer-code-injector/
