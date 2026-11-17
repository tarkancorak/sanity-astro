# Astro Website Template

A **skeleton** starter for client marketing sites — extracted from the `hs-geldern`
and `corak-org` projects. It provides the plumbing (structure, tooling, CI/CD, form
wiring, legal boilerplate) but **intentionally ships no finished design**, so every
client site gets its own look instead of a shared template appearance.

## What's included (the skeleton)

- **Astro 7** + **Tailwind 4** (via `@tailwindcss/vite`), TypeScript strict mode
- **astro-icon** with the Lucide icon set
- Self-hostable variable fonts pattern (GDPR-friendly, no Google Fonts CDN) — documented in `global.css`
- **Central config** in `src/config/site.ts` — brand, contact, nav and the contact-form endpoint in one place
- **Layouts**: `Layout` (site shell + SEO head + cookie-consent placeholder) and `LegalLayout`
- **Pages**: `index` (blank homepage skeleton), `impressum`, `datenschutz` (German legal boilerplate with `[TODO]` markers)
- **Contact form** wired to **Formspree** — one form endpoint per site, no server needed (see below)
- **Sitemap** via `@astrojs/sitemap`, referenced from `public/robots.txt` — both need the real domain
  (see step 4 below); until `site` is set the build warns and emits no sitemap
- **CI/CD**: `ci.yml` (build check on PRs) and `astro.yml` (SCP deploy to IONOS webspace, opt-in)
- **Dependabot**: grouped weekly npm + github-actions updates, majors excluded
- **`design/`** — convention for versioning design deliveries (handoffs, screens, adoption log) alongside
  the code; empty apart from its `README.md`, see there

## What's intentionally NOT included

No opinionated visual design. Colors, fonts, spacing and section layouts are neutral
placeholders. The homepage is a blank canvas. **Design each client site individually**
by editing `src/styles/global.css` (design tokens) and building out the components/sections.

## Quick start

```bash
npm install          # generates package-lock.json
npm run dev          # http://localhost:4321
npm run build        # production build -> dist/
npm run check        # astro type/diagnostics check
```

Node **>= 22.12** (see `.nvmrc`).

## Setting up a new client site

1. Edit **`src/config/site.ts`** — company name, phone, email, service area, nav, `formEndpoint`.
2. Fill in the `[TODO]` fields in **`src/pages/impressum.astro`** and **`datenschutz.astro`** (and have them reviewed legally).
3. Do the **design pass**: set the palette/fonts/tokens in `src/styles/global.css`, replace the placeholder `Logo`/favicon, and build out the homepage sections in `src/pages/index.astro`. Design deliveries go to `design/` — see `design/README.md` for the layout and who moves what when.
4. Set `site:` in `astro.config.mjs` to the production URL, and put the **same domain** into the
   `Sitemap:` line of `public/robots.txt`. Both are needed: `site` makes Astro emit the sitemap at
   all, the robots.txt line is how a crawler finds it without anyone submitting it in Search Console.

## Contact form (Formspree)

Backend is **Formspree**. The form posts to the URL in `siteConfig.formEndpoint`:

1. Create a Formspree account at <https://formspree.io> and a new form (destination = the
   client's inbox).
2. Copy the form endpoint (e.g. `https://formspree.io/f/abcdwxyz`).
3. Paste it into `formEndpoint` in `src/config/site.ts`.

No API key or secret is needed — the endpoint id is public and lives in the client HTML.
Formspree emails you each submission. `ContactForm.astro` POSTs `FormData` with
`Accept: application/json` and shows a thank-you state on success; if JS fails it falls back
to a native form POST. Any other POST backend (Getform, Web3Forms, a custom handler) works
too by changing `formEndpoint`.

## Deployment (SCP to IONOS)

`astro.yml` builds on every push to `master` and can deploy `dist/` via SCP. The deploy
step is **off by default** (build stays green). To enable, in the GitHub repo under
**Settings → Secrets and variables → Actions**:

- **Variable** `DEPLOY_ENABLED = true`
- Optional **Variable** `DEPLOY_PATH = /subfolder` (empty = webspace root)
- **Secrets** `SSH_USERNAME`, `SSH_HOST`, `SSH_PASSWORD`

## Keeping dependencies current

Dependabot opens **grouped weekly PRs** (it does not edit `package.json` on `master`
directly — updates land only when you merge those PRs). Majors are excluded on purpose
and done by hand. To bump proactively:

```bash
npx npm-check-updates -u   # raises ranges in package.json (review majors!)
npm install
```

## Using this as a GitHub template repository

Push this folder to a new GitHub repo, then enable **Settings → Template repository**.
New client sites can then be created with **Use this template**.
