# Install local-first SEO launch documentation v0.1

Run from the repository root:

```bash
unzip -n forprint_local_first_seo_launch_documentation_v0_1_bundle.zip -d . && python3 -m py_compile scripts/inspection/check_website_local_first_seo_launch_docs.py && python3 scripts/inspection/check_website_local_first_seo_launch_docs.py && git diff --check -- docs/seo docs/decisions docs/documentation docs/status/snapshots seo/config scripts/inspection/check_website_local_first_seo_launch_docs.py
```

The package installs documentation only. It does not mutate the website,
hosting, DNS, database, analytics, Ads or Git.
