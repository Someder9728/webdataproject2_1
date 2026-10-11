<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class InvoicePageController extends Controller
{
    public function index(): View
    {
        return view('invoices.index');
    }

    public function history(): View
    {
        return view('invoices.history');
    }

    public function show(int $invoice): View
    {
        return view('invoices.show', ['invoiceId' => $invoice]);
    }
}
