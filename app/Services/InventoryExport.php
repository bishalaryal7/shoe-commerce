<?php

namespace App\Services;

use App\Models\Variant;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryExport
{
    public function download(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['SKU', 'Product', 'Category', 'Color', 'EU size', 'Stock', 'Retail price', 'Wholesale price', 'Active']);

            Variant::query()
                ->with('product:id,name,category,retail_price,wholesale_price,active')
                ->orderBy('product_id')
                ->orderBy('size')
                ->chunk(500, function ($variants) use ($output) {
                    foreach ($variants as $variant) {
                        fputcsv($output, [
                            $this->spreadsheetSafe($variant->sku),
                            $this->spreadsheetSafe($variant->product->name),
                            $this->spreadsheetSafe($variant->product->category),
                            $this->spreadsheetSafe($variant->color),
                            $this->spreadsheetSafe($variant->size),
                            $variant->stock,
                            $variant->product->retail_price,
                            $variant->product->wholesale_price,
                            $variant->product->active ? 'yes' : 'no',
                        ]);
                    }
                });

            fclose($output);
        }, 'eve-shoes-inventory-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^[\t\r\n ]*[=+\-@]/', $value) ? "'{$value}" : $value;
    }
}
