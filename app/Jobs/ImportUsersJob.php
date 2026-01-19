<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportProgress;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ImportUsersJob implements ShouldQueue
{
    use Queueable;

    protected string $path;
    protected int $importId;
    public int $timeout = 3600; // allow long-running job (1 hour)
    public int $tries = 1; // avoid duplicate reprocessing on timeout

    public function __construct(string $path, int $importId)
    {
        $this->path = $path;
        $this->importId = $importId;
    }

    public function handle(): void
    {
        // Increase CLI memory limit if allowed (configurable via .env)
        @ini_set('memory_limit', env('IMPORT_MEMORY_LIMIT', '1024M'));

        // Disable query log to reduce memory usage for long-running job
        DB::disableQueryLog();

        $import = ImportProgress::find($this->importId);
        if ($import === null) {
            return;
        }

        $file = storage_path('app/' . $this->path);

        // Hitung total baris terlebih dulu (streaming, tanpa load seluruh file)
        $totalRows = 0;
        $counter = @fopen($file, 'r');
        if ($counter !== false) {
            // lewati header
            fgets($counter);
            while (($line = fgets($counter)) !== false) {
                if (trim($line) !== '') {
                    $totalRows++;
                }
            }
            fclose($counter);
        }

        $import->update([
            'total_rows' => $totalRows,
            'processed_rows' => 0,
        ]);

        $handle = fopen($file, 'r');
        if ($handle === false) {
            return;
        }

        // ambil header
        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            return;
        }
        // normalisasi header: trim, lowercase, hapus BOM
        $header = array_map(function ($h) {
            $s = is_string($h) ? $h : (string) $h;
            $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // remove UTF-8 BOM
            return strtolower(trim($s));
        }, $headerRow);

        // pastikan kolom minimal tersedia
        if (!in_array('name', $header, true) || !in_array('email', $header, true)) {
            fclose($handle);
            $import->update(['status' => 'failed']);
            return;
        }

        $batch = [];
        // Allow tuning via .env; use smaller default batch to reduce SQL payload size
        $batchSize = (int) env('IMPORT_BATCH_SIZE', config('database.default') === 'sqlite' ? 500 : 1000);
        $processed = 0;
        $progressStep = (int) env('IMPORT_PROGRESS_STEP', 10000); // update progress setiap ~10k baris
        $hashedPassword = bcrypt('password'); // hash once, reuse

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }

            $data = @array_combine($header, $row);
            if ($data === false) {
                continue;
            }
            if (!isset($data['name']) || !isset($data['email'])) {
                continue;
            }

            $batch[] = [
                'name'       => trim($data['name']),
                'email'      => trim($data['email']),
                'address'    => $data['address'] ?? null,
                'password'   => $hashedPassword,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                try {
                    // Use query builder to reduce Eloquent overhead
                    DB::table('users')->upsert(
                        $batch,
                        ['email'],
                        ['name', 'address', 'password', 'updated_at']
                    );
                } catch (\Throwable $e) {
                    // Mark failed, log, and exit gracefully
                    $import->update(['status' => 'failed']);
                    logger()->error('Import upsert failed', ['message' => $e->getMessage()]);
                    fclose($handle);
                    return;
                }

                $processed += count($batch);
                $batch = [];
                gc_collect_cycles();

                if ($processed % $progressStep === 0) {
                    $import->update([
                        'processed_rows' => $processed,
                    ]);
                    logger()->info('Import progress', ['processed' => $processed]);
                }
            }
        }

        fclose($handle);

        if (!empty($batch)) {
            try {
                DB::table('users')->upsert(
                    $batch,
                    ['email'],
                    ['name', 'address', 'password', 'updated_at']
                );
            } catch (\Throwable $e) {
                $import->update(['status' => 'failed']);
                logger()->error('Import upsert failed (final batch)', ['message' => $e->getMessage()]);
                // still finalize processed count
            }

            $processed += count($batch);
        }

        $import->update([
            'processed_rows' => $processed,
            'status' => 'done',
        ]);
    }
}
