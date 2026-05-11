<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ParticipantController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        $participants = User::whereIn('role', ['basic', 'premium'])->latest()->get();
        
        if ($request->ajax()) {
            return $this->successResponse($participants);
        }

        return view('admin.participants.index', compact('participants'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'role' => 'required|in:basic,premium',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) return $this->validationResponse($validator->errors());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse($user, 'Peserta berhasil ditambahkan', 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return $this->successResponse($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'role' => 'required|in:basic,premium',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) return $this->validationResponse($validator->errors());

        $data = $request->only(['name', 'email', 'phone', 'address', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return $this->successResponse($user, 'Data peserta berhasil diperbarui');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return $this->successResponse(null, 'Peserta berhasil dihapus');
    }
}
