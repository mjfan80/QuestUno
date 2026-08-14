# QuestUno – Amministrazione

**Versione:** 0.1 (Draft)

---

# 1. Menu principale

QuestUno aggiunge un nuovo menu nell'amministrazione WordPress.

```
QuestUno

├── Dashboard

├── Percorsi

├── Checkpoint

├── Gruppi

├── Partecipazioni

├── QR Code

├── Esportazioni

├── Eventi

└── Impostazioni
```

---

# 2. Dashboard

La Dashboard mostra una panoramica del plugin.

Informazioni previste:

- numero Percorsi;
- Percorsi attivi;
- numero delle Partecipazioni;
- Partecipazioni attive;
- numero totale delle scansioni;
- numero delle scansioni non valide;
- numero delle scansioni duplicate;
- ultime scansioni.

---

# 3. Percorsi

La schermata mostra, per impostazione predefinita, i Percorsi non archiviati.

L'amministratore può scegliere di visualizzare anche i Percorsi archiviati.

Operazioni disponibili:

- Nuovo
- Modifica
- Archivia
- Statistiche

I Percorsi archiviati sono disponibili tramite l'apposito filtro di stato WordPress. L'azione **Ripristina** riporta il Percorso in bozza: una nuova pubblicazione è esplicita e sottoposta nuovamente alla verifica della configurazione.

L'azione **Statistiche** apre il riepilogo aggregato del singolo Percorso, senza statistiche per Checkpoint.

La lista include inoltre la colonna **Configurazione** per i soli Percorsi pubblicati: mostra in forma compatta `✓ OK`, il numero di warning oppure il numero di errori bloccanti e, quando presenti, anche i warning. La sintesi apre la modifica del Percorso, dove rimane disponibile il dettaglio completo. Bozze, Percorsi archiviati e gli altri stati non ricevono segnalazioni nella panoramica.

---

## Creazione Percorso

Campi previsti

- Nome
- Descrizione
- Stato
- Data apertura
- Data chiusura
- Checkpoint iniziale
- Checkpoint finale

## Verifica configurazione

La schermata di modifica del Percorso mostra una sezione read-only con controlli superati, errori bloccanti, warning e verdetto di pubblicabilità. Gli errori bloccano la pubblicazione; i warning no.

Un warning viene mostrato per ogni Checkpoint ordinario che non possiede proprie Dipendenze. Checkpoint iniziale e finale sono esclusi; essere destinazione di una Dipendenza altrui non elimina il warning.

---

# 4. Checkpoint

I Checkpoint vengono gestiti tramite il Custom Post Type.

La schermata di modifica del Checkpoint aggiunge un metabox QuestUno.

---

## Configurazione

Campi previsti

- Percorso
- Gruppo
- Token (sola lettura)
- QR Code
- Dipendenze
- Pulsante di anteprima

---

# 5. Regole del Checkpoint

Le Dipendenze vengono configurate tramite il metabox QuestUno presente nella schermata di modifica del Checkpoint.

Ogni Dipendenza può riferirsi a:

- un Checkpoint;
- un Gruppo.

Nella versione 1.0 tutte le Dipendenze vengono valutate con logica AND. Possono essere aggiunte più regole AFTER o BEFORE tramite il metabox.

---

## Note

Le regole possono essere configurate solo dopo il primo salvataggio del Checkpoint.

Questo consente di selezionare esclusivamente Checkpoint e Gruppi già esistenti.
---

# 6. Partecipazioni

La schermata mostra:

- utente;
- percorso;
- stato;
- data inizio;
- data termine;
- checkpoint validati.

Operazioni:

- visualizza;
- annulla.

---

# 7. Eventi

La schermata mostra:

- data;
- utente;
- percorso;
- checkpoint;
- tipo;
- esito.

Sono disponibili filtri per:

- percorso;
- utente;
- Checkpoint;
- intervallo temporale;
- esito.

---

## Statistiche Path

L'azione **Statistiche** del singolo Percorso mostra Partecipazioni totali, in corso, terminate, completate, annullate, scansioni totali, accepted, duplicate e non valide.

---

# 8. Impostazioni

Configurazioni previste.

## Privacy

- registra indirizzo IP;
- registra User Agent.

Le due opzioni sono indipendenti e disattivate per impostazione predefinita. Si applicano esclusivamente agli Eventi creati dopo il salvataggio dell'impostazione.

---

## QR Code

- formato PNG;
- formato SVG;
- download del QR Code esistente.

Dimensione, livello di correzione e logo non sono configurabili nella versione 1.0.

---

## Esportazione

- separatore CSV configurabile (virgola, punto e virgola o tabulazione);
- codifica sempre UTF-8.

---

## Internazionalizzazione

QuestUno utilizza il locale nativo di WordPress. Non dispone di un selettore di lingua proprietario. La release WordPress.org include l'inglese come lingua sorgente; la traduzione italiana resta nel repository per la successiva importazione su translate.wordpress.org, che distribuisce i language pack separatamente.

---

# 9. Principi

L'interfaccia amministrativa deve:

- utilizzare componenti standard di WordPress;
- evitare pagine personalizzate quando non necessarie;
- essere coerente con il resto dell'amministrazione WordPress;
- risultare utilizzabile anche con un elevato numero di Percorsi e Checkpoint.
