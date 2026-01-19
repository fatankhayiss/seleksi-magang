<?php declare(strict_types=1);

namespace App\Services;

use App\Jobs\ImportUsersJob;
use App\Models\ImportProgress;
use Illuminate\Http\UploadedFile;

class ImportUserService
{
    public function handle(UploadedFile $file): int
    {
        $path = $file->store('imports');

        $progress = ImportProgress::create([
            'status' => 'processing',
        ]);

        ImportUsersJob::dispatch($path, $progress->id);

        return $progress->id;
    }
}
