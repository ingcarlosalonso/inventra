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

    public function sales(Request $request): JsonResponse
    {
        return response()->json($this->sales->getData($request->all()));
    }

    public function salesExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->sales->getExportData($request->all()), $this->sales->getHeadings()),
            'reporte-ventas-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function products(Request $request): JsonResponse
    {
        return response()->json($this->products->getData($request->all()));
    }

    public function productsExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->products->getExportData($request->all()), $this->products->getHeadings()),
            'reporte-productos-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function payments(Request $request): JsonResponse
    {
        return response()->json($this->payments->getData($request->all()));
    }

    public function paymentsExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->payments->getExportData($request->all()), $this->payments->getHeadings()),
            'reporte-cobros-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function inventory(Request $request): JsonResponse
    {
        return response()->json($this->inventory->getData($request->all()));
    }

    public function inventoryExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->inventory->getExportData($request->all()), $this->inventory->getHeadings()),
            'reporte-inventario-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function dailyCashes(Request $request): JsonResponse
    {
        return response()->json($this->dailyCashes->getData($request->all()));
    }

    public function dailyCashesExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->dailyCashes->getExportData($request->all()), $this->dailyCashes->getHeadings()),
            'reporte-cajas-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json($this->orders->getData($request->all()));
    }

    public function ordersExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->orders->getExportData($request->all()), $this->orders->getHeadings()),
            'reporte-pedidos-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function clients(Request $request): JsonResponse
    {
        return response()->json($this->clients->getData($request->all()));
    }

    public function clientsExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->clients->getExportData($request->all()), $this->clients->getHeadings()),
            'reporte-clientes-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function purchases(Request $request): JsonResponse
    {
        return response()->json($this->purchases->getData($request->all()));
    }

    public function purchasesExport(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($this->purchases->getExportData($request->all()), $this->purchases->getHeadings()),
            'reporte-compras-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
