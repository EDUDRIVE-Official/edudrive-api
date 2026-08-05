<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\ContentBlocks\AudioContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\DownloadContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\ImageContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\InteractiveContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\TextContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\VideoContentBlock;
use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Exceptions\ContentAccessibilityRequired;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\ExternalContentUrl;

function contentBlockId(string $value = '01981a64-8300-7b1d-b442-764ea7f915c0'): ContentBlockId
{
    return ContentBlockId::fromString($value);
}

function createContentBlockDirectly(string $type, int $position): ContentBlock
{
    return match ($type) {
        'text' => TextContentBlock::fromPayload(contentBlockId(), $position, [
            'markdown' => 'Contenido',
        ]),
        'image' => ImageContentBlock::fromPayload(contentBlockId(), $position, [
            'url' => 'https://cdn.example.test/senal.png',
            'alt' => 'Señal preventiva',
        ]),
        'video' => VideoContentBlock::fromPayload(contentBlockId(), $position, [
            'url' => 'https://media.example.test/video.mp4',
            'captions_url' => 'https://media.example.test/video.vtt',
            'transcript' => 'Transcripción',
        ]),
        'audio' => AudioContentBlock::fromPayload(contentBlockId(), $position, [
            'url' => 'https://media.example.test/audio.mp3',
            'transcript' => 'Transcripción',
        ]),
        'interactive' => InteractiveContentBlock::fromPayload(contentBlockId(), $position, [
            'url' => 'https://activities.example.test/cruce',
            'accessible_text' => 'Alternativa accesible',
        ]),
        'download' => DownloadContentBlock::fromPayload(contentBlockId(), $position, [
            'url' => 'https://docs.example.test/manual.pdf',
            'display_name' => 'Manual',
            'mime_type' => 'application/pdf',
        ]),
        default => throw new LogicException('Tipo de prueba no soportado.'),
    };
}

it('normaliza el identificador de bloque y compara por valor', function (): void {
    $id = ContentBlockId::fromString(' 01981A64-8300-7B1D-B442-764EA7F915C0 ');

    expect($id->value())
        ->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and((string) $id)
        ->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and($id->equals(contentBlockId()))
        ->toBeTrue();
});

it('rechaza identificadores de bloque que no son uuid', function (string $value): void {
    ContentBlockId::fromString($value);
})->with(['block-1', '', '01981a64-8300-0b1d-b442-764ea7f915c0'])
    ->throws(InvalidArgumentException::class);

it('limita los tipos de bloque al catalogo soportado', function (): void {
    expect(array_map(
        static fn (ContentBlockType $type): string => $type->value,
        ContentBlockType::cases(),
    ))->toBe(['text', 'image', 'video', 'audio', 'interactive', 'download']);
});

it('rechaza tipos de bloque desconocidos con el contrato publico', function (): void {
    try {
        ContentBlockFactory::create(contentBlockId(), 'document', 1, []);
    } catch (InvalidContentBlock $exception) {
        expect($exception->errorCode())->toBe('INVALID_CONTENT_BLOCK')
            ->and($exception->statusCode())->toBe(422);

        return;
    }

    $this->fail('Se esperaba InvalidContentBlock.');
});

it('rechaza posiciones de bloque no positivas', function (int $position): void {
    ContentBlockFactory::create(contentBlockId(), ContentBlockType::Text, $position, []);
})->with([0, -1])->throws(InvalidContentBlock::class);

it('define un contrato uniforme para todos los bloques', function (): void {
    expect((new ReflectionClass(ContentBlock::class))->getMethod('id')->getReturnType()?->getName())
        ->toBe(ContentBlockId::class)
        ->and((new ReflectionClass(ContentBlock::class))->getMethod('type')->getReturnType()?->getName())
        ->toBe(ContentBlockType::class)
        ->and((new ReflectionClass(ContentBlock::class))->getMethod('position')->getReturnType()?->getName())
        ->toBe('int')
        ->and((new ReflectionClass(ContentBlock::class))->getMethod('payload')->getReturnType()?->getName())
        ->toBe('array');
});

it('normaliza referencias externas https sin consultarlas', function (): void {
    $url = ExternalContentUrl::fromString('  https://cdn.example.test/recurso?lang=es  ');

    expect($url->value())->toBe('https://cdn.example.test/recurso?lang=es')
        ->and((string) $url)->toBe('https://cdn.example.test/recurso?lang=es');
});

it('rechaza referencias externas inseguras o malformadas', function (string $value): void {
    ExternalContentUrl::fromString($value);
})->with([
    'http://example.test/resource',
    'ftp://example.test/resource',
    'file:///etc/passwd',
    'javascript:alert(1)',
    'https://user:secret@example.test/resource',
    'https:///missing-host',
    'not-a-url',
    str_repeat('a', 2049),
])->throws(InvalidContentBlock::class);

it('crea un bloque de texto con payload canonico', function (): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'text', 1, [
        'markdown' => '  **Distancia segura**  ',
        'title' => '  Principio básico  ',
    ]);

    expect($block->id()->equals(contentBlockId()))->toBeTrue()
        ->and($block->type())->toBe(ContentBlockType::Text)
        ->and($block->position())->toBe(1)
        ->and($block->payload())->toBe([
            'markdown' => '**Distancia segura**',
            'title' => 'Principio básico',
        ]);
});

it('admite autolinks seguros de markdown sin tratarlos como html', function (): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'text', 1, [
        'markdown' => 'Consulta <https://seguridad-vial.example.test/guia>.',
    ]);

    expect($block->payload())->toBe([
        'markdown' => 'Consulta <https://seguridad-vial.example.test/guia>.',
    ]);
});

it('admite etiquetas literales dentro de contextos seguros de markdown', function (string $markdown): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'text', 1, [
        'markdown' => $markdown,
    ]);

    expect($block->payload())->toBe(['markdown' => $markdown]);
})->with([
    'inline code' => ['Ejecuta `<script>alert(1)</script>` como ejemplo.'],
    'fenced code' => ["```html\n<video controls></video>\n```"],
    'escaped tag' => ['El texto \\<video> no crea un elemento.'],
]);

it('admite html literal dentro de bloques de codigo commonmark anidados', function (string $markdown): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'text', 1, [
        'markdown' => $markdown,
    ]);

    expect($block->payload())->toBe(['markdown' => $markdown]);
})->with([
    'indented code' => ['    <script>alert(1)</script>'],
    'fence in quote' => ["> ```html\n> <video controls></video>\n> ```"],
    'fence in list' => ["- ```html\n  <video controls></video>\n  ```"],
    'fence in multi digit list' => ["10. item\n    ```html\n    <video></video>\n    ````"],
]);

it('preserva la sangria de codigo despues de lineas exteriores en blanco', function (): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'text', 1, [
        'markdown' => "\n    <script>ejemplo</script>",
    ]);

    expect($block->payload())->toBe([
        'markdown' => '    <script>ejemplo</script>',
    ]);
});

it('mantiene el rechazo de html crudo fuera de bloques de codigo', function (string $markdown): void {
    ContentBlockFactory::create(contentBlockId(), 'text', 1, [
        'markdown' => $markdown,
    ]);
})->with([
    'root' => ['<script>alert(1)</script>'],
    'quote' => ['> <script>alert(1)</script>'],
    'list' => ['- <script>alert(1)</script>'],
    'indented paragraph continuation' => ["Texto introductorio\n    <script>alert(1)</script>"],
])->throws(InvalidContentBlock::class);

it('rechaza comentarios y declaraciones html crudas en markdown', function (string $markdown): void {
    ContentBlockFactory::create(contentBlockId(), 'text', 1, [
        'markdown' => $markdown,
    ]);
})->with([
    'comment' => ['Contenido <!-- comentario --> visible.'],
    'doctype' => ['<!DOCTYPE html>'],
    'unclosed html block start' => ["<script\nalert(1)"],
])->throws(InvalidContentBlock::class);

it('rechaza texto vacio, html arbitrario o claves desconocidas', function (array $payload): void {
    ContentBlockFactory::create(contentBlockId(), 'text', 1, $payload);
})->with([
    [['markdown' => ' ']],
    [['markdown' => '<script>alert(1)</script>']],
    [['markdown' => 'Contenido', 'html' => '<p>Contenido</p>']],
    [['markdown' => ['contenido']]],
])->throws(InvalidContentBlock::class);

it('crea un bloque de imagen accesible con payload canonico', function (): void {
    $block = ContentBlockFactory::create(contentBlockId(), ContentBlockType::Image, 2, [
        'url' => ' https://cdn.example.test/senal.png ',
        'alt' => '  Señal de curva peligrosa  ',
        'caption' => '  Señal preventiva  ',
    ]);

    expect($block->payload())->toBe([
        'url' => 'https://cdn.example.test/senal.png',
        'alt' => 'Señal de curva peligrosa',
        'caption' => 'Señal preventiva',
    ]);
});

it('exige texto alternativo para imagenes', function (array $payload): void {
    ContentBlockFactory::create(contentBlockId(), 'image', 1, $payload);
})->with([
    [['url' => 'https://cdn.example.test/senal.png']],
    [['url' => 'https://cdn.example.test/senal.png', 'alt' => null]],
    [['url' => 'https://cdn.example.test/senal.png', 'alt' => ' ']],
])->throws(ContentAccessibilityRequired::class);

it('crea un bloque de video accesible con payload canonico', function (): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'video', 3, [
        'url' => 'https://media.example.test/video.mp4',
        'captions_url' => 'https://media.example.test/video.vtt',
        'transcript' => '  Transcripción completa.  ',
        'title' => '  Maniobra segura  ',
        'description' => '  Demostración práctica.  ',
    ]);

    expect($block->payload())->toBe([
        'url' => 'https://media.example.test/video.mp4',
        'captions_url' => 'https://media.example.test/video.vtt',
        'transcript' => 'Transcripción completa.',
        'title' => 'Maniobra segura',
        'description' => 'Demostración práctica.',
    ]);
});

it('exige subtitulos y transcripcion para videos', function (array $payload): void {
    ContentBlockFactory::create(contentBlockId(), 'video', 1, $payload);
})->with([
    [[
        'url' => 'https://media.example.test/video.mp4',
        'transcript' => 'Transcripción',
    ]],
    [[
        'url' => 'https://media.example.test/video.mp4',
        'captions_url' => null,
        'transcript' => 'Transcripción',
    ]],
    [[
        'url' => 'https://media.example.test/video.mp4',
        'captions_url' => 'https://media.example.test/video.vtt',
        'transcript' => ' ',
    ]],
])->throws(ContentAccessibilityRequired::class);

it('crea un bloque de audio accesible con payload canonico', function (): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'audio', 1, [
        'url' => 'https://media.example.test/audio.mp3',
        'transcript' => '  Indicaciones de conducción.  ',
        'title' => '  Audio guía  ',
        'description' => '  Ejercicio auditivo.  ',
    ]);

    expect($block->payload())->toBe([
        'url' => 'https://media.example.test/audio.mp3',
        'transcript' => 'Indicaciones de conducción.',
        'title' => 'Audio guía',
        'description' => 'Ejercicio auditivo.',
    ]);
});

it('exige transcripcion para audios', function (array $payload): void {
    ContentBlockFactory::create(contentBlockId(), 'audio', 1, $payload);
})->with([
    [['url' => 'https://media.example.test/audio.mp3']],
    [['url' => 'https://media.example.test/audio.mp3', 'transcript' => null]],
    [['url' => 'https://media.example.test/audio.mp3', 'transcript' => '']],
])->throws(ContentAccessibilityRequired::class);

it('crea interactivos con alternativa textual o enlazada', function (array $payload, array $expected): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'interactive', 1, $payload);

    expect($block->payload())->toBe($expected);
})->with([
    'textual' => [[
        'url' => 'https://activities.example.test/cruce',
        'accessible_text' => '  Descripción paso a paso del cruce.  ',
        'title' => '  Cruce interactivo  ',
    ], [
        'url' => 'https://activities.example.test/cruce',
        'accessible_text' => 'Descripción paso a paso del cruce.',
        'title' => 'Cruce interactivo',
    ]],
    'linked' => [[
        'url' => 'https://activities.example.test/cruce',
        'accessible_url' => ' https://accessible.example.test/cruce ',
        'description' => '  Versión accesible.  ',
    ], [
        'url' => 'https://activities.example.test/cruce',
        'accessible_url' => 'https://accessible.example.test/cruce',
        'description' => 'Versión accesible.',
    ]],
]);

it('exige una alternativa accesible para interactivos', function (array $payload): void {
    ContentBlockFactory::create(contentBlockId(), 'interactive', 1, $payload);
})->with([
    [['url' => 'https://activities.example.test/cruce']],
    [[
        'url' => 'https://activities.example.test/cruce',
        'accessible_text' => ' ',
        'accessible_url' => null,
    ]],
])->throws(ContentAccessibilityRequired::class);

it('crea una descarga accesible con payload canonico', function (): void {
    $block = ContentBlockFactory::create(contentBlockId(), 'download', 1, [
        'url' => 'https://docs.example.test/manual.pdf',
        'display_name' => '  Manual de conducción  ',
        'mime_type' => ' application/pdf ',
        'description' => '  Material de consulta.  ',
        'filename' => ' manual.pdf ',
        'size_bytes' => 1024,
    ]);

    expect($block->payload())->toBe([
        'url' => 'https://docs.example.test/manual.pdf',
        'display_name' => 'Manual de conducción',
        'mime_type' => 'application/pdf',
        'description' => 'Material de consulta.',
        'filename' => 'manual.pdf',
        'size_bytes' => 1024,
    ]);
});

it('exige nombre visible para descargas', function (array $payload): void {
    ContentBlockFactory::create(contentBlockId(), 'download', 1, $payload);
})->with([
    [['url' => 'https://docs.example.test/manual.pdf', 'mime_type' => 'application/pdf']],
    [[
        'url' => 'https://docs.example.test/manual.pdf',
        'display_name' => null,
        'mime_type' => 'application/pdf',
    ]],
    [[
        'url' => 'https://docs.example.test/manual.pdf',
        'display_name' => ' ',
        'mime_type' => 'application/pdf',
    ]],
])->throws(ContentAccessibilityRequired::class);

it('clasifica tipos invalidos de accesibilidad como bloque invalido', function (string $type, array $payload): void {
    try {
        ContentBlockFactory::create(contentBlockId(), $type, 1, $payload);
    } catch (InvalidContentBlock $exception) {
        expect($exception->errorCode())->toBe('INVALID_CONTENT_BLOCK')
            ->and($exception->statusCode())->toBe(422);

        return;
    }

    $this->fail('Se esperaba InvalidContentBlock.');
})->with([
    'image alt array' => ['image', [
        'url' => 'https://cdn.example.test/senal.png',
        'alt' => [],
    ]],
    'video captions array' => ['video', [
        'url' => 'https://media.example.test/video.mp4',
        'captions_url' => [],
        'transcript' => 'Transcripción',
    ]],
    'video transcript integer' => ['video', [
        'url' => 'https://media.example.test/video.mp4',
        'captions_url' => 'https://media.example.test/video.vtt',
        'transcript' => 42,
    ]],
    'audio transcript integer' => ['audio', [
        'url' => 'https://media.example.test/audio.mp3',
        'transcript' => 42,
    ]],
    'download display name array' => ['download', [
        'url' => 'https://docs.example.test/manual.pdf',
        'display_name' => [],
        'mime_type' => 'application/pdf',
    ]],
]);

it('rechaza posiciones no positivas al construir cualquier bloque directamente', function (string $type, int $position): void {
    createContentBlockDirectly($type, $position);
})->with([
    ['text', 0],
    ['image', -1],
    ['video', 0],
    ['audio', -1],
    ['interactive', 0],
    ['download', -1],
])->throws(InvalidContentBlock::class);

it('rechaza estructuras invalidas en bloques con recursos externos', function (string $type, array $payload): void {
    ContentBlockFactory::create(contentBlockId(), $type, 1, $payload);
})->with([
    'image without url' => ['image', ['alt' => 'Señal preventiva']],
    'image unknown key' => ['image', [
        'url' => 'https://cdn.example.test/senal.png',
        'alt' => 'Señal preventiva',
        'width' => 100,
    ]],
    'video invalid captions url' => ['video', [
        'url' => 'https://media.example.test/video.mp4',
        'captions_url' => 'http://media.example.test/video.vtt',
        'transcript' => 'Transcripción',
    ]],
    'audio invalid title type' => ['audio', [
        'url' => 'https://media.example.test/audio.mp3',
        'transcript' => 'Transcripción',
        'title' => ['Audio'],
    ]],
    'interactive invalid alternative url' => ['interactive', [
        'url' => 'https://activities.example.test/cruce',
        'accessible_url' => 'javascript:alert(1)',
    ]],
    'download empty mime type' => ['download', [
        'url' => 'https://docs.example.test/manual.pdf',
        'display_name' => 'Manual',
        'mime_type' => ' ',
    ]],
    'download non positive size' => ['download', [
        'url' => 'https://docs.example.test/manual.pdf',
        'display_name' => 'Manual',
        'mime_type' => 'application/pdf',
        'size_bytes' => 0,
    ]],
])->throws(InvalidContentBlock::class);
