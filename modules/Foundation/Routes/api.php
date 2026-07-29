Route::post('/test-validation', function (): array {
    request()->validate([
        'email' => ['required', 'email'],
    ]);

    return [
        'success' => true,
    ];
})->name('foundation.test-validation');