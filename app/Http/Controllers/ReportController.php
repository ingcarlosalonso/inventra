<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Reports\ClientsReport;
use App\Reports\DailyCashesReport;
use App\Reports\InventoryReport;
use App\Reports\OrdersReport;
use App\Reports\PaymentsReport;
use App\Reports\ProductsReport;
use App\Reports\PurchasesReport;
use App\Reports\SalesReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly SalesReport $sales,
        private readonly ProductsReport $products,
        private readonly PaymentsReport $payments,
        private readonly InventoryReport $inventory,
        private readonly DailyCashesReport $dailyCashes,
        private readonly OrdersReport $orders,
        private readonly ClientsReport $clients,
        private readonly PurchasesReport $purchases,
    ) {}

    private function validateDateFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
    }

    public function sales(Request $request): JsonResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['client_id', 'sale_state_id', 'point_of_sale_id']));

        return response()->json($this->sales->getData($filters));
    }

    public function salesExport(Request $request): BinaryFileResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['client_id', 'sale_state_id', 'point_of_sale_id']));

        return Excel::download(
            new ReportExport($this->sales->getExportData($filters), $this->sales->getHeadings()),
            'reporte-ventas-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function products(Request $request): JsonResponse
    {
        return response()->json($this->products->getData($this->validateDateFilters($request)));
    }

    public function productsExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->products->getExportData($this->validateDateFilters($request)), $this->products->getHeadings()),
            'reporte-productos-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function payments(Request $request): JsonResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['payment_method_id']));

        return response()->json($this->payments->getData($filters));
    }

    public function paymentsExport(Request $request): BinaryFileResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['payment_method_id']));

        return Excel::download(
            new ReportExport($this->payments->getExportData($filters), $this->payments->getHeadings()),
            'reporte-cobros-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function inventory(Request $request): JsonResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['search', 'product_type_id']));

        return response()->json($this->inventory->getData($filters));
    }

    public function inventoryExport(Request $request): BinaryFileResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['search', 'product_type_id']));

        return Excel::download(
            new ReportExport($this->inventory->getExportData($filters), $this->inventory->getHeadings()),
            'reporte-inventario-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function dailyCashes(Request $request): JsonResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['point_of_sale_id']));

        return response()->json($this->dailyCashes->getData($filters));
    }

    public function dailyCashesExport(Request $request): BinaryFileResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['point_of_sale_id']));

        return Excel::download(
            new ReportExport($this->dailyCashes->getExportData($filters), $this->dailyCashes->getHeadings()),
            'reporte-cajas-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function orders(Request $request): JsonResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['order_state_id', 'courier_id']));

        return response()->json($this->orders->getData($filters));
    }

    public function ordersExport(Request $request): BinaryFileResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['order_state_id', 'courier_id']));

        return Excel::download(
            new ReportExport($this->orders->getExportData($filters), $this->orders->getHeadings()),
            'reporte-pedidos-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function clients(Request $request): JsonResponse
    {
        return response()->json($this->clients->getData($this->validateDateFilters($request)));
    }

    public function clientsExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->clients->getExportData($this->validateDateFilters($request)), $this->clients->getHeadings()),
            'reporte-clientes-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function purchases(Request $request): JsonResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['supplier_id']));

        return response()->json($this->purchases->getData($filters));
    }

    public function purchasesExport(Request $request): BinaryFileResponse
    {
        $filters = array_merge($this->validateDateFilters($request), $request->only(['supplier_id']));

        return Excel::download(
            new ReportExport($this->purchases->getExportData($filters), $this->purchases->getHeadings()),
            'reporte-compras-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
