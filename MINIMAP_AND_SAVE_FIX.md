# Fix: MiniMap Removal & Node Save Improvements

## Modifiche Effettuate

### 1. ✅ Rimossa MiniMap
**File:** `FlowEditor.jsx`

- Rimosso import di `MiniMap` da reactflow
- Rimosso componente `<MiniMap />` dal render
- La canvas ora mostra solo Background e Controls

---

### 2. 🔧 Migliorato Salvataggio Dati Nodo
**File:** `FlowEditor.jsx`

**Problema Originale:**
Quando aggiungi un nodo e lo configuri, i dati non vengono salvati immediatamente. Il salvataggio avviene solo al secondo/terzo edit o dopo reload della pagina.

**Modifiche Applicate:**

#### A. Ridotto Debounce
- **Prima:** 1000ms (1 secondo)
- **Dopo:** 500ms (0.5 secondi)
- **Beneficio:** Salvataggio più reattivo, dimezzato il tempo di attesa

#### B. Aggiunto Logging Dettagliato
```javascript
console.log('[FlowCanvas] Saving flow data...', { 
    nodesCount: nodes.length, 
    edgesCount: edges.length 
});

console.log('[FlowCanvas] Calling Livewire saveFlowData', {
    nodes: flowData.nodes.length,
    edges: flowData.edges.length
});
```

**Benefici:**
- Visibilità completa del processo di salvataggio
- Debug facilitato per capire quando e cosa viene salvato
- Tracciamento del numero di nodi ed edge salvati

#### C. Aggiunta Dipendenza `getViewport`
- Aggiunto `getViewport` alle dipendenze del `useEffect`
- Assicura che il salvataggio si triggeri anche quando cambia il viewport

---

## Come Testare

### Test 1: Verifica Salvataggio Immediato
1. Apri la console del browser (F12)
2. Aggiungi un nuovo nodo
3. Configura i dati del nodo (es. cambia un campo)
4. Osserva i log nella console:
   - Dovresti vedere `[FlowCanvas] Saving flow data...` dopo 500ms
   - Seguito da `[FlowCanvas] Calling Livewire saveFlowData`

### Test 2: Verifica Persistenza
1. Aggiungi un nodo e configuralo
2. Aspetta 1 secondo
3. Ricarica la pagina
4. Il nodo dovrebbe mantenere la configurazione

### Test 3: Verifica MiniMap Rimossa
1. Apri il flow editor
2. Verifica che la MiniMap non sia più visibile nell'angolo

---

## Possibili Cause del Problema Originale

Se il problema persiste dopo queste modifiche, le cause potrebbero essere:

### 1. **Stato Locale del Nodo Non Propagato**
I nodi potrebbero gestire lo stato localmente senza aggiornare lo state di ReactFlow.

**Soluzione:** Verificare che ogni nodo usi correttamente `setNodes()` quando i dati cambiano.

### 2. **Livewire Non Riceve i Dati**
Il metodo `saveFlowData` di Livewire potrebbe non salvare correttamente.

**Soluzione:** Controllare il metodo PHP `saveFlowData` nel componente Livewire.

### 3. **Race Condition**
Il salvataggio potrebbe essere cancellato da un nuovo trigger del debounce.

**Soluzione:** I log aiuteranno a identificare se questo è il caso.

---

## Debug Avanzato

Se il problema persiste, controlla questi punti:

### 1. Verifica che setNodes() venga chiamato
Aggiungi un log in ogni nodo quando chiama `setNodes()`:
```javascript
console.log('[NodeType] Updating node data', { id, newData });
setNodes((nds) => nds.map((node) => { ... }));
```

### 2. Verifica il Backend
Controlla che il metodo Livewire salvi effettivamente:
```php
public function saveFlowData($flowData)
{
    Log::info('Saving flow data', ['data' => $flowData]);
    // ... resto del codice
}
```

### 3. Monitora le Dipendenze del useEffect
Se i log non appaiono, significa che il `useEffect` non si sta triggerando.
Possibile causa: lo state `nodes` non viene aggiornato correttamente.

---

## Prossimi Step (se il problema persiste)

1. **Forzare il salvataggio immediato** - Rimuovere completamente il debounce
2. **Aggiungere un pulsante "Save"** - Permettere salvataggio manuale
3. **Implementare auto-save indicator** - Mostrare quando i dati sono salvati
4. **Verificare ogni nodo** - Assicurarsi che tutti i nodi aggiornino correttamente lo state

---

## Files Modificati

- `resources/js/components/FlowEditor.jsx`
  - Rimosso MiniMap import e componente
  - Ridotto debounce a 500ms
  - Aggiunto logging dettagliato
  - Aggiunta dipendenza getViewport
