# 🚀 Voodflow - Sistema Auto-Discovery Completo

## ✅ Sistema Implementato

### **Auto-Discovery Completo** - Zero Configurazione Manuale

Il sistema Voodflow ora ha **auto-discovery completo** sia lato backend (PHP) che frontend (React):

#### 🔧 **Backend (PHP)** - `NodeRegistry.php`
- ✅ Scopre automaticamente tutti i nodi in `src/Nodes/`
- ✅ Registra automaticamente le classi che implementano `NodeInterface`
- ✅ Genera metadata per il frontend

#### ⚛️ **Frontend (React)** - `FlowEditor.jsx`
- ✅ Usa `import.meta.glob` di Vite per scoprire componenti React
- ✅ Carica automaticamente tutti i file `.jsx` in `src/Nodes/*/components/`
- ✅ Converte automaticamente `PascalCase` → `snake_case` (es: `SlackNode` → `slack_node`)
- ✅ Fallback component per nodi sconosciuti

---

## 📝 Come Creare un Nuovo Nodo

### 1. **Esegui il comando**
```bash
php artisan voodflow:make-node
```

### 2. **Rispondi alle domande**
- Nome: `EmailNode`
- Tipo: `action`
- Tier: `CORE`
- Descrizione: `Send emails via SMTP`

### 3. **FATTO! 🎉**

Il nodo è **immediatamente disponibile**:
- ✅ Nessuna compilazione necessaria
- ✅ Nessun file da spostare
- ✅ Nessuna registrazione manuale
- ✅ Appare automaticamente nell'editor

---

## 🔄 Flusso Automatico

```
1. Crei nodo con comando
   ↓
2. NodeRegistry (PHP) lo scopre automaticamente
   ↓
3. FlowEditor (React) lo carica automaticamente
   ↓
4. Nodo disponibile nell'UI
```

---

## 📁 Struttura Nodo Generato

```
src/Nodes/EmailNode/
├── EmailNode.php                    # Classe PHP (auto-discovered)
└── components/
    └── EmailNode.jsx                # Componente React (auto-discovered)
```

**Nessun altro file da toccare!**

---

## 🎨 Template Nodo Minimo

### **PHP** - Passthrough di default
```php
public function execute(ExecutionContext $context): ExecutionResult
{
    $inputData = $context->input;
    return ExecutionResult::success($inputData);
}
```

### **React** - UI Moderna
- ✅ Header colorato dinamico
- ✅ Title/Description editabili
- ✅ Espansione/Collasso
- ✅ Delete modal
- ✅ AddNodeButton
- ✅ Dark mode
- ✅ Salvataggio automatico

---

## 🔧 Personalizzazione

Dopo la creazione, puoi estendere il nodo:

### **1. Aggiungi logica PHP**
```php
public function execute(ExecutionContext $context): ExecutionResult
{
    $to = $context->getConfig('to');
    $subject = $context->getConfig('subject');
    
    Mail::to($to)->send(new MyEmail($subject));
    
    return ExecutionResult::success(['sent' => true]);
}
```

### **2. Aggiungi campi React**
Nel file `.jsx`, nella sezione `{/* TODO: Add your custom configuration fields here */}`:

```jsx
<div>
    <label>Email To</label>
    <input 
        value={emailTo} 
        onChange={(e) => handleEmailToChange(e.target.value)}
    />
</div>
```

---

## 🎯 Componenti Condivisi

I componenti `ConfirmModal` e `AddNodeButton` sono **condivisi** da:
```
resources/js/components/
```

Importati automaticamente con:
```javascript
import ConfirmModal from '../../../resources/js/components/ConfirmModal';
import AddNodeButton from '../../../resources/js/components/AddNodeButton';
```

**Non vengono copiati** - un'unica fonte di verità!

---

## 🚀 Vantaggi

✅ **Zero configurazione** - Crea e usa immediatamente  
✅ **Auto-discovery** - Backend e frontend sincronizzati  
✅ **Hot reload** - Modifiche visibili subito (dev mode)  
✅ **Type-safe** - Conversione automatica PascalCase ↔ snake_case  
✅ **Fallback robusto** - Nodi sconosciuti mostrano errore chiaro  
✅ **Scalabile** - Aggiungi infiniti nodi senza toccare il core  

---

## 🐛 Troubleshooting

### **Nodo non appare nell'UI**
1. Verifica che il file PHP implementi `NodeInterface`
2. Verifica che il componente React sia in `components/NomeNode.jsx`
3. Controlla la console browser per errori di import

### **"Unknown Node" / Quadrato bianco**
- Il componente React non è stato trovato
- Verifica il nome del file: deve essere `PascalCase.jsx`
- Esempio: `EmailNode.jsx` (non `emailNode.jsx` o `email-node.jsx`)

### **Modifiche non visibili**
- In dev mode, Vite fa hot reload automatico
- Se non funziona: `npm run dev` (riavvia il dev server)
- In produzione: `npm run build`

---

## 📊 Esempio Completo: SlackNode

### Creazione
```bash
php artisan voodflow:make-node SlackNode
```

### Risultato
- ✅ `src/Nodes/SlackNode/SlackNode.php` creato
- ✅ `src/Nodes/SlackNode/components/SlackNode.jsx` creato
- ✅ Auto-discovered come `slack_node`
- ✅ Disponibile immediatamente nell'editor

### Estensione
Aggiungi campi custom nel React component e logica nel PHP.

---

**Il sistema è pronto! Crea nodi senza limiti! 🎉**
