// @ts-check
import { defineConfig } from "astro/config";
import sitemap from "@astrojs/sitemap";
import tailwind from "@tailwindcss/vite";
import icon from "astro-icon";

// https://astro.build/config
export default defineConfig({
  // TODO: set the production URL of this site (used for canonical URLs, sitemap, etc.)
  // site: "https://example.com",

  // Sitemap. Depends on `site` above: without an absolute base URL Astro cannot
  // build absolute <loc> entries, so the integration emits nothing and warns at
  // build time. That warning is the reminder to fill in `site` — it disappears
  // the moment the line above is uncommented, and the sitemap appears with it.
  //
  // Remember the second half: `public/robots.txt` has to name the sitemap, with
  // the same domain. That reference is how a crawler finds it without anyone
  // submitting it in Search Console.
  integrations: [icon(), sitemap()],

  // Tailwind via the official Vite plugin (@tailwindcss/vite), Tailwind 4
  vite: {
    plugins: [tailwind()],
  },
});
