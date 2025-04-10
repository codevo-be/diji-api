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
        /** @var TaxDTO[] */
        public array $taxes,
        /** @var InvoiceLineDTO[] */
        public array $lines,
        public MonetaryTotalDTO $totals,
        public string $buyerReference
    ) {}
}
