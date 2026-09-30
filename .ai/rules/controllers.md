---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Update the in-app tutorial with every feature change
The ERP has a Tutorial menu (Indonesian) that explains how to use each module. Whenever you add, change or remove a feature, menu, form field, button, workflow, permission or route, update the matching topic in resources/views/erp/tutorial/topics/{slug}.blade.php and its entry in App\Services\Tutorial (title, summary, permission, `covers` route patterns) in the same change. A new module or page needs a topic (or a section in one). tests/Feature/TutorialTest.php fails when a page has no topic or a topic covers a route that no longer exists, but it cannot see changes inside an existing page, so review the topic by hand.
