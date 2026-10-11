<?php

namespace App\Services;

use App\Models\Variant;
use App\Repositories\InventoryRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryImport
{
    public function __construct(private InventoryRepository $inventory) {}

    public function import(UploadedFile $file, int $actor): int
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['inventory_file' => __('The inventory file could not be read.')]);
        }

        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            if (! $headers) {
                throw ValidationException::withMessages(['inventory_file' => __('The CSV is missing SKU or Stock columns.')]);
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $skuColumn = array_search('SKU', $headers, true);
            $stockColumn = array_search('Stock', $headers, true);
            if ($skuColumn === false || $stockColumn === false) {
                throw ValidationException::withMessages(['inventory_file' => __('The CSV is missing SKU or Stock columns.')]);
            }

            $rows = [];
            $seen = [];
            $errors = [];
            $rowNumber = 1;
            while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $rowNumber++;
                if ($cells === [null] || count(array_filter($cells, fn ($cell) => trim((string) $cell) !== '')) === 0) {
                    continue;
                }
                if (count($rows) >= 1000) {
                    $errors[] = __('The CSV can update at most :count sizes at a time.', ['count' => 1000]);
                    break;
                }

                $sku = trim((string) ($cells[$skuColumn] ?? ''));
                $stock = trim((string) ($cells[$stockColumn] ?? ''));
                if ($sku === '' || strlen($sku) > 100 || ! ctype_digit($stock) || (int) $stock > 1000000) {
                    $errors[] = __('CSV row :row has an invalid SKU or stock value.', ['row' => $rowNumber]);

                    continue;
                }
                if (isset($seen[$sku])) {
                    $errors[] = __('CSV row :row contains a duplicate SKU.', ['row' => $rowNumber]);

                    continue;
                }
                $seen[$sku] = true;
                $rows[] = ['row' => $rowNumber, 'sku' => $sku, 'stock' => (int) $stock];
            }

            if (! $rows && ! $errors) {
                $errors[] = __('The CSV has no inventory rows to import.');
            }
            $variants = Variant::whereIn('sku', array_column($rows, 'sku'))->get()->keyBy('sku');
            foreach ($rows as $row) {
                if (! $variants->has($row['sku'])) {
                    $errors[] = __('CSV row :row references an unknown SKU.', ['row' => $row['row']]);
                }
            }
            if ($errors) {
                throw ValidationException::withMessages(['inventory_file' => $errors]);
            }

            DB::transaction(function () use ($rows, $variants, $actor) {
                foreach ($rows as $row) {
                    $this->inventory->adjust($variants[$row['sku']]->id, $row['stock'], $actor);
                }
            });

            return count($rows);
        } finally {
            fclose($handle);
        }
    }
}
