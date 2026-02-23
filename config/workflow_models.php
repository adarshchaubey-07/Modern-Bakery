<?php
return [
    'order'           => App\Models\Agent_Transaction\OrderHeader::class,
    'Add_Chiller' => App\Models\AddChiller::class,
    'Approved_CRF_Request' => App\Models\ChillerRequest::class,
    'IRO_Header' => App\Models\IROHeader::class,
    'Pricing_Header' => App\Models\PricingHeader::class,
    'Agent_Delivery_Headers' => App\Models\Agent_Transaction\AgentDeliveryHeaders::class,
    'Caps_Collection_Header' => App\Models\Agent_Transaction\CapsCollectionHeader::class,
    'Exchange_Header' => App\Models\Agent_Transaction\ExchangeHeader::class,
    'Invoice_Header' => App\Models\Agent_Transaction\InvoiceHeader::class,
    'Load_Header' => App\Models\Agent_Transaction\LoadHeader::class,
    'Return_Header' => App\Models\Agent_Transaction\ReturnHeader::class,
    'Unload_Header' => App\Models\Agent_Transaction\UnloadHeader::class,
    'Ht_Caps_Header' => App\Models\Hariss_Transaction\Web\HtCapsHeader::class,
    'HT_Delivery_Header' => App\Models\Hariss_Transaction\Web\HTDeliveryHeader::class,
    'HT_Invoice_Header' => App\Models\Hariss_Transaction\Web\HTInvoiceHeader::class,
    'HT_Order_Header' => App\Models\Hariss_Transaction\Web\HTOrderHeader::class,
    'Ht_Return_Header' => App\Models\Hariss_Transaction\Web\HtReturnHeader::class,
    'Po_Order_Header' => App\Models\Hariss_Transaction\Web\PoOrderHeader::class,
    'Compiled_Claim' => App\Models\Claim_Management\Web\CompiledClaim::class,
    'Petit_Claim' => App\Models\Claim_Management\Web\PetitClaim::class,//Distributor_Advance_Payment
    'Distributor_Advance_Payment' => App\Models\Agent_Transaction\AdvancePayment::class,//Distributor_Advance_Payment
    'Distributor_Stock_Transfer'=> App\Models\StockTransferHeader::class,

];
 