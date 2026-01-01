e-bazar v1.0.0 - Release Notes (2025-12-22)

Summary
-------
This first release implements the essential scaffolding and core features required for the mini-project baseline:

Implemented
- Router: support for both `REQUEST_URI` pretty URLs and `?url=` query parameter (compatibility with frontend fetch)
- Models: `Ad` and `User` models with prepared statements
- Authentication: register/login/logout with password hashing and CSRF protection
- Admin: default admin user created by `scripts/init_db.php`
- Ads: extended schema (owner_id, description, delivery_modes, sold, created_at), `create` and `store` pages for posting ads (basic validation)
- Views & navigation: header links to auth, ad creation, dashboard; simple user dashboard and admin dashboard
- Utilities: `scripts/init_db.php` for safe migrations and seeding

Known limitations and next steps
- Image uploads not yet implemented (max 5 JPEGs, <=200 KiB) — planned next
- Categories and paginated listing still missing
- Purchase flow (buy/confirm receipt) not implemented
- Improve tests, security hardening (rate limiting, stricter validation), and packaging/deployment automation

Testing & installation
- php scripts/init_db.php
- php -S 0.0.0.0:8000 -t .
- Default admin: admin@ebazar.local / admin

Suggested git commit message for this release
"chore(release): v1.0.0 - initial functional baseline (auth, ads scaffold, admin, migrations)"

If you want, I can (a) install git and create a tag locally, or (b) prepare a git-format patch for you to apply in a git repo.
