<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Rutas del proyecto: sesiones, roles, clubes y reservas.

Route::middleware("guest")->group(function () {
    Route::get("/register", [AuthController::class, "showRegister"])->name("register");
    Route::post("/register", [AuthController::class, "register"]);
    Route::get("/login", [AuthController::class, "showLogin"])->name("login");
    Route::post("/login", [AuthController::class, "login"]);
});

Route::post("/logout", [AuthController::class, "logout"])->middleware(["auth", "auth.session"])->name("logout");

// --- Partidos abiertos ---
Route::middleware(["auth", "auth.session"])->group(function () {
    Route::get("/matches", [App\Http\Controllers\MatchController::class, "index"])->name("matches.index");
    Route::get("/my/matches", [App\Http\Controllers\MatchController::class, "mine"])->name("matches.mine");
    Route::post("/matches/{booking}/join", [App\Http\Controllers\MatchController::class, "join"])->name("matches.join");
    Route::delete("/matches/{booking}/leave", [App\Http\Controllers\MatchController::class, "leave"])->name("matches.leave");
});

// --- Reservas ---
Route::middleware(["auth", "auth.session"])->group(function () {
    Route::post("/bookings", [App\Http\Controllers\BookingController::class, "store"])->name("bookings.store");
    Route::get("/my/bookings", [App\Http\Controllers\BookingController::class, "index"])->name("bookings.index");
    Route::patch("/bookings/{booking}/cancel", [App\Http\Controllers\BookingController::class, "cancel"])->name("bookings.cancel");
});

// --- Panel de administración ---
Route::middleware(["auth", "auth.session", "role:admin"])->prefix("admin")->name("admin.")->group(function () {
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
Route::get("/clubs/{club}/availability", [App\Http\Controllers\ClubController::class, "availability"])->name("clubs.availability");

// --- Perfil del jugador ---
Route::middleware(["auth", "auth.session"])->group(function () {
    Route::get("/profile", [App\Http\Controllers\ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [App\Http\Controllers\ProfileController::class, "update"])->name("profile.update");
    Route::patch('/profile/password', [App\Http\Controllers\PasswordController::class, 'update'])->name('profile.password');
    Route::post('/profile/avatar', [App\Http\Controllers\AvatarController::class, 'store'])->name('profile.avatar.store');
    Route::delete('/profile/avatar', [App\Http\Controllers\AvatarController::class, 'destroy'])->name('profile.avatar.destroy');
    Route::get('/users/{user}/avatar', [App\Http\Controllers\AvatarController::class, 'show'])->whereNumber('user')->name('users.avatar');
});

// --- Panel del gestor de club ---
Route::middleware(["auth", "auth.session", "role:manager"])->prefix("manager")->name("manager.")->group(function () {
    Route::get("/courts", [App\Http\Controllers\Manager\ManagerCourtController::class, "index"])->name("courts.index");
    Route::get("/courts/create", [App\Http\Controllers\Manager\ManagerCourtController::class, "create"])->name("courts.create");
    Route::post("/courts", [App\Http\Controllers\Manager\ManagerCourtController::class, "store"])->name("courts.store");
    Route::get("/courts/{court}/edit", [App\Http\Controllers\Manager\ManagerCourtController::class, "edit"])->name("courts.edit");
    Route::patch("/courts/{court}", [App\Http\Controllers\Manager\ManagerCourtController::class, "update"])->name("courts.update");
    Route::delete("/courts/{court}", [App\Http\Controllers\Manager\ManagerCourtController::class, "destroy"])->name("courts.destroy");
    Route::get("/bookings", [App\Http\Controllers\Manager\ManagerBookingController::class, "index"])->name("bookings.index");
});

// Comprobación pública para la entrada estática: no inicia sesiones ni crea cookies.
Route::get('/brand/ready', \App\Http\Controllers\BrandReadyController::class)
    ->withoutMiddleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ])
    ->name('brand.ready');
