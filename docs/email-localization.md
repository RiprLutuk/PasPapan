# Email Localization Phase

## What Changed

- Standardized email and notification copy to use translation keys instead of mixed hardcoded Indonesian and English strings.
- Updated email templates and notification classes to format dates and labels through locale-aware helpers.
- Added missing translation entries in `lang/id.json` and `lang/en.json` for email subjects, greetings, CTA labels, helper text, and notification messages.

## Why

- Several email flows were partially bilingual or mixed both languages in the same message.
- Subjects, detail labels, and database notifications were not consistently using the active locale.
- This phase makes the email and notification experience more predictable for both Indonesian and English users.

## Risks

- Existing translation keys may still be incomplete in unrelated modules outside the mail/notification flows updated here.
- Any future notification copy added directly in PHP or Blade without translation helpers can reintroduce mixed-language output.

## Future Improvements

- Add focused tests for locale-specific mail rendering.
- Introduce reusable helpers for localized date, amount, and status formatting across all notification classes.
