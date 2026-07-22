<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function download(Invoice $invoice)
    {
        // Check permissions: admin can download any, staff can download only if assigned to client
        if (auth()->check() && auth()->user()->role === 'staff') {
            if ($invoice->client && $invoice->client->assigned_manager_id !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $companyName = Setting::get('company_name', 'AIWA AGENCY');
        $companyAddress = Setting::get('company_address', "AIWA Agency HQ\nDubai, UAE");
        $companyTrn = Setting::get('company_trn', '100234567800003');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyTrn' => $companyTrn,
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
