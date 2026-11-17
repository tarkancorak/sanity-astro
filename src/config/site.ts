/**
 * Central site configuration.
 *
 * This is the single place to change brand, contact details and the contact-form
 * endpoint. Header, Footer, ContactForm and the legal pages all read from here,
 * so a new project only needs to edit THIS file (plus the legal page content).
 */

export const siteConfig = {
  // Brand / company
  company: {
    name: "Brand", // TODO: company / brand name
    // Wordmark split for the Logo component: <main><accent>
    wordmarkMain: "BR",
    wordmarkAccent: "AND",
    tagline: "Kurzer Untertitel / Claim für das Unternehmen.", // TODO
    get year() {
      return new Date().getFullYear();
    },
  },

  // Phone contact
  phone: {
    display: "0000 0000000", // TODO: human-readable phone number
    link: "00000000000", // TODO: tel: friendly (digits only, no spaces)
  },

  // Email contact
  email: "info@example.com", // TODO

  // Service area / location shown in header/footer/hero
  serviceArea: "Musterstadt & Umgebung", // TODO

  /**
   * Contact-form backend — self-hosted PHP handler (see public/kontakt.php).
   *
   * Standard for corak client sites: the form is processed on the site's own
   * webspace (IONOS) and emailed to the client's inbox. No external form
   * service, no third-country transfer, no extra data processor — the AVV is
   * covered by the hosting contract.
   *
   *   1. In public/kontakt.php set MAIL_TO / MAIL_FROM / SMTP_USER (the client's
   *      OWN mailbox) and $ALLOWED_ORIGINS (the client's domain).
   *   2. Add the mailbox password as the GitHub secret SMTP_PASSWORD in the
   *      client's repo — the deploy writes it into smtp-secret.php (never
   *      committed). Sending uses authenticated SMTP (mail() is unreliable on
   *      IONOS), so a real mailbox + password is required.
   *   3. Ensure the host runs PHP (classic IONOS Webhosting/Apache — not a
   *      static-only host; Vercel/Netlify do NOT run PHP).
   *
   * ContactForm posts FormData with `Accept: application/json`; kontakt.php
   * returns 200 on success (JS shows the in-page thank-you) or 4xx/5xx on error.
   */
  formEndpoint: "/kontakt.php",

  /**
   * Optional dropdown ("topic") in the contact form — each project decides.
   * Leave `options` empty to hide it, or fill it and set a `label`. The field
   * is submitted as `topic`; kontakt.php mails it under its TOPIC_LABEL.
   */
  topicField: {
    label: "Thema", // TODO: e.g. "Art der Leistung", "Ihr Anliegen"
    options: [] as string[], // TODO: e.g. ["Beratung", "Angebot"]; empty = no dropdown
  },

  /**
   * Second optional dropdown ("source"): how did the visitor find us. Kept
   * deliberately separate from `topicField` — the two answer different
   * questions, and mixing a marketing channel into a list of services puts it
   * on the same footing as the services themselves.
   *
   * Worth shipping on every site, because for a small business this is usually
   * the only attribution that exists: word of mouth, print and a flyer on a
   * noticeboard show up in no analytics tool, and no cookie banner is involved
   * — the visitor simply tells us.
   *
   * The field stays optional. Whoever fills it in is doing us a favour; making
   * it mandatory would cost enquiries, and a forced answer would be worthless
   * anyway.
   *
   * Per project: list only channels the client actually has. An option without
   * a presence behind it can only be clicked by mistake and dilutes the
   * result. Add the project's own peculiarities at the end, before
   * "Sonstiges" — a campaign landing page, an event, a game.
   */
  sourceField: {
    label: "Wie sind Sie auf uns aufmerksam geworden?",
    options: [
      "Empfehlung",
      "Google-Suche",
      "Instagram",
      "Facebook",
      "TikTok",
      "Zeitung oder Aushang",
      "Sonstiges",
    ] as string[], // empty = no dropdown
  },

  // Primary navigation (anchors into the homepage sections)
  nav: [
    { href: "#leistungen", label: "Leistungen" },
    { href: "#kontakt", label: "Kontakt" },
  ],
};

export type SiteConfig = typeof siteConfig;
