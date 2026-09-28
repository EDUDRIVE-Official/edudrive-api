<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\ContentBlocks\ScenarioContentBlock;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;

$projectRoot = getenv('REVIEW_PROJECT_ROOT') ?: dirname(__DIR__);
require $projectRoot.'/vendor/autoload.php';
if ($override = getenv('REVIEW_SCENARIO_FILE')) {
    require $override;
}

$id = ContentBlockId::fromString('e67850f8-00c0-5583-b087-157e7f92813e');
$payload = [
    'title' => 'Lugar de espera', 'context' => 'Una van tapa la vista.',
    'prompt' => '¿Dónde esperás?', 'accessible_text' => 'Una van tapa la vista. ¿Dónde esperás?',
    'choices' => [
        ['id' => 'wait', 'label' => 'Acera', 'feedback' => 'Espacio protegido.', 'correct' => true],
        ['id' => 'go', 'label' => 'Calzada', 'feedback' => 'Trayectoria activa.', 'correct' => false],
    ],
];
$checks = 0;
$check = static function (bool $valid, string $message) use (&$checks): void {
    if (! $valid) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$check(ScenarioContentBlock::fromPayload($id, 1, $payload)->payload() === $payload, 'Legacy payload must round-trip unchanged.');
foreach ([true, false] as $value) {
    $candidate = [...$payload, 'stop_at_decision_point' => $value];
    $check(ScenarioContentBlock::fromPayload($id, 1, $candidate)->payload() === $candidate, 'Boolean must round-trip.');
}
foreach (['true', 'false', 0, 1, null, [], new stdClass] as $value) {
    try {
        ScenarioContentBlock::fromPayload($id, 1, [...$payload, 'stop_at_decision_point' => $value]);
    } catch (InvalidContentBlock) {
        $checks++;
        continue;
    }
    throw new RuntimeException('Invalid flag accepted.');
}
$draftPath = getenv('REVIEW_DRAFT_FILE') ?: $projectRoot.'/docs/product/BORRADOR-BLOQUES-CAMINO-PASAJERO-v1.json';
$draft = json_decode(file_get_contents($draftPath), true, 512, JSON_THROW_ON_ERROR);
foreach ($draft['lessons'] as $lesson) {
    foreach ($lesson['blocks'] as $index => $block) {
        if ($block['type'] === 'scenario') {
            $check(ScenarioContentBlock::fromPayload($id, $index + 1, $block['payload'])->payload() === $block['payload'], 'Draft payload rejected or altered.');
        }
    }
}
echo "PASS: {$checks} scenario-domain checks; legacy and draft payloads round-trip.\n";
