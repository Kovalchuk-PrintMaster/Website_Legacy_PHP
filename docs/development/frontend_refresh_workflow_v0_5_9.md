
ForPrint Website — Frontend Refresh Workflow v0.5.9
Status

frontend_refresh_workflow_v0_5_9_recorded

Purpose

Define how the legacy PHP website frontend will be modernized quickly without turning the task into a deep rewrite.

Current strategy

The site is a temporary public website for ForPrint.

It should be good enough for launch, but it is not the final ForPrint platform UI.

Working rules
1. Work by visible blocks

Preferred order:

header/navigation
homepage hero / first screen
homepage product/category blocks
product cards
product page
cart
footer/contact/CTA
mobile responsive pass
public-launch hardening
2. Screenshots are the main input

For frontend work, the fastest input format is:

URL:
screen size:
what looks wrong:
what result is wanted:
3. Aggressive frontend replacement is allowed

Allowed to replace broadly:

CSS
frontend JS
HTML structure inside templates
visual blocks
old animations
sliders
card layout
responsive behavior
decorative markup
4. Backend is changed only when needed

Be careful with:

routing
cart/order/login
admin
SQL queries
mail
upload
DB write behavior

Backend changes should be targeted and tested.

5. Avoid endless tiny edits

If a frontend file is clearly obsolete, prefer a clean replacement of the block/file while preserving required PHP variables, routes and data loops.

6. Commit after each working block

Each visual block should end with:

make site-smoke
make check
git diff --check
git commit
git push
7. Keep ForPrint standards gradual

This repository is legacy PHP, so it will not immediately match the full target ForPrint module tree.

But it should gradually adopt:

Makefile operator targets
coordination reports
status records
docs/development
docs/architecture
scripts/inspection
safe config/secrets rules
Next practical frontend checkpoint

v0.6.0 — Header and First Screen Refresh
