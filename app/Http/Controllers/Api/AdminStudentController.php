<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminStudentController extends Controller
{
    public function index()
    {
        return response()->json(User::where('role', 'student')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ], [
            'email.unique' => 'Ky email është tashmë i regjistruar.',
            'password.min' => 'Fjalëkalimi duhet të ketë të paktën 8 karaktere.'
        ]);

        $student = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
        ]);

        return response()->json($student, 201);
    }

    public function show($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);
        return response()->json($student);
    }

    public function update(Request $request, $id)
    {
        $student = User::where('role', 'student')->findOrFail($id);
        
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
        ]);

        $student->update($data);
        return response()->json($student);
    }

    public function destroy($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);
        $student->delete();
        return response()->json(['message' => 'Studenti u fshi me sukses.']);
    }
}
