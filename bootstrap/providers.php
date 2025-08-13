<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
    Laravel\Passport\PassportServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    \Diji\Billing\BillingServiceProvider::class,
    \Diji\Contact\ContactServiceProvider::class,
    \Diji\Team\TeamServiceProvider::class,
    \Diji\Module\ModuleServiceProvider::class,
    \Diji\Peppol\PeppolServiceProvider::class,
    \Diji\Expense\ExpenseServiceProvider::class,
    \Diji\Task\TaskServiceProvider::class,
    \Diji\Project\ProjectServiceProvider::class,
    \Diji\History\HistoryServiceProvider::class,
    \Diji\Module\ModuleServiceProvider::class,
];
