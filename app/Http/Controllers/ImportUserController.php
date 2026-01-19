<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ImportUserRequest;
use App\Services\ImportUserService;
use App\Models\ImportProgress;
use Illuminate\Http\JsonResponse;

class ImportUserController extends Controller
{
    public function store(
        ImportUserRequest $request,
        ImportUserService $service
    ): JsonResponse {
        $importId = $service->handle($request->file('file'));

        return response()->json([
            'status' => 'processing',
            'import_id' => $importId,
        ]);
    }

    public function status(int $id): JsonResponse
    {
        $progress = ImportProgress::findOrFail($id);

        return response()->json([
            'id' => $progress->id,
            'status' => $progress->status,
            'processed_rows' => (int) $progress->processed_rows,
            'total_rows' => (int) $progress->total_rows,
            'percent' => $progress->total_rows > 0
                ? round(($progress->processed_rows / max(1, $progress->total_rows)) * 100, 2)
                : 0.0,
        ]);
    }
}
