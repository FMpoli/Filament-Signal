# Esempi di utilizzo di Filament Signal

## Come un plugin può esporre i suoi eventi

Un plugin può esporre i suoi eventi in modo che appaiano nella select del form dei trigger. Questo permette agli utenti di selezionare facilmente gli eventi disponibili senza dover conoscere il nome completo della classe.

### Esempio: Plugin LabOps espone eventi per i prestiti

Nel service provider del plugin LabOps (`FilamentLabOpsServiceProvider`), nel metodo `boot()`:

```php
use Base33\FilamentSignal\FilamentSignal;

public function boot(): void
{
    // ... altro codice ...

    // Registra gli eventi del plugin LabOps
    FilamentSignal::registerEvent(
        eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanCreated::class,
        name: 'Equipment Loan Created',
        description: 'Triggered when a new equipment loan is created',
        group: 'LabOps'
    );

    FilamentSignal::registerEvent(
        eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanApproved::class,
        name: 'Equipment Loan Approved',
        description: 'Triggered when an equipment loan is approved/opened',
        group: 'LabOps'
    );

    FilamentSignal::registerEvent(
        eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanReturned::class,
        name: 'Equipment Loan Returned',
        description: 'Triggered when an equipment loan is returned',
        group: 'LabOps'
    );

    FilamentSignal::registerEvent(
        eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanStatusChanged::class,
        name: 'Equipment Loan Status Changed',
        description: 'Triggered when the status of an equipment loan changes',
        group: 'LabOps'
    );

    FilamentSignal::registerEvent(
        eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanDeleted::class,
        name: 'Equipment Loan Deleted',
        description: 'Triggered when an equipment loan is deleted',
        group: 'LabOps'
    );

    FilamentSignal::registerEvent(
        eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanDueDateUpdated::class,
        name: 'Equipment Loan Due Date Updated',
        description: 'Triggered when the due date of an equipment loan is updated',
        group: 'LabOps'
    );

    FilamentSignal::registerEvent(
        eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanReminded::class,
        name: 'Equipment Loan Reminded',
        description: 'Triggered when a reminder is sent for an equipment loan',
        group: 'LabOps'
    );

    FilamentSignal::registerEvent(
        eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanOverdueDetected::class,
        name: 'Equipment Loan Overdue Detected',
        description: 'Triggered when an overdue equipment loan is detected',
        group: 'LabOps'
    );
}
```

### Come funziona

1. **Registrazione**: Quando il plugin viene caricato, chiama `FilamentSignal::registerEvent()` per ogni evento che vuole esporre.

2. **Visualizzazione**: Gli eventi registrati appaiono nella select del form dei trigger, organizzati per gruppo (se specificato).

3. **Auto-discovery**: Il sistema registra automaticamente gli eventi quando vengono usati nei trigger, quindi anche eventi non registrati esplicitamente possono essere selezionati (usando il nome della classe come fallback).

4. **Priorità**: Gli eventi registrati dai plugin hanno priorità e mostrano il nome personalizzato. Gli eventi dal database o dal config mostrano solo il nome della classe.

### Vantaggi

- **UX migliore**: Gli utenti vedono nomi leggibili invece di nomi di classi completi
- **Organizzazione**: Gli eventi possono essere raggruppati per plugin/modulo
- **Documentazione**: Le descrizioni aiutano a capire quando viene scatenato l'evento
- **Estensibilità**: Qualsiasi plugin può esporre i suoi eventi senza modificare il codice di Filament Signal

