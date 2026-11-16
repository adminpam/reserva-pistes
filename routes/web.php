<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// ÐšÐ°Ñ€Ñ‚Ð° Ñ€Ð¾ÑƒÑ‚Ð¾Ð² Ð¿Ñ€Ð¾ÐµÐºÑ‚Ð°. ÐŸÐ¾ÐºÐ° Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ð°ÑƒÑ‚ÐµÐ½Ñ‚Ð¸Ñ„Ð¸ÐºÐ°Ñ†Ð¸Ñ:
// Ð¾ÑÑ‚Ð°Ð»ÑŒÐ½Ñ‹Ðµ Ñ€Ð¾ÑƒÑ‚Ñ‹ Ð´Ð¾Ð±Ð°Ð²Ð»ÑÑŽÑ‚ÑÑ Ð²Ð¼ÐµÑÑ‚Ðµ ÑÐ¾ ÑÐ²Ð¾Ð¸Ð¼Ð¸ ÐºÐ¾Ð½Ñ‚Ñ€Ð¾Ð»Ð»ÐµÑ€Ð°Ð¼Ð¸.


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

// --- Panel de administraciÃ³n ---
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

// --- Mapa de clubes ---
Route::get("/", [App\Http\Controllers\MapController::class, "index"])->name("home");
Route::get("/api/clubs", [App\Http\Controllers\MapController::class, "clubs"])->name("api.clubs");
Route::get("/clubs/{club}", [App\Http\Controllers\ClubController::class, "show"])->name("clubs.show");

// --- Perfil del jugador ---
Route::middleware("auth")->group(function () {
    Route::get("/profile", [App\Http\Controllers\ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [App\Http\Controllers\ProfileController::class, "update"])->name("profile.update");
});

// --- Panel del gestor de club ---
Route::middleware(["auth", "role:manager"])->prefix("manager")->name("manager.")->group(function () {
    Route::get("/courts", [App\Http\Controllers\Manager\ManagerCourtController::class, "index"])->name("courts.index");
    Route::get("/courts/create", [App\Http\Controllers\Manager\ManagerCourtController::class, "create"])->name("courts.create");
    Route::post("/courts", [App\Http\Controllers\Manager\ManagerCourtController::class, "store"])->name("courts.store");
    Route::get("/courts/{court}/edit", [App\Http\Controllers\Manager\ManagerCourtController::class, "edit"])->name("courts.edit");
    Route::patch("/courts/{court}", [App\Http\Controllers\Manager\ManagerCourtController::class, "update"])->name("courts.update");
    Route::delete("/courts/{court}", [App\Http\Controllers\Manager\ManagerCourtController::class, "destroy"])->name("courts.destroy");
    Route::get("/bookings", [App\Http\Controllers\Manager\ManagerBookingController::class, "index"])->name("bookings.index");
});
