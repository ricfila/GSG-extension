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
* Ingredienti

## Installazione