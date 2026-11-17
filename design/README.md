# Design im Repository

Designartefakte liegen bewusst im Website-Repo. Ein Projekt dieser Größe ist klein genug, dass ein
getrenntes Design-Repo mehr Verwaltung kostet, als es an Ordnung bringt. Diese Datei legt fest, **was hier
abgelegt wird, in welcher Struktur, und wer es wann überträgt.**

Im Template ist der Ordner leer — er füllt sich mit der ersten Lieferung des neuen Projekts.

## Grundregel: drei Zonen

| Zone | Ort | Rolle |
|---|---|---|
| Verbindliche Werte | `src/styles/global.css` | Einzige Quelle der Wahrheit für Farben, Fonts, Tokens. Code liest nur hier. |
| Designstand | `design/` | Master, Design System, Entscheidungen. Menschenlesbar, versioniert, **nicht ausgeliefert**. |
| Genutzte Teilmenge | `public/`, `src/assets/` | Die Dateien, die der Build tatsächlich braucht. Bewusst kopiert. |

`design/` definiert keine parallel gültige Palette — es dokumentiert die, die in `global.css` steht.
Weichen beide voneinander ab, gilt `global.css` und `design/` wird korrigiert.

Die Duplikation zwischen `design/` und `public/` ist Absicht, kein Versehen: Astro kopiert nur
`public/` unverändert nach `dist/`. Der Designstand darf sich ändern, ohne dass der Build wackelt.

## Struktur

```
design/
  README.md                    diese Datei — Konvention
  UEBERNAHME.md                Einbaustand: was schon im Projekt steckt
  design-system/               das Design System aus Claude Design, flach und nachziehbar
  <bereich>/                   weitere flache Quellordner, z. B. logo/, og/
  handoff/                     NUR Altbestand: Lieferungen ohne lebende Quelle
    YYYY-MM-DD-<thema>/        eingefroren, siehe unten
  archive/                     abgelöste Stände
```

Ordner entstehen mit ihrer ersten Datei — Git versioniert keine leeren Verzeichnisse.

### Der Normalfall: flache Quellordner

**Ein Ordner je Gegenstand, ein gültiger Stand je Datei, kein Datum im Namen.** Der Verlauf steckt in
der Git-Historie, nicht in Ordnernamen.

Das gilt, weil die Quelle lebt: Claude-Design-Projekte sind über das **`DesignSync`**-Tool direkt les-
und schreibbar. Ein Stand lässt sich jederzeit neu ziehen, also muss keine Kopie ihn konservieren.
Ein datierter Lieferordner würde bei jedem Sync einen weiteren Ordner daneben erzeugen.

Zu jedem Ordner gehört ein Eintrag in `UEBERNAHME.md` — was daraus im Projekt steckt und was offen ist.

### Handoff-Ordner: Altbestand, keine Vorgabe mehr

`handoff/<YYYY-MM-DD>-<thema>/` war die ursprüngliche Regel, entstanden unter einer Annahme, die nicht
mehr stimmt: dass eine Lieferung nur als heruntergeladene Kopie existiert und deshalb eingefroren werden
muss, weil niemand mehr an die Quelle kommt. Der Ordner *war* das Archiv, weil er das Einzige war.

Mit `DesignSync` ist die Quelle erreichbar. **Für neue Lieferungen wird kein Handoff-Ordner mehr
angelegt.** Bestehende bleiben unverändert liegen: die Lieferungen aus der Zeit vor den
Design-System-Projekten (Sommer 2026) enthalten Entwürfe und verworfene Varianten, die in keinem
lebenden Projekt stehen. Sie sind dort das einzige Zeugnis und werden nicht überschrieben.

### Screens

`design/pages/<route>/` als eingefrorener HTML-Export je Route hat sich **nicht bewährt** und wird nicht
mehr angelegt: bei Assets zieht der Code aus `design/`, bei Seiten wäre es umgekehrt — ein Export
veraltet mit dem nächsten Commit, und nichts erzeugt etwas daraus. Seitengestaltung findet im
Claude-Design-Projekt statt (`ui_kits/`), als Baustein-Rekonstruktion statt als Spezifikation je Route.

### Bindung an den Änderungsprozess

Nutzt das Projekt OpenSpec, ist eine Design-Lieferung der `design`-Schritt in
`proposal → design → implementation → verification`, und der Verweis läuft in beide Richtungen: der
Ordner nennt die Change-ID, die `tasks.md` der Change nennt den Pfad. Ohne OpenSpec ist
`UEBERNAHME.md` der einzige Nachweis, was aus einer Lieferung eingebaut ist.

## Wer überträgt was, wann

| Schritt | Wer |
|---|---|
| Artefakt entsteht (Logo, Screen, Design System) | Design |
| Stand ins Repo ziehen (`DesignSync`) bzw. gelieferte Dateien ablegen | Entwicklung, committet |
| Kopie der benötigten Dateien nach `public/` bzw. `src/assets/` | Entwicklung, im Rahmen der Change |
| Eintrag in `UEBERNAHME.md` | Entwicklung, mit demselben Commit wie der Einbau |
| Ablösung: alter Stand nach `archive/` | Entwicklung, beim Einbau des Nachfolgers |

Der Commit, der ein Artefakt einbaut, aktualisiert im selben Zug `UEBERNAHME.md`. Getrennte Commits
laufen erfahrungsgemäß auseinander.

## Was hier nicht hingehört

- **Generierte Exporte in Serie.** Wo das SVG die Quelle ist, gehören PNG-Stufen in einen Build-Schritt
  (siehe `scripts/`). Als Beleg einer Lieferung sind sie im Quellordner in Ordnung.
- **Ein eigenes npm-Paket oder Submodule für den Designstand.** Bei dieser Projektgröße Mehraufwand ohne
  Gegenwert. Geteilte *Komponenten* gehen nach `@tarkancorak/ui-kit`, nicht hierher.
- **`design/` im Build.** Wird nicht ausgeliefert und soll es nicht werden.
