<?php

namespace Diji\Peppol\DTO;

class PeppolPayloadDTO
{
    public function __construct(
        public DocumentDTO $document,
        public SenderDTO $sender,
        public ReceiverDTO $receiver,
        public DeliveryDTO $delivery,
        public PaymentDTO $payment,
        /** @var InvoiceLineDTO[] */
        public array $lines,
        /** @var TaxDTO[] */
        public array $taxes,
        public MonetaryTotalDTO $totals
    ) {}
}
