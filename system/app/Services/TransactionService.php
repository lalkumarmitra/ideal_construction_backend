<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Arr;

class TransactionService
{
    private $request;
    private $query;

    public function __construct(Request $request, Builder $query) {
        $this->request = $request;
        $this->query = $query;
    }
    public function export() {
        $this->applyFilters($this->query, $this->request);
        $selectedColumns = $this->request->input('columns', $this->defaultColumns());
        $sortedColumns = $this->sortColumns($selectedColumns);
        $transactions = $this->query->get();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $this->setHeaders($sheet, $sortedColumns);
        $this->populateRows($sheet, $transactions, $sortedColumns);
        
        return $this->downloadExcel($spreadsheet);
    }

    public function applyFilters() {
        $filters = [
            'product_id' => 'product_ids',
            'loading_point_id' => 'loading_point_ids',
            'unloading_point_id' => 'unloading_point_ids',
            'loading_vehicle_id' => 'loading_vehicle_ids',
            'unloading_vehicle_id' => 'unloading_vehicle_ids',
            'loading_quantity' => ['min_loading_quantity', 'max_loading_quantity'],
            'unloading_quantity' => ['min_unloading_quantity', 'max_unloading_quantity'],
            'loading_rate' => ['min_loading_rate', 'max_loading_rate'],
            'unloading_rate' => ['min_unloading_rate', 'max_unloading_rate'],
        ];
 
        foreach ($filters as $column => $keys) {
            if (is_array($keys)) {
                if ($min = $this->request->input($keys[0])) $this->query->where($column, '>=', $min);
                if ($max = $this->request->input($keys[1])) $this->query->where($column, '<=', $max);
            } else {
                if ($ids = $this->request->input($keys)) $this->query->whereIn($column, $ids);
            }
        }

        if ($keyword = $this->request->input('search')) $this->query->searchKeyword($keyword);
        if ($vehicleKeyword = $this->request->input('vehicle')) $this->query->searchVehicle($vehicleKeyword);
        if ($txnType = $this->request->input('txn_type')) $this->query->filterByTxnType($txnType);
        if ($this->request->boolean('is_sold')) $this->query->isSold();
    }

    private function setHeaders($sheet, array $columns) {
        foreach ($columns as $index => $column) {
            $cellCoordinate = Coordinate::stringFromColumnIndex($index + 1) . '1';
            $sheet->setCellValue($cellCoordinate, ucwords(str_replace('_', ' ', $column)));
        }
    }
    private function populateRows($sheet, $transactions, array $columns) {
        $totals = [
            'loading_quantity'  => 0,
            'unloading_quantity'=> 0,
            'loading_price'     => 0,
            'unloading_price'   => 0,
            'transport_expense' => 0
        ];
        
        foreach ($transactions as $rowIndex => $transaction) {
            foreach ($columns as $colIndex => $column) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 2);
                $value = $this->getColumnValue($transaction, $column);
                $sheet->setCellValue($cellCoordinate, $value);      
                if (array_key_exists($column, $totals)) {
                    $totals[$column] += (float)$value;
                }
            }
        }
        
        // Add totals row at the bottom
        $this->addTotalsRow($sheet, $columns, $totals, count($transactions), count($transactions) + 2);
    }

    private function addTotalsRow($sheet, array $columns, array $totals, int $totalRows, int $rowNumber) {
        $totalRowIndex = $rowNumber;
        $sheet->setCellValue(Coordinate::stringFromColumnIndex(1) . $totalRowIndex, "TOTAL");
        $sheet->getStyle('A' . $totalRowIndex . ':' . Coordinate::stringFromColumnIndex(count($columns)) . $totalRowIndex)
            ->getFont()->setBold(true);
        foreach ($columns as $colIndex => $column) {
            if (array_key_exists($column, $totals)) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($colIndex + 1) . $totalRowIndex;
                $sheet->setCellValue($cellCoordinate, $totals[$column]);
            }
        }
        $totalRowsCell = Coordinate::stringFromColumnIndex(2) . $totalRowIndex;
        $sheet->setCellValue($totalRowsCell, "Total Rows: " . $totalRows);
    }

    private function getColumnValue($transaction, $column) {
        $mapping = [
            'loading_date' => fn($t) => Carbon::parse($t->loading_date)->format('d-m-Y'),
            'unloading_date' => fn($t) => $t->unloading_date ? Carbon::parse($t->unloading_date)->format('d-m-Y') : '',
            'loading_point' => 'loadingPoint.name',
            'unloading_point' => 'unloadingPoint.name',
            'loading_vehicle' => 'loadingVehicle.type',
            'unloading_vehicle' => 'unloadingVehicle.type',
            'product' => 'product.name',
            'vehicle'=> 'loadingVehicle.number',
            'loading_vehicle_number' => 'loadingVehicle.number',
            'unloading_vehicle_number' => 'unloadingVehicle.number',
            'loading_price' => fn($t) => $t->loading_rate * $t->loading_quantity,
            'unloading_price' => fn($t) => $t->unloading_rate * $t->unloading_quantity,
            'status' => fn($t) => $t->is_sold ? 'Sold' : 'In Transit',
            'loading_driver' => 'loadingDriver.name',
            'unloading_driver' => 'unLoadingDriver.name',
        ];
        return is_callable($mapping[$column] ?? null) ? $mapping[$column]($transaction) : Arr::get($transaction, $mapping[$column] ?? $column, '');
    }

    private function downloadExcel($spreadsheet) {
        $filename = 'transactions_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $exportDir = 'public/exports';
        Storage::makeDirectory($exportDir);
        $tempPath = Storage::path($exportDir . '/' . $filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        return [
            'path'=>$tempPath,
            'filename'=>$filename
        ];
    }
    
    private function sortColumns(array $columns): array {
        $orderMapping = $this->getColumnOrderMapping();
        usort($columns, function ($a, $b) use ($orderMapping) {
            $orderA = $orderMapping[$a] ?? 100; // Default order if not defined
            $orderB = $orderMapping[$b] ?? 100;
            return $orderA <=> $orderB;
        });
        return $columns;
    }

    public function defaultColumns() {
        return [
            'loading_point', 'unloading_point', 'loading_vehicle', 'unloading_vehicle', 'product',
            'loading_rate', 'unloading_rate', 'loading_quantity', 'unloading_quantity',
            'loading_price', 'unloading_price', 'loading_vehicle_number', 'unloading_vehicle_number',
            'loading_date', 'unloading_date', 'transaction_id', 'do_number', 'challan_number','transport_expense','loading_driver', 'unloading_driver','status'
        ];
    }
    private function getColumnOrderMapping(): array {
        return [
            'loading_date'          => 1,
            'unloading_date'        => 2,
            'product'               => 3,
            'loading_point'         => 4,
            'unloading_point'       => 5,
            'loading_vehicle'       => 6,
            'unloading_vehicle'     => 7,
            'loading_rate'          => 8,
            'unloading_rate'        => 9,
            'loading_quantity'      => 10,
            'unloading_quantity'    => 11,
            'loading_price'         => 12,
            'unloading_price'       => 13,
            'loading_vehicle_number'=> 14,
            'unloading_vehicle_number'=> 15,
            'transaction_id'        => 16,
            'do_number'             => 17,
            'challan_number'        => 18,
            'transport_expense'     => 19,
            'loading_driver'        => 20, 
            'unloading_driver'      => 21,
            'status'                => 22,
        ];
    }

}
