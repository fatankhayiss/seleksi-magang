<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->select(['name', 'email', 'address'])
            ->orderByDesc('id')
            ->paginate(50);

        return view('users', ['users' => $users]);
    }
}
