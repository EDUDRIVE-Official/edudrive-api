<?php

declare(strict_types=1);

use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;

it('preserves optional teaching supplements through domain round trips', function (string $type): void {
    $draft = json_decode(file_get_contents(resource_path('curriculum/editorial/camino-pasajero-v1.json')), true, 512, JSON_THROW_ON_ERROR);
    $payload = $draft['lessons'][0]['blocks'][$type === 'text' ? 0 : 1]['payload'];
    $id = ContentBlockId::fromString('dd79e60f-8cf6-4004-b6d9-d09fc4105b51');
    expect(ContentBlockFactory::create($id, $type, 1, $payload)->payload())->toEqual($payload);
    $payload['supplement'] = "## Una pista\n\nObservá desde la acera.";
    $block = ContentBlockFactory::create($id, $type, 1, $payload);
    expect($block->payload())->toEqual($payload)->and($block->id()->value())->toBe($id->value());
    expect(view('courses.blocks.supplement', ['block' => ['payload' => $block->payload()]])->render())
        ->toContain('<details', 'Una pista', 'Observá desde la acera.');
})->with(['text', 'scenario']);

it('rejects unsafe or invalid supplements', function (mixed $supplement): void {
    ContentBlockFactory::create(ContentBlockId::fromString('dd79e60f-8cf6-4004-b6d9-d09fc4105b51'), 'text', 1, ['markdown' => 'Tema', 'supplement' => $supplement]);
})->with(['html' => '<script>alert(1)</script>', 'link' => '[Abrir](javascript:alert(1))', 'empty' => '', 'array' => [[]], 'null' => [null]])->throws(InvalidContentBlock::class);

it('does not add support UI to existing blocks without supplements', function (): void {
    expect(trim(view('courses.blocks.supplement', ['block' => ['payload' => ['markdown' => 'Original']]])->render()))->toBe('');
});
