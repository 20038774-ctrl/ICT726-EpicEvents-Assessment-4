# EpicEvents testing record

Complete this table on the final machine and add screenshot filenames or notes. Do not mark an item Pass until it has been run.

| ID | Test | Expected result | Browser/viewport | Result | Evidence |
|---|---|---|---|---|---|
| F01 | Import `database/schema.sql` | Four tables and seed records created without error | MySQL 8 | Not run | |
| F02 | Browse and search events | Results come from database and filters combine correctly | Chrome desktop | Not run | |
| F03 | Submit invalid registration | Specific errors; values retained; no row inserted | Chrome desktop | Not run | |
| F04 | Register valid member | Hashed password row; authenticated dashboard opens | Chrome desktop | Not run | |
| F05 | Log in/out | Correct account works; wrong password rejected; logout requires POST | Firefox desktop | Not run | |
| F06 | Book 1–10 tickets | Unique reference appears and availability decreases | Chrome desktop | Not run | |
| F07 | Exceed remaining capacity | Server rejects request without creating booking | Chrome desktop | Not run | |
| F08 | Cancel own booking | Status changes; capacity becomes available | Chrome desktop | Not run | |
| A01 | Member opens `/admin/` | HTTP 403 access-denied page | Chrome desktop | Not run | |
| A02 | Admin creates/edits/archives event | Catalogue reflects valid published changes | Chrome desktop | Not run | |
| A03 | Admin changes workflow statuses | Booking/enquiry status persists | Chrome desktop | Not run | |
| A04 | Delete draft with no bookings; then attempt published/booked event | Eligible draft is deleted; protected records remain with a clear message | Chrome desktop | Not run | |
| S01 | Send forged/missing CSRF token | Request rejected with 419 | DevTools | Not run | |
| S02 | Search using SQL/XSS payload | No SQL change and payload is not executed | Chrome desktop | Not run | |
| S03 | Member changes another booking ID | No other user record changes | DevTools | Not run | |
| S04 | Submit capacity/price/quantity outside database checks | MySQL rejects invalid values | MySQL 8 | Not run | |
| U01 | Keyboard-only navigation | Skip link, menu, forms and actions usable with visible focus | Chrome desktop | Not run | |
| U02 | Zoom to 200% | Content reflows without loss or overlap | Firefox desktop | Not run | |
| U03 | Mobile layout | No unintended horizontal page scroll; menu/buttons usable | 390×844 | Not run | |
| U04 | Cross-browser | Core flows behave consistently | Safari/Firefox/Chrome | Not run | |
| Q01 | W3C HTML validator | No critical semantic errors | Public pages | Not run | |
| Q02 | Lighthouse | Accessibility/SEO issues reviewed and resolved | Chrome | Not run | |
| Q03 | Rich Results Test | Event JSON-LD has no critical errors | Hosted event URL | Not run | |
| Q04 | Inspect private/admin page headers and source | `Cache-Control: no-store` where authenticated and `noindex, nofollow` in private templates | DevTools | Not run | |
