<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Карта роутов проекта. Пока только аутентификация:
// остальные роуты добавляются вместе со своими контроллерами.

Route::get("/", fn () => redirect()->route("login"))->name("home");

Route::middleware("guest")->group(function () {
    Route::get("/register", [AuthController::class, "showRegister"])->name("register");
    Route::post("/register", [AuthController::class, "register"]);
    Route::get("/login", [AuthController::class, "showLogin"])->name("login");
    Route::post("/login", [AuthController::class, "login"]);
});

Route::post("/logout", [AuthController::class, "logout"])->middleware("auth")->name("logout");
