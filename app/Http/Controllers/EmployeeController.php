<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request): Response
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $employees = User::with(['department:id,name', 'subDepartment:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        $departments = Department::active()->get(['id', 'name']);
        $subDepartments = SubDepartment::active()->with('department:id,name')->get(['id', 'name', 'department_id']);

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'subDepartments' => $subDepartments,
        ]);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(Request $request): Response
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $departments = Department::active()->with('subDepartments')->get();

        return Inertia::render('employees/Form', [
            'employee' => null,
            'departments' => $departments,
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        Log::info('Storing new employee', $request->all());
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'unique:users,employee_id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'sub_department_id' => ['nullable', 'exists:sub_departments,id'],
            'designation' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['user', 'manager', 'admin'])],
            'weekend_days' => ['required', 'array', 'min:1'],
            'weekend_days.*' => ['string', Rule::in(['friday', 'saturday', 'sunday'])],
        ]);

        $user = User::create([
            'employee_id' => $validated['employee_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'],
            'sub_department_id' => $validated['sub_department_id'],
            'designation' => $validated['designation'],
            'role' => $validated['role'],
            'weekend_days' => $validated['weekend_days'],
            'email_verified_at' => now(),
        ]);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully!');
    }

    /**
     * Show the form for editing the employee.
     */
    public function edit(Request $request, User $employee): Response
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $employee->load(['department', 'subDepartment']);
        $departments = Department::active()->with('subDepartments')->get();

        return Inertia::render('employees/Form', [
            'employee' => $employee,
            'departments' => $departments,
        ]);
    }

    /**
     * Update the employee.
     */
    public function update(Request $request, User $employee): RedirectResponse
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'string', Rule::unique('users', 'employee_id')->ignore($employee->id)],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'sub_department_id' => ['nullable', 'exists:sub_departments,id'],
            'designation' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['user', 'manager', 'admin'])],
            'weekend_days' => ['required', 'array', 'min:1'],
            'weekend_days.*' => ['string', Rule::in(['friday', 'saturday', 'sunday'])],
        ]);

        $employee->update([
            'employee_id' => $validated['employee_id'],
            'name' => $validated['name'],
            'department_id' => $validated['department_id'],
            'sub_department_id' => $validated['sub_department_id'],
            'designation' => $validated['designation'],
            'role' => $validated['role'],
            'weekend_days' => $validated['weekend_days'],
        ]);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully!');
    }

    /**
     * Remove the employee.
     */
    public function destroy(Request $request, User $employee): RedirectResponse
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Prevent admin from deleting themselves
        if ($request->user()->id === $employee->id) {
            return redirect()->route('employees.index')->with('error', 'You cannot delete yourself!');
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully!');
    }
}
