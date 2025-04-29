<?php

namespace Diji\Billing\Jobs;

use App\Services\Brevo;
use App\Services\ZipService;
use Diji\Billing\Models\CreditNote;
use Diji\Billing\Services\PdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBatchCreditNotesEmail implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected array $validIds;
    protected string $email;

    public function __construct(array $validIds, string $email)
    {
        $this->validIds = $validIds;
        $this->email = $email;
    }

    public function handle(): void
    {
        $pdfFiles = [];

        foreach($this->validIds as $id) { //TODO Faire une gestion d'erreur
            $credit_note = CreditNote::findOrFail($id)->load('items');

            $fileName = 'credit-note-' . str_replace("/", "-", $credit_note->identifier .'.pdf');
            $pdfString = PdfService::generateCreditNote($credit_note);

            $pdfFiles[$fileName] = $pdfString;
        }


        $zipPath = ZipService::createTempZip($pdfFiles);
        $zipContent = file_get_contents($zipPath);

        $mailService = new Brevo();

        $mailService->to($this->email)
            ->subject('Notes de crédit')
            ->content('Voici vos notes de crédit')
            ->attachments([
                [
                    'output' => $zipContent,
                    'filename' => 'credit_notes.zip',
                ],
            ])
            ->send();

        ZipService::deleteTempZip($zipPath);
    }
}
