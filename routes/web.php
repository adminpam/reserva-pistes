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

// --- Reservas ---
Route::middleware("auth")->group(function () {
    Route::post("/bookings", [App\Http\Controllers\BookingController::class, "store"])->name("bookings.store");
    Route::get("/my/bookings", [App\Http\Controllers\BookingController::class, "index"])->name("bookings.index");
    Route::patch("/bookings/{booking}/cancel", [App\Http\Controllers\BookingController::class, "cancel"])->name("bookings.cancel");
});

// --- Panel de administración ---
Route::middleware(["auth", "role:admin"])->prefix("admin")->name("admin.")->group(function () {
    Route::get("/clubs", [App\Http\Controllers\Admin\AdminClubController::class, "index"])->name("clubs.index");
    Route::patch("/clubs/{club}", [App\Http\Controllers\Admin\AdminClubController::class, "update"])->name("clubs.update");
    Route::delete("/clubs/{club}", [App\Http\Controllers\Admin\AdminClubController::class, "destroy"])->name("clubs.destroy");

    Route::get("/sports", [App\Http\Controllers\Admin\AdminSportController::class, "index"])->name("sports.index");
    Route::post("/sports", [App\Http\Controllers\Admin\AdminSportController::class, "store"])->name("sports.store");
    Route::patch("/sports/{sport}", [App\Http\Controllers\Admin\AdminSportController::class, "update"])->name("sports.update");
    Route::delete("/sports/{sport}", [App\Http\Controllers\Admin\AdminSportController::class, "destroy"])->name("sports.destroy");

    Route::get("/users", [App\Http\Controllers\Admin\AdminUserController::class, "index"])->name("users.index");
    Route::patch("/users/{user}", [App\Http\Controllers\Admin\AdminUserController::class, "update"])->name("users.update");
    Route::delete("/users/{user}", [App\Http\Controllers\Admin\AdminUserController::class, "destroy"])->name("users.destroy");
});
