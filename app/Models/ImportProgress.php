<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportProgress extends Model
{
    /** @var string */
    protected $table = 'import_progress';

    /** @var array<int,string> */
    protected $fillable = [
        'status',
        'total_rows',
        'processed_rows',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
    ];
}
