<?php

use Illuminate\Support\Facades\Route;
use Modules\People\Http\Controllers\PeopleController;
use Modules\People\Http\Controllers\StudentsController;
use Modules\People\Http\Controllers\TeachersController;
use Modules\People\Http\Controllers\AdminsController;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/people', [PeopleController::class, 'index'])->name('people.index');
    Route::post('/people', [PeopleController::class, 'store'])->name('people.store');
    Route::put('/people/{person}', [PeopleController::class, 'update'])->name('people.update');
    Route::delete('/people/{person}', [PeopleController::class, 'destroy'])->name('people.destroy');

    Route::get('/students', [StudentsController::class, 'index'])->name('students.index');
    Route::post('/students', [StudentsController::class, 'store'])->name('students.store');
    Route::put('/students/{student}', [StudentsController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentsController::class, 'destroy'])->name('students.destroy');

    Route::get('/teachers', [TeachersController::class, 'index'])->name('teachers.index');
    Route::post('/teachers', [TeachersController::class, 'store'])->name('teachers.store');
    Route::put('/teachers/{teacher}', [TeachersController::class, 'update'])->name('teachers.update');
    Route::delete('/teachers/{teacher}', [TeachersController::class, 'destroy'])->name('teachers.destroy');

    Route::get('/admins', [AdminsController::class, 'index'])->name('admins.index');
    Route::post('/admins', [AdminsController::class, 'store'])->name('admins.store');
    Route::put('/admins/{admin}', [AdminsController::class, 'update'])->name('admins.update');
    Route::delete('/admins/{admin}', [AdminsController::class, 'destroy'])->name('admins.destroy');

});