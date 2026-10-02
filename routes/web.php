<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/rechten/product-aanpassen', 'permission-demo')
        ->middleware('permission:product aanpassen')->name('permission-demo');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Ook opslaan, wijzigen en verwijderen vallen onder dezelfde adminbeveiliging.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.permissions.index'))->name('index');
    Route::resource('permissions', PermissionController::class)->except('show');
    Route::resource('roles', RoleController::class)->except('show');
    Route::get('role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
    Route::get('role-permissions/create', [RolePermissionController::class, 'create'])->name('role-permissions.create');
    Route::post('role-permissions', [RolePermissionController::class, 'store'])->name('role-permissions.store');
    Route::get('role-permissions/{role}/{permission}/edit', [RolePermissionController::class, 'edit'])->whereNumber(['role', 'permission'])->name('role-permissions.edit');
    Route::put('role-permissions/{role}/{permission}', [RolePermissionController::class, 'update'])->whereNumber(['role', 'permission'])->name('role-permissions.update');
    Route::delete('role-permissions/{role}/{permission}', [RolePermissionController::class, 'destroy'])->whereNumber(['role', 'permission'])->name('role-permissions.destroy');
});

require __DIR__.'/auth.php';
