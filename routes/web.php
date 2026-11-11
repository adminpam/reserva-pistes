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

// --- Partidos abiertos ---
Route::middleware("auth")->group(function () {
    Route::get("/matches", [App\Http\Controllers\MatchController::class, "index"])->name("matches.index");
    Route::get("/my/matches", [App\Http\Controllers\MatchController::class, "mine"])->name("matches.mine");
    Route::post("/matches/{booking}/join", [App\Http\Controllers\MatchController::class, "join"])->name("matches.join");
    Route::delete("/matches/{booking}/leave", [App\Http\Controllers\MatchController::class, "leave"])->name("matches.leave");
});
