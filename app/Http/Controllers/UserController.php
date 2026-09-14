<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Barangay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function delete(User $user)
    {
        // TODO: user cannot delete themselve, or others, the only one how has authority of delete is 
        // first user or admin

        
        if ($user->id === auth()->id()) {
            return back()->with('error', "You can't delete your own account.");
        }



        try {
            $user->deleteOrFail();
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Cannot delete this user because it has associated records');
        }

        return redirect()->route('users.index')->with('message', 'User deleted');
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(UserRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
                'barangay_id' => $request->validated('role') === 'barangayofficial'
                    ? $request->validated('barangay_id')
                    : null,
            ]);

            $user->assignRole($request->validated('role'));
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to create user.');
        }

        return redirect()->route('users.index')->with('message', 'User Created Successfully');
    }

    public function edit(User $user)
    {

        // TODO: user cannot edit themselve, or others, the only one how has authority of edit is 
        // first user or admin

        $user->load('roles:id,name');

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'barangay_id' => $user->barangay_id,
            ],
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(UserRequest $request, User $user)
    {
        try {
            $user->update([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'barangay_id' => $request->validated('role') === 'barangayofficial'
                    ? $request->validated('barangay_id')
                    : null,
                ...($request->validated('password') ? ['password' => Hash::make($request->validated('password'))] : []),
            ]);

            $user->syncRoles([$request->validated('role')]);
        } catch (\Throwable $th) {
            Log::error($th);
            return back()->with('error', 'Failed to update user.');
        }

        return redirect()->route('users.index')->with('message', 'User Updated Successfully');
    }

    public function index(Request $request)
    {
        $users = User::query()
            ->with(['barangay:id,name', 'roles:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "{$search}%")
                        ->orWhere('email', 'like', "{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->role($request->string('role')->trim()->toString());
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    protected function roleOptions(): array
    {
        return Role::query()
            ->pluck('name')
            ->map(fn($name) => [
                'value' => $name,
                'label' => $name === 'lgustaff' ? 'LGU Staff' : 'Barangay Official',
            ])
            ->all();
    }
}