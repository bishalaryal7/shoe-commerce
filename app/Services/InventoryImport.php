<?php

namespace App\Services;

use App\Models\Variant;
use App\Repositories\InventoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryImport
{
    private const MAX_ROWS = 1000;

    private const MAX_SKU_LENGTH = 100;

    private const MAX_STOCK = 1000000;

    public function __construct(private InventoryRepository $inventory) {}

    public function import(UploadedFile $file, int $actor): int
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['inventory_file' => __('The inventory file could not be read.')]);
        }

        try {
            $rows = $this->readRows($handle);
            $variants = $this->resolveVariants($rows);
            $this->applyRows($rows, $variants, $actor);

            return count($rows);
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle
     * @return list<array{row: int, sku: string, stock: int}>
     */
    private function readRows($handle): array
    {
        $headers = fgetcsv($handle, 0, ',', '"', '');
        if (! $headers) {
            $this->throwMissingColumns();
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $skuColumn = array_search('SKU', $headers, true);
        $stockColumn = array_search('Stock', $headers, true);
        if ($skuColumn === false || $stockColumn === false) {
            $this->throwMissingColumns();
        }

        return $this->parseRows($handle, $skuColumn, $stockColumn);
    }

    /** @param resource $handle
     * @return list<array{row: int, sku: string, stock: int}>
     */
    private function parseRows($handle, int $skuColumn, int $stockColumn): array
    {
        $rows = [];
        $seen = [];
        $errors = [];
        $rowNumber = 1;

        while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rowNumber++;
            if ($this->isBlankRow($cells)) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                $errors[] = __('The CSV can update at most :count sizes at a time.', ['count' => self::MAX_ROWS]);
                break;
            }

            $sku = trim((string) ($cells[$skuColumn] ?? ''));
            $stock = trim((string) ($cells[$stockColumn] ?? ''));
            if ($sku === '' || strlen($sku) > self::MAX_SKU_LENGTH || ! ctype_digit($stock) || (int) $stock > self::MAX_STOCK) {
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
        if ($errors) {
            throw ValidationException::withMessages(['inventory_file' => $errors]);
        }

        return $rows;
    }

    private function isBlankRow(array $cells): bool
    {
        return $cells === [null]
            || count(array_filter($cells, fn ($cell) => trim((string) $cell) !== '')) === 0;
    }

    /** @param list<array{row: int, sku: string, stock: int}> $rows */
    private function resolveVariants(array $rows): Collection
    {
        $variants = Variant::whereIn('sku', array_column($rows, 'sku'))
            ->get()
            ->keyBy('sku');
        $errors = [];

        foreach ($rows as $row) {
            if (! $variants->has($row['sku'])) {
                $errors[] = __('CSV row :row references an unknown SKU.', ['row' => $row['row']]);
            }
        }
        if ($errors) {
            throw ValidationException::withMessages(['inventory_file' => $errors]);
        }

        return $variants;
    }

    /** @param list<array{row: int, sku: string, stock: int}> $rows */
    private function applyRows(array $rows, Collection $variants, int $actor): void
    {
        DB::transaction(function () use ($rows, $variants, $actor) {
            foreach ($rows as $row) {
                $this->inventory->adjust($variants[$row['sku']]->id, $row['stock'], $actor);
            }
        });
    }

    private function throwMissingColumns(): never
    {
        throw ValidationException::withMessages([
            'inventory_file' => __('The CSV is missing SKU or Stock columns.'),
        ]);
    }
}
