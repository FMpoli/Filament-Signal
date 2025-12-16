# 📦 Voodflow Node Marketplace System

## Overview

Sistema completo per creare, distribuire e installare nodi Voodflow con supporto licensing integrato.

## 🎯 Workflow Completo

### **Per Developer (Creatore di Nodi)**

1. **Crea il nodo**
   ```bash
   php artisan voodflow:make-node EmailNode
   ```

2. **Sviluppa la logica**
   - Implementa `execute()` in `EmailNode.php`
   - Personalizza UI in `components/EmailNode.jsx`

3. **Crea manifest.json**
   ```bash
   php artisan voodflow:package-node EmailNode
   ```
   - Prima volta: genera template manifest.json
   - Modifica il manifest con i tuoi dati
   - Aggiungi info licensing se nodo a pagamento

4. **Packagea per distribuzione**
   ```bash
   php artisan voodflow:package-node EmailNode
   ```
   - Compila il bundle JavaScript
   - Crea ZIP pronto per distribuzione
   - Output: `storage/app/voodflow-packages/email-node-1.0.0.zip`

5. **Distribuisci**
   - **Opzione A**: Pubblica su Packagist (Composer)
   - **Opzione B**: Vendi su Anystack
   - **Opzione C**: Distribu isci ZIP manualmente

### **Per Utente Finale**

#### **Metodo 1: Composer (Tecnico)**
```bash
composer require acme/email-node
```
Il nodo si auto-registra e appare nell'editor.

#### **Metodo 2: Upload ZIP (UI)**
1. Scarica `email-node.zip` dal marketplace
2. Voodflow → Settings → Nodes → "Upload Node"
3. Carica lo ZIP
4. Il nodo appare immediatamente

#### **Metodo 3: 1-Click Install (Futuro)**
1. Marketplace UI → "Install EmailNode"
2. Sistema installa automaticamente via Composer
3. Nodo disponibile istantaneamente

---

## 🔐 Sistema Licensing

### **Nodi Gratuiti (FREE/CORE)**
- Nessuna licenza richiesta
- Funzionano immediatamente dopo installazione

### **Nodi a Pagamento (PRO/PREMIUM)**

1. **Setup Developer (Anystack)**
   - Crea prodotto su Anystack
   - Ottieni `product_id`
   - Configura webhook validation

2. **Manifest del Nodo**
   ```json
   {
     "tier": "PREMIUM",
     "license": {
       "type": "commercial",
       "requires_activation": true,
       "anystack_product_id": "prod_abc123",
       "validation_url": "https://api.anystack.sh/v1/licenses/validate"
     }
   }
   ```

3. **Utente Acquista**
   - Compra su Anystack
   - Riceve license key

4. **Utente Attiva**
   - Installa il nodo
   - Apre configurazione nodo
   - Inserisce license key
   - Sistema valida con Anystack
   - Nodo si attiva

5. **Validazione**
   - Cache 24h per performance
   - Rivalidazione automatica
   - Supporto offline grace period

---

## 📁 Struttura Package

```
email-node-1.0.0.zip
├── manifest.json              # Metadata + licensing info
├── EmailNode.php              # Backend logic
├── components/
│   └── EmailNode.jsx          # React component source
├── dist/
│   └── email-node.js          # Pre-compiled bundle
└── README.md                  # Documentation
```

---

## 🛠️ API Reference

### **LicenseService**

```php
use Voodflow\Voodflow\Services\LicenseService;

$license = app(LicenseService::class);

// Validate license
$result = $license->validate('email-node', 'LICENSE-KEY-HERE');

// Store license
$license->storeLicense('email-node', 'LICENSE-KEY-HERE');

// Check if licensed
$isLicensed = $license->isLicensed('email-node');

// Get stored license
$key = $license->getLicense('email-node');
```

### **Commands**

```bash
# Create new node
php artisan voodflow:make-node NodeName

# Package node for distribution
php artisan voodflow:package-node NodeName

# Install node from ZIP (future)
php artisan voodflow:install-node /path/to/node.zip
```

---

## 🔄 Prossimi Step

### **Fase 1: Foundation** ✅
- [x] Manifest specification
- [x] Package command
- [x] License service
- [x] Auto-discovery system

### **Fase 2: Installation** (Next)
- [ ] Upload ZIP handler
- [ ] Node installer service
- [ ] UI per upload nodi
- [ ] Validazione package

### **Fase 3: Marketplace** (Future)
- [ ] Marketplace UI
- [ ] 1-click install
- [ ] Node browser/search
- [ ] Rating & reviews

### **Fase 4: Advanced** (Future)
- [ ] Auto-updates
- [ ] Dependency management
- [ ] Sandbox/security
- [ ] Credentials system (n8n-style)

---

## 💡 Note Importanti

1. **Bundle Pre-compilato**: Il developer deve includere il bundle JS compilato nel package
2. **Zero Build per Utente**: L'utente finale NON deve mai fare `npm run build`
3. **Licensing Opzionale**: Solo nodi PRO/PREMIUM richiedono licenza
4. **Anystack Integration**: Sistema pronto per integrazione con Anystack
5. **Backward Compatible**: Nodi esistenti continuano a funzionare

---

## 🎉 Vantaggi

- ✅ **Developer**: Facile creare e distribuire nodi
- ✅ **Utente**: Installazione zero-config
- ✅ **Monetizzazione**: Sistema licensing integrato
- ✅ **Scalabile**: Supporta migliaia di nodi
- ✅ **Sicuro**: Validazione licenze server-side
- ✅ **Flessibile**: Supporta free, paid, subscription

---

**Il sistema è pronto per essere esteso con UI e installazione automatica!** 🚀
