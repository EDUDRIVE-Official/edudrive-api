<?php

declare(strict_types=1);

use Tests\TestCase;

it('muestra la página de demostración del design system', function (): void {
    /** @var TestCase $this */
    $response = $this->get('/design-system');

    $response->assertOk();
    $response->assertSeeText('Botones');
    // Task 2's follow-up (1c403f3) made the theme-toggle button's visible
    // label reactive: it's rendered via Alpine's x-text on an empty <span>,
    // so the literal string only exists inside the un-evaluated x-text
    // attribute in server-rendered HTML — it's never a text node, and
    // assertSeeText() strips tags (and their attributes) before matching.
    // The static, always-present, accessible description of that same
    // button is its aria-label, so we assert on that instead to verify
    // the toggle is present and accessible.
    $response->assertSee('Cambiar entre modo claro y oscuro');
});
