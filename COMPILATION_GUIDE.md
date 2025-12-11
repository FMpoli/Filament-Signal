# Schema di Compilazione e Aggiornamento Assets Plugin Filament

Per chiarire la relazione tra la build del progetto principale e quella del plugin (`Filament-Signal`), ecco come funziona il flusso:

## 1. Architettura Separata
Il plugin `Filament-Signal` è un pacchetto isolato con la sua propria configurazione di build. Questo è essenziale perché:
*   Ha dipendenze specifiche (React, React Flow) che il progetto principale (Filament/Laravel) non necessariamente usa o gestisce nello stesso modo.
*   Ha bisogno di una configurazione Tailwind specifica che scansioni i suoi file `.jsx` per generare le classi CSS corrette.

## 2. Il Flusso di Compilazione del Plugin

Quando esegui `npm run build` dentro alla cartella `packages/Base33/Filament-Signal`:

1.  **Script Build**: Esegue `esbuild` per compilare il codice React (`resources/js`) in un unico file JS (`resources/dist/filament-signal.js`).
2.  **Style Build**: Esegue `tailwindcss` usando il `tailwind.config.js` **del plugin**.
    *   Scansiona i file PHP e JSX del plugin.
    *   Genera un file CSS (`resources/dist/filament-signal.css`).
    *   **IMPORTANTE**: Abbiamo disabilitato `preflight` (`corePlugins: { preflight: false }`) per evitare che il CSS del plugin resettasse gli stili globali dell'applicazione principale (che causava il problema grafico).
3.  **Post Build (Manuale)**: Lo script `postbuild` che abbiamo modificato copia fisicamente questi file generati (`dist`) direttamente nella cartella `public` dell'applicazione principale.
    *   `resources/dist/filament-signal.js` -> `public/js/base33/filament-signal/filament-signal-scripts.js`
    *   `resources/dist/filament-signal.css` -> `public/css/base33/filament-signal/filament-signal-styles.css`

## 3. Registrazione Assets in Laravel
Nel file `FilamentSignalServiceProvider.php`, diciamo a Filament dove trovare questi file:

```php
protected function getAssets(): array
{
    // ...
    // Registra il file CSS che abbiamo copiato
    $assets[] = Css::make('filament-signal-styles', __DIR__ . '/../resources/dist/filament-signal.css');
    
    // Registra il file JS che abbiamo copiato
    $assets[] = Js::make('filament-signal-scripts', __DIR__ . '/../resources/dist/filament-signal.js');
    
    return $assets;
}
```

Quando Filament carica la pagina, include questi file.

## Riepilogo
*   **NON** serve ricompilare il progetto principale (root) quando modifichi il plugin.
*   Devi invece ricompilare il plugin (`npm run build` dentro la cartella del package).
*   Grazie allo script `postbuild`, le modifiche vengono applicate immediatamente copiando i file nella public folder.

## Perché il tema generale si era "incasinato"?
Il plugin stava importando `tailwindcss/base` (il reset CSS di Tailwind) nel suo file CSS. Quando questo file veniva caricato nella pagina amministrativa, il reset sovrascriveva gli stili base di Filament/Minim. Rimuovendo `preflight` dalla config del plugin, abbiamo isolato gli stili del plugin in modo che non tocchino il resto della pagina.
