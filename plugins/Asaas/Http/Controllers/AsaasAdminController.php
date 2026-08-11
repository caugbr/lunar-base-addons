<?php

namespace Plugins\Asaas\Http\Controllers;

use App\Http\Controllers\Controller;
use Plugins\Asaas\Models\AsaasInvoice;
use Plugins\Asaas\Services\AsaasApiClient;
use Illuminate\Http\Request;

class AsaasAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = AsaasInvoice::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('external_reference', 'like', "%{$request->search}%")
                  ->orWhere('payment_id', 'like', "%{$request->search}%");
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('asaas::admin.index', compact('invoices'));
    }

    /**
     * Consulta os dados do comprador AO VIVO no Asaas sem salvar no banco local
     */
    public function show(string $id, AsaasApiClient $apiClient)
    {
        $invoice = AsaasInvoice::findOrFail($id);
        $customerData = [];

        if ($invoice->customer_id) {
            $customerData = $apiClient->getCustomerDetails($invoice->customer_id);
        }

        return view('asaas::admin.show', compact('invoice', 'customerData'));
    }
}
