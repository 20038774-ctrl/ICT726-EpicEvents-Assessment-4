# Week 12 demonstration guide (8–10 minutes)

## Recommended running order

1. **Problem and architecture (1 minute):** introduce NSW event discovery and show the four-table relationship diagram from `database/schema.sql`.
2. **Public experience (1.5 minutes):** home, database-driven catalogue, combined search/category filter and an event detail page.
3. **Member flow (2 minutes):** show a failed form validation, register/log in, book tickets and cancel from the dashboard.
4. **Administrator flow (2 minutes):** demonstrate role denial as a member, then create/edit/archive an event and update a booking.
5. **Quality (1.5 minutes):** keyboard skip link/focus, mobile layout, privacy notice, unique metadata, sitemap and Event JSON-LD.
6. **Team evidence and reflection (1 minute):** each member connects their Git commits to one feature and explains one test.

## Likely Q&A prompts

- Why is JavaScript validation insufficient on its own?
- How do prepared statements reduce SQL injection risk?
- Why regenerate the session identifier after login?
- How does the application prevent a member changing another member’s booking?
- What relationships and deletion rules exist in the schema?
- How is overselling reduced when two users book simultaneously?
- Which accessibility and SEO techniques were deliberately implemented?
- What would need to change before accepting real payments or public users?

Do not memorise scripts without understanding the code. Open the relevant source file when answering a technical question.
