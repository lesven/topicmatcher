# MVP-Spezifikation  
**Interessen- & Matching-Tool für Konferenzen / Messen**

---

## 1. Produktvision

Das System ermöglicht es Besucher:innen von Konferenzen und Messen, **öffentliche „Ich suche / Ich biete“-Beiträge** zu erstellen und **Interesse an bestehenden Beiträgen zu bekunden**.  
Alle Inhalte werden **durch ein Moderationsteam kuratiert**.  
Nach der Veranstaltung können die gesammelten Kontakte **exportiert** und manuell zusammengeführt werden.

---

## 2. Zentrale Produktprinzipien

- **Mobile First**, vollständig responsiv  
- **Öffentlich lesen**, aber **kontrolliert schreiben**  
- **Keine Self-Service-Funktionen** nach Einreichung  
- **Moderationsgetrieben**  
- **Datenschutz by Default**  
- **Einsprachig (Deutsch)**

---

## 3. Rollen & Zugriffe

### Öffentliche Nutzer (ohne Login)
- Beiträge ansehen  
- Beiträge einreichen (Name + E-Mail)  
- Interesse bekunden (Name + E-Mail)

### Backoffice-Nutzer (Login erforderlich)
- **Admin**
  - Events & Kategorien verwalten
  - Beiträge moderieren
  - Backoffice-Accounts verwalten
  - Exporte durchführen
- **Moderator**
  - Beiträge moderieren
  - Interessen einsehen
  - Exporte durchführen

---

## 4. Event-Lifecycle (verbindlich)

| Status   | Öffentlich sichtbar | Beiträge möglich | Interessen möglich | Moderation | Export |
|----------|---------------------|------------------|--------------------|------------|--------|
| Draft    | Ja                  | Nein             | Nein               | Nein       | Nein   |
| Active   | Ja                  | Ja               | Ja                 | Ja         | Nein   |
| Closed   | Ja (Read-only)      | Nein             | Nein               | Nein       | Ja     |
| Archived (optional) | Nein      | Nein             | Nein               | Nein       | Ja     |

**Wichtige Regel:**  
👉 *Draft-Events sind immer leer (keine Posts, keine Interests).*

---

## 5. Funktionale Anforderungen – User Stories

### EPIC A – Öffentliche Pinnwand (Besucher)

#### US-A1: Öffentliche Beiträge anzeigen
**Als** Besucher  
**möchte ich** alle freigegebenen Beiträge eines Events sehen  
**damit** ich relevante Themen finde.

**Akzeptanzkriterien**
- Nur Beiträge mit Status „freigegeben“
- Gruppierung nach Kategorien
- Event über eindeutige URL erreichbar

---

#### US-A2: Beitrag im Detail ansehen
**Als** Besucher  
**möchte ich** einen Beitrag öffnen  
**damit** ich den Inhalt vollständig lesen kann.

**Akzeptanzkriterien**
- Beitragstext sichtbar
- Kategorie sichtbar
- CTA „Interesse bekunden“
- QR-Code sichtbar

---

#### US-A3: Interessenanzahl anzeigen
**Als** Besucher  
**möchte ich** sehen, wie viele Personen bereits Interesse bekundet haben  
**damit** ich die Relevanz einschätzen kann.

**Akzeptanzkriterien**
- Anzeige nur als Zahl
- Keine Namen oder E-Mails sichtbar
- Aktualisierung nach neuer Interessenbekundung

---

#### US-A4: Namen optional anzeigen
**Als** Besucher  
**möchte ich** optional den Namen des Beitragserstellers sehen  
**damit** ich Kontext habe.

**Akzeptanzkriterien**
- Anzeige nur, wenn Moderation „Name sichtbar“ aktiviert
- Default: anonym
- E-Mail niemals öffentlich

---

### EPIC B – Interesse bekunden

#### US-B1: Interesse bekunden
**Als** Besucher  
**möchte ich** Interesse an einem Beitrag bekunden  
**damit** der Veranstalter mich berücksichtigen kann.

**Pflichtfelder**
- Name
- E-Mail
- Datenschutz-Checkbox

**Akzeptanzkriterien**
- Formular validiert Eingaben
- Erfolgsbestätigung nach Absenden

---

#### US-B2: Interesse per QR-Code
**Als** Besucher  
**möchte ich** per QR-Code direkt zum Beitrag gelangen  
**damit** ich mobil schnell reagieren kann.

---

#### US-B3: Doppelte Interessen verhindern
**Als** System  
**möchte ich** verhindern, dass dieselbe Person sich mehrfach meldet  
**damit** die Daten sauber bleiben.

**Akzeptanzkriterien**
- Eindeutig pro `(Post, E-Mail)`
- Verständlicher Hinweis bei Duplikat

---

### EPIC C – Beitrag einreichen

#### US-C1: Beitrag einreichen
**Als** Besucher  
**möchte ich** einen Beitrag einreichen  
**damit** mein Thema sichtbar wird.

**Pflichtfelder**
- Kategorie
- Beitragstext (1–2 Sätze, Zeichenlimit)
- Name
- E-Mail
- Datenschutz-Checkbox

**Akzeptanzkriterien**
- Status nach Absenden: „eingereicht“
- Nicht öffentlich sichtbar vor Freigabe

---

### EPIC D – Moderation & Kuration (Backoffice)

#### US-D1: Event anlegen
**Als** Admin  
**möchte ich** ein Event anlegen  
**damit** eine neue Pinnwand existiert.

---

#### US-D2: Kategorien konfigurieren
**Als** Admin  
**möchte ich** 2–5 Kategorien pro Event definieren  
**damit** Inhalte strukturiert sind.

---

#### US-D3: Beiträge moderieren
**Als** Moderator  
**möchte ich** eingereichte Beiträge prüfen  
**damit** nur passende Inhalte veröffentlicht werden.

**Aktionen**
- Freigeben
- Ablehnen
- Bearbeiten
- Archivieren

---

#### US-D4: Namenssichtbarkeit steuern
**Als** Moderator  
**möchte ich** festlegen, ob der Name öffentlich sichtbar ist  
**damit** ich situationsabhängig entscheiden kann.

---

#### US-D5: Interessen einsehen
**Als** Moderator  
**möchte ich** sehen, wer Interesse bekundet hat  
**damit** ich den Export vorbereiten kann.

---

### EPIC E – Export & Abschluss

#### US-E1: Event schließen
**Als** Admin  
**möchte ich** ein Event schließen  
**damit** keine weiteren Einreichungen möglich sind.

---

#### US-E2: Export durchführen
**Als** Admin/Moderator  
**möchte ich** alle Daten eines Events exportieren  
**damit** ich die Nachbereitung manuell durchführen kann.

**Export enthält**
- Event
- Kategorie
- Beitragstext
- Name & E-Mail des Einreichers
- Interessenanzahl
- Name & E-Mail der Interessenten

---

### EPIC F – Backoffice-Accounts

#### US-F1: Benutzerliste anzeigen
**Als** Admin  
**möchte ich** alle Backoffice-Accounts sehen.

---

#### US-F2: Benutzer anlegen
**Als** Admin  
**möchte ich** neue Admins oder Moderatoren anlegen.

**Akzeptanzkriterien**
- Initialpasswort wird gesetzt
- Passwortwechsel beim ersten Login erforderlich

---

#### US-F3: Benutzer bearbeiten
**Als** Admin  
**möchte ich** Benutzer ändern können.

---

#### US-F4: Benutzer deaktivieren
**Als** Admin  
**möchte ich** Benutzer deaktivieren  
**damit** kein Zugriff mehr möglich ist.

---

#### US-F5: Passwort zurücksetzen
**Als** Admin  
**möchte ich** Passwörter zurücksetzen können.

---

## 6. Nicht-funktionale Anforderungen

### 6.1 Technologie (verbindlich)

- **Backend:** PHP 8.2+, Symfony 7.4 LTS  
- **Rendering:** Twig (server-side), Monolith  
- **Datenbank:** MariaDB 10.6+  
- **ORM:** Doctrine ORM + Migrations  
- **Auth:** Symfony Security  
- **Frontend:** Mobile-first, responsive (Bootstrap 5 empfohlen)

---

### 6.2 Architektur
- DDD-orientiert (Domain / Application / Infrastructure)
- Klare Bounded Contexts:
  - EventManagement
  - Participation
  - Backoffice
- CQRS-light für Interest-Counter

---

### 6.3 Performance
- Öffentliche Seiten < 3 Sekunden Ladezeit (Mobilfunk)
- Interessenregistrierung idempotent

---

### 6.4 Sicherheit & Datenschutz
- Keine personenbezogenen Daten in URLs
- E-Mail niemals öffentlich
- Datenschutz-Zustimmung verpflichtend
- Löschfunktion nach Event

---

### 6.5 Passwortregeln (MVP)
- Mindestlänge: 8 Zeichen
- Keine weiteren Komplexitätsregeln

---

### 6.6 Betrieb & Qualität
- Docker-fähig
- Logging für Fehler & Schreibaktionen
- Unit-Tests für Domain-Regeln
- Mindestens 1–2 Feature-Tests für Kernflows

---

## 7. Explizit **nicht** Teil des MVP

- E-Mail-Versand
- Direktes Messaging
- Automatisches Matching
- Mehrsprachigkeit
- Native Apps
- Besucher-Login
- Self-Service nach Einreichung

---

## 8. Abschluss

Dieses Dokument beschreibt den **vollständigen MVP-Scope**, die **Domänenlogik**, **User Stories** und **nicht-funktionalen Anforderungen**.
