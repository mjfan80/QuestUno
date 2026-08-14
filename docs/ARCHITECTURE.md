# QuestUno – Software Architecture

**Versione:** 0.1 (Draft)

---

# 1. Obiettivi

L'architettura di QuestUno deve essere:

- semplice;
- modulare;
- facilmente estendibile;
- conforme agli standard WordPress;
- facilmente manutenibile.

Ogni componente deve avere una singola responsabilità.

---

# 2. Principi architetturali

QuestUno utilizza esclusivamente le API ufficiali di WordPress.

Il plugin non sostituisce funzionalità già offerte dalla piattaforma.

WordPress rimane responsabile della gestione di:

- utenti;
- autenticazione;
- ruoli;
- permessi;
- editor Gutenberg;
- contenuti;
- media;
- traduzioni;
- amministrazione.

QuestUno utilizza il locale nativo del sito o dell'utente WordPress. Non definisce una preferenza di lingua proprietaria. La release WordPress.org distribuisce l'inglese come lingua sorgente; la traduzione italiana rimane nel repository per la successiva importazione su translate.wordpress.org, che distribuisce i language pack separatamente.

QuestUno implementa esclusivamente la logica del gioco.

---

# 3. Architettura generale

L'applicazione è suddivisa nei seguenti livelli.

```
+---------------------------+
| WordPress                 |
+---------------------------+
             │
             ▼
+---------------------------+
| Plugin Bootstrap          |
+---------------------------+
             │
             ▼
+---------------------------+
| Controller                |
+---------------------------+
             │
             ▼
+---------------------------+
| Services                  |
+---------------------------+
             │
             ▼
+---------------------------+
| Repositories              |
+---------------------------+
             │
             ▼
+---------------------------+
| Database                  |
+---------------------------+
```

Ogni livello comunica esclusivamente con il livello immediatamente sottostante.

---

# 4. Bootstrap

Il file principale del plugin ha il solo compito di:

- verificare i requisiti;
- caricare le classi;
- registrare hook e filtri;
- inizializzare il plugin.

Non deve contenere logica di business.

---

# 5. Controller

I Controller rappresentano il punto di ingresso delle richieste.

Devono:

- ricevere la richiesta;
- validarla;
- invocare i servizi necessari;
- costruire la risposta.

Non devono contenere logica di business.

---

# 6. Services

I Services implementano la logica del plugin.

Ogni Service deve avere una sola responsabilità.

Esempi:

- Token Service;
- Validation Service;
- Participation Service;
- Event Service;
- Path Service.

I Services non devono conoscere il database.

---

# 7. Repositories

I Repository sono l'unico componente autorizzato ad accedere direttamente al database.

Tutte le query SQL devono essere centralizzate nei Repository.

I Services non devono utilizzare direttamente `$wpdb`.

---

# 8. Modelli

I Modelli rappresentano le entità del dominio.

Esempi:

- Path;
- Checkpoint;
- Participation;
- Event;
- Dependency;
- Checkpoint Group.

I Modelli non devono contenere logica di accesso al database.

---

# 9. Routing

Il plugin intercetta gli URL pubblici dei QR Code.

Ogni URL contiene esclusivamente il token pubblico del Checkpoint.

Il token viene risolto una sola volta.

Dopo la risoluzione vengono utilizzati esclusivamente gli identificativi interni.

---

# 10. Separazione tra contenuto e logica

I contenuti dei Checkpoint sono gestiti dal Custom Post Type WordPress.

La logica del gioco è gestita esclusivamente dalle tabelle del plugin.

Le due componenti devono rimanere indipendenti.

---

# 11. Estendibilità

Nuove funzionalità dovranno essere implementate introducendo nuovi Services, Repository o Modelli.

Le componenti esistenti dovranno essere modificate solo quando strettamente necessario.

---

# 12. Principi di implementazione

L'implementazione dovrà rispettare i seguenti principi.

- una classe deve avere una sola responsabilità;
- evitare duplicazione del codice;
- evitare file di grandi dimensioni;
- evitare dipendenze circolari;
- evitare accesso diretto al database al di fuori dei Repository;
- utilizzare esclusivamente API ufficiali di WordPress;
- mantenere separati interfaccia, logica di business e persistenza dei dati.
