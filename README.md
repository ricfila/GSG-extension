# GSG-extension
Il presente repository fornisce degli strumenti e delle funzioni aggiuntive per il programma [Gestione Stand Gastronomico](https://www.gestionestandgastronomico.it) tra cui:

### Per i camerieri
>Accesso tramite IPSERVER/palmare/
* L'associazione del tavolo dopo la stampa dell'ordine tramite palmare per smartphone
* Una schermata di riepilogo sullo stato di ogni ordine

### Per le casse
>Accesso tramite IPSERVER/pannello/casse.php
* La possibilità di apportare lievi modifiche agli ordini già stampati, anche di altre casse
* Un riepilogo delle giacenze vendute e ancora da servire
* La stampa del rendiconto economico, che tiene traccia delle variazioni di prezzo riportate dopo le modifiche inter-casse

### Per il personale di servizio
>Accesso tramite IPSERVER/pannello/
* Un modo pratico per evadere gli ordini (solo copie bar e cucina)
* Ricerca rapida degli ordini per identificativo (progressivo o ID), nominativo e tavolo
* Un pannello riepilogativo delle ultime associazioni dei tavoli
* Statistiche sui tempi di servizio
* Alcune semplici routine di servizio di bonifica del database

## Descrizione del sistema
Questa estensione è stata pensata e sviluppata per adattarsi su misura alle esigenze della sagra della [Parrocchia di Stra](https://www.parrocchiadistra.it). Nell'adottare questo programma potreste riscontrare alcune limitazioni o addirittura funzionamenti non previsti dovuti al ristretto utiilizzo inizialmente previsto. Abbiamo voluto condividere comunque il codice, che può essere liberamente preso, adattato e migliorato secondo le esigenze di ciascuno.

Di seguito un riepilogo delle principali scelte architetturali di cui è bene essere a conoscenza se si vuole mettere mano al codice:

* Le webapp sono pensate per funzionare in parallelo su due server distinti, in ognuno dei quali c'è un'istanza del database (da tenere allineate tramite dump o replica di postgresql). In caso di guasto del server principale il secondo è già in funzione e pronto per operare, a patto di avere i database allineati. Per usufruire dei collegamenti ipertestuali tra i due server vanno modificati gli indirizzi IP nelle variabili `$ipserver1` e `$ipserver2` nel file `pannello/css/bootstrap.php`
* I reparti gestiti sono solo due: cucina e bar.
* La suddivisione dei turni non avviene solo in base alla giornata ma anche in base all'ora: fino alle 17:00 gli ordini emessi rientrano nel turno del pranzo, dalle ore 17:00 fino alla mezzanotte gli ordini emessi rientrano nel turno della cena. Tutte le webapp qui presentate operano nel medesimo turno (identificato da data e pranzo/cena) indicato nella tabella shiftstart del database. Il turno viene aggiornato a quello attuale (determinato dall'orario di sistema) cliccando sul pulsante verde "Avvia nuova sessione" che compare all'avvio.
* Nelle associazioni dei tavoli, il nome del cameriere che esegue l'operazione viene salvato nel campo "cassiere" dell'ordine.
* Nelle statistiche sul servizio gli intervalli indicati corrispondono al tempo che intercorre tra l'associazione dell'ordine al tavolo e la sua evasione. Senza il primo passaggio (e cioè senza il record nella tabella "passaggi_stato") le statistiche non sono calcolabili, tuttavia è possibile riferirsi all'ora di emissione dell'ordine modificando opportunamente il codice. Fare riferimento al file `pannello/php/function.php` nella versione del commit [pannello2022](https://github.com/ricfila/GSG-extension/commit/b79265aacca7fd786a8e4431dd1662d129b945dd#diff-cef0a8d117f12d2f790dfdcc848955936c612dc6793dcf996e07611a06b325dd).
* Con questa estensione è possibile avere articoli composti da porzioni decimali di ingredienti (es. "Gnocchi al baccalà" composti da 1 gnocchi e 0.5 baccalà). Per poter inserire porzioni decimali occorre impostare un divisore intero (ad esempio 100) nella tabella dati_ingredienti, creando un record con la stessa descrizionebreve dell'ingrediente a cui ci si vuole riferire (non ci devono essere due ingredienti con la stessa descrizione). Il divisore impostato andrà a dividere tutte le quantità dell'ingrediente in tutti gli articoli in cui esso compare. Con il divisore 100, quindi, per aggiungere una porzione effettiva bisogna aggiungerne 100 (il quoziente farà 1), mentre per aggiungerne mezza basterà scrivere 50 (il quoziente farà 0.5). Si consiglia di impostare dei divisori solo per gli ingredienti obbligatori in tutti gli articoli in cui compaiono. Dove non viene impostato un divisore, questo viene sottinteso come pari a 1.<br>Questa funzione risulta particolarmente comoda per un monitoraggio in tempo reale delle quantità di ingredienti da preparare (corrispondenti agli ordini emessi e non ancora evasi) nella schermata "Ultime vendite" dell'Ausilio alle casse. Il selettore temporale serve ad escludere gli ordini stampati da molto tempo che probabilmente sono già stati serviti, ma per distrazione possono non essere stati contrassegnati come evasi.

## Installazione
L'installazione di questo sistema è da effettuarsi su un solo computer (volendo anche un Raspberry), preferibilmente quello su cui risiede anche il database PostgreSQL ma non è indispensabile. Oltre a questo occorre installare anche un server web e l'interprete PHP. Tutti gli strumenti qui presentati potranno essere utilizzati da tutti i dispositivi (PC, tablet e smartphone) collegati nella rete, compreso quello su cui si installa la presente estensione, e saranno accessibili da un browser con i collegamenti indicati in fondo.

1. Installare Apache e PHP, se il PC usa Linux i comandi da usare sono i seguenti:
```
sudo apt update
sudo apt install apache2

sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.1 php8.1-cli
sudo apt install libapache2-mod-php
sudo apt-get install php-pgsql
```
2. Dopo aver installato PHP aprire il file di configurazione `php.ini` (in Linux dovrebbe trovarsi in `/etc/php/8.1/apache2/php.ini` o in `/etc/php/8.1/cli/php.ini`) e decommentare (ovvero togliere il `;` ad inizio riga) le righe `extension=pdo_pgsql` e `extension=pgsql` in modo da attivare le estensioni per postgresql.
3. In alto in questa pagina cliccare sul pulsante verde "Code" e su "Download ZIP" per scaricare il codice.
4. Estrarre i file del pacchetto zip e spostarli nella directory del server web, in Linux corrisponde alla cartella `/var/www/html` (quindi dovranno crearsi le cartelle `/var/www/html/pannello` e `/var/www/html/palmare` e dovrà comparire il file `/var/www/html/connect.php` in questo esatto percorso).
5. Aprire pgAdmin o una console di PostgreSQL ed eseguire la query nel file `install.sql`.
6. Modificare il file `connect.php` impostando i parametri richiesti per la connessione al database e, volendo, delle password per le schermate.
7. Il sistema è pronto per l'esecuzione tramite browser, le schermate disponibili e i loro indirizzi sono (supponendo che l'indirizzo IP del server sia 192.168.1.1):
   * **Palmare sagra** (*per tablet e smartphone*): `http://192.168.1.1/palmare/`
   * **Ausilio alle casse** (*per pc e tablet*): `http://192.168.1.1/pannello/casse.php`
   * **Pannello evasione comande** (*per pc e tablet*): `http://192.168.1.1/pannello/`

## Sviluppi futuri
Non sono previsti ulteriori modifiche di grossa portata a questo sistema, lo sviluppatore resta però disponibile a chiarire dubbi e problemi di implementazione, può essere contattato tramite messaggi privati o e-mail attraverso il [forum di GSG](https://gestionestandgastronomico.forumfree.it/?act=Profile&MID=11997612) (i contatti sono nel pulsante con la busta in alto a destra). Se qualcuno volesse cimentarsi nell'aggiunta di nuove funzioni o correzioni saranno ben accette richieste di merge, purché compatibili con le funzionalità originali del progetto.