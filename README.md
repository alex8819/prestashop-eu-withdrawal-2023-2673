# EU Withdrawal Button for PrestaShop — Pulsante di Recesso (Directive 2023/2673)

> ⚠️ **BETA (v0.2)** — funzionante e testato, ma in fase beta: provalo in staging e segnala problemi.
> *Working and tested, but beta: test it on staging and report issues.*

**Free & open-source PrestaShop 8 / 8.2 module** that adds the EU statutory **right-of-withdrawal
function** ("withdrawal button" / *pulsante di recesso* / *Widerrufsbutton* / *bouton de rétractation* /
*botón de desistimiento*) required by **Directive (EU) 2023/2673** — **Art. 11a** of the Consumer Rights
Directive 2011/83/EU; in Italy **Art. 54‑bis del Codice del Consumo (D.Lgs. 209/2025)** — applicable from
**19 June 2026**.

[![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7.6%20--%208.2-blue)](https://www.prestashop.com)
[![License](https://img.shields.io/badge/license-GPL--3.0-green)](LICENSE)
[![Status](https://img.shields.io/badge/status-beta%200.2-orange)]()
[![EU Directive](https://img.shields.io/badge/EU%20Directive-2023%2F2673-yellow)]()

**Keywords:** prestashop withdrawal button, prestashop 8.2 recesso, modulo recesso prestashop gratis,
pulsante di recesso, diritto di recesso, EU Directive 2023/2673, Art. 54-bis, Art. 11a, right of withdrawal,
Widerrufsbutton PrestaShop, bouton de rétractation, botón de desistimiento, free open source returns compliance.

> 🛈 **Disclaimer / Avvertenza:** This module is a technical aid to compliance, **not legal advice**.
> Have your implementation reviewed by your legal advisor. — Questo modulo è uno strumento tecnico di
> ausilio alla conformità, **non costituisce consulenza legale**. Fai validare l'implementazione dal tuo legale.

---

## 🇮🇹 Italiano

### Perché esiste
Dal **19 giugno 2026** ogni e‑commerce B2C nell'UE deve offrire una **funzione digitale di recesso** facile,
visibile e disponibile per tutta la durata del periodo di recesso. PrestaShop **non** include nativamente un
pulsante conforme: questo modulo colma il vuoto, **gratis e open source**. È, a quanto ci risulta, il **primo
modulo open‑source per PrestaShop** dedicato a questo obbligo.

### Funzionalità
- **Pulsante "Recedi dal contratto"** con dicitura statutaria, nel **dettaglio ordine** dell'area cliente.
- **Recesso totale o parziale** (selezione dei singoli prodotti e quantità).
- **Supporto ospiti** (guest checkout): pagina di **lookup sicura** con n° ordine + email.
- **Conferma a due step reale** (dichiarazione → revisione) con funzione esplicita **"Conferma il recesso"**
  (Art. 11a §3), senza dark pattern, **nessuna motivazione richiesta**.
- **Modulo Allegato I‑B** (modello di recesso UE) incluso in pagina e nell'email, nelle 5 lingue.
- **Etichetta del pulsante personalizzabile** per lingua dal pannello (default = dicitura statutaria).
- **Ricevuta su supporto durevole**: email automatica al cliente con dichiarazione, **data e ora** e dati ordine.
- **Notifica al negozio** + **nota automatica** nell'ordine (back office).
- **Finestra di recesso** configurabile (14 giorni; decorrenza da consegna o da data ordine).
- **Pannello di gestione in back office**: elenco richieste, stati (in attesa / elaborato / rifiutato /
  rimborsato), reinvio ricevuta, link all'ordine.
- **5 lingue**: IT, EN, FR, DE, ES (diciture statutarie per lingua).
- **Privacy by design**: IP anonimizzato.

### Rispetto di SEO e performance
- Il pulsante vive solo in **pagine private** (area cliente) → `noindex`, **fuori dalla cache full‑page**.
- La pagina ospiti invia `X-Robots-Tag: noindex` e `Cache-Control: no-store`.
- **Nessun CSS/JS** caricato sulle pagine pubbliche cachate (home, categorie, prodotti).
- Nessuna modifica al core, a `robots.txt`, canonical o sitemap.

### Installazione
1. Scarica lo ZIP della release **oppure** copia la cartella `euwithdrawal/` in `modules/`.
2. Back office → **Moduli → Carica un modulo** (ZIP) **o** Moduli → installa `euwithdrawal`.
3. L'installazione **crea automaticamente** le tabelle necessarie nel database
   (`ps_euwithdrawal_request`, `ps_euwithdrawal_request_item`).
4. Configura dal **pannello del modulo** (vedi sotto).

### Pannello di configurazione
| Opzione | Default | Descrizione |
|---|---|---|
| Attivo | Sì | Abilita/disabilita la funzione |
| Giorni periodo di recesso | 14 | Termine legale |
| Decorrenza | Consegna | Dalla consegna (consigliato per beni) o dalla data ordine |
| Recesso ospiti | Sì | Lookup n° ordine + email |
| Email negozio | Email shop | Destinatario delle notifiche |
| Stati ordine idonei | Tutti i validi | Limita gli stati in cui appare il pulsante |

### Disinstallazione
La disinstallazione rimuove tab, hook, configurazioni **e le tabelle** create.

---

## 🇬🇧 English

### Why it exists
From **19 June 2026** every EU B2C online shop must provide an **easy, visible digital withdrawal function**
available throughout the withdrawal period. PrestaShop has **no** native compliant button — this module fills
the gap, **free and open source**. As far as we know, it is the **first open‑source PrestaShop module** for
this obligation.

### Features
- **"Withdraw from the contract" button** with statutory wording, on the customer **order detail** page.
- **Full or partial withdrawal** (select individual products and quantities).
- **Guest support**: secure **lookup page** (order reference + email).
- **Genuine two-step confirmation** (declaration → review) with an explicit **"Confirm withdrawal"** function
  (Art. 11a §3), no dark patterns, **no reason required**.
- **Annex I‑B model withdrawal form** included on screen and in the email, in 5 languages.
- **Customisable button label** per language from the panel (statutory wording as default).
- **Durable‑medium acknowledgement**: automatic email to the customer with the statement, **date & time** and
  order data.
- **Merchant notification** + **automatic order note** (back office).
- **Configurable withdrawal window** (14 days; starting from delivery or order date).
- **Back‑office management panel**: requests list, statuses (pending / processed / rejected / refunded),
  resend receipt, order link.
- **5 languages**: IT, EN, FR, DE, ES (per‑language statutory wording).
- **Privacy by design**: anonymised IP.

### SEO & performance friendly
- The button lives only on **private pages** (customer area) → `noindex`, **outside the full‑page cache**.
- The guest page sends `X-Robots-Tag: noindex` and `Cache-Control: no-store`.
- **No CSS/JS** added to public cached pages (home, categories, products).
- No core changes, no `robots.txt` / canonical / sitemap changes.

### Installation
1. Download the release ZIP **or** copy the `euwithdrawal/` folder into `modules/`.
2. Back office → **Modules → Upload a module** (ZIP) **or** Modules → install `euwithdrawal`.
3. Installation **automatically creates** the required database tables
   (`ps_euwithdrawal_request`, `ps_euwithdrawal_request_item`).
4. Configure from the **module panel** (see above).

### Uninstall
Uninstalling removes the tab, hooks, configuration **and the created tables**.

---

## ⚖️ Compliance mapping (Directive (EU) 2023/2673, Art. 11a)
| Requirement | Module |
|---|---|
| §1 Withdrawal button, legible, available during the period | Statutory label, shown on order detail while within the window |
| §2 Consumer provides name, contract id, contact for confirmation | Pre‑filled from order; guest via reference + email |
| §3 Distinct "confirm withdrawal" function | Dedicated confirmation page + explicit confirm button |
| §4 Acknowledgement on durable medium (content + date/time) | Automatic email with full statement, date and time |

## 🗺️ Roadmap
- PDF receipt attached to the acknowledgement email.
- Downloadable Annex I‑B model withdrawal form.
- Art. 59 exemptions (excluded goods/services) with explanatory note.
- Tamper‑evident request log (hash‑chaining).
- Additional languages.

## 🤝 Contribuisci / Contribute
**Cerchiamo collaboratori** per rendere questo modulo lo standard open‑source del recesso UE su PrestaShop!
**We are looking for contributors** to make this the open‑source standard for EU withdrawal on PrestaShop!

- 🐛 Apri una **Issue** per bug o proposte / open an **Issue** for bugs or ideas.
- 🔀 Manda una **Pull Request** (vedi [CONTRIBUTING.md](CONTRIBUTING.md)).
- 🌍 Traduzioni, revisione legale per‑paese, test su versioni PrestaShop diverse: benvenuti!
- ⭐ Lascia una stella se ti è utile / star the repo if it helps.

## 📄 Licenza / License
[GPL‑3.0‑or‑later](LICENSE).
