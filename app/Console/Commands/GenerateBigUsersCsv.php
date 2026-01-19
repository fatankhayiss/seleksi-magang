<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateBigUsersCsv extends Command
{
    /** @var string */
    protected $signature = 'users:generate-csv {--count=1000000} {--output=big_users.csv} {--headers=1}';
    /** @var string */
    protected $description = 'Generate a large CSV file of users for import testing.';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $output = (string) $this->option('output');
        $writeHeaders = (bool) ((int) $this->option('headers'));

        // Resolve path: absolute stays, otherwise relative to base_path
        if (!$this->isAbsolutePath($output)) {
            $output = base_path($output);
        }

        $this->info("Writing to: {$output}");

        $fh = @fopen($output, 'w');
        if ($fh === false) {
            $this->error('Failed to open output file for writing.');
            return self::FAILURE;
        }

        // Optional headers
        if ($writeHeaders) {
            fputcsv($fh, ['name', 'email', 'address']);
        }

        $start = microtime(true);
        $batchSize = 10000; // buffer writes to reduce syscalls
        $buffer = '';

        for ($i = 1; $i <= $count; $i++) {
            // Simple deterministic data for speed and uniqueness
            $name = 'User ' . $i;
            $email = 'user' . $i . '@example.com';
            $address = 'Street ' . $i . ', City';

            $buffer .= $this->csvLine([$name, $email, $address]);

            if ($i % $batchSize === 0) {
                fwrite($fh, $buffer);
                $buffer = '';
                if ($i % 100000 === 0) {
                    $elapsed = microtime(true) - $start;
                    $this->line("Progress: {$i}/{$count} rows (" . number_format($elapsed, 2) . "s)");
                }
            }
        }

        if ($buffer !== '') {
            fwrite($fh, $buffer);
        }

        fclose($fh);

        $elapsed = microtime(true) - $start;
        $this->info('Done. Rows: ' . $count . ' in ' . number_format($elapsed, 2) . 's');

        return self::SUCCESS;
    }

    private function isAbsolutePath(string $path): bool
    {
        // Windows absolute path (e.g., C:\...)
        if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return true;
        }
        // Unix absolute path
        return str_starts_with($path, DIRECTORY_SEPARATOR);
    }

    private function csvLine(array $fields): string
    {
        // Lightweight CSV line builder (avoids per-line fputcsv overhead)
        $escaped = [];
        foreach ($fields as $field) {
            $f = (string) $field;
            if (str_contains($f, '"') || str_contains($f, ',') || str_contains($f, "\n") || str_contains($f, "\r")) {
                $f = '"' . str_replace('"', '""', $f) . '"';
            }
            $escaped[] = $f;
        }
        return implode(',', $escaped) . "\n";
    }
}
