<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\Responses\MatchingResponse;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\OrderingResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Entities\Responses\TrueFalseResponse;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

it('construye una respuesta de seleccion unica con su opcion correcta', function (): void {
    $response = SingleChoiceResponse::fromArray([
        'type' => 'single_choice',
        'optionId' => '1',
    ]);

    expect($response->toArray())->toBe([
        'type' => 'single_choice',
        'optionId' => '1',
    ]);
});

it('rechaza una respuesta de seleccion unica sin opcion correcta', function (): void {
    expect(fn () => SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => '']))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza una respuesta de seleccion unica con claves desconocidas', function (): void {
    expect(fn () => SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => '1', 'extra' => true]))
        ->toThrow(InvalidQuestion::class);
});

it('construye una respuesta de opcion multiple', function (): void {
    $response = MultiSelectResponse::fromArray([
        'type' => 'multi_select',
        'optionIds' => ['1', '2'],
    ]);

    expect($response->toArray())->toBe([
        'type' => 'multi_select',
        'optionIds' => ['1', '2'],
    ]);
});

it('rechaza ids duplicados en respuestas de opcion multiple', function (): void {
    expect(fn () => MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['1', '1', '2']]))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza respuestas de opcion multiple sin opciones correctas', function (): void {
    expect(fn () => MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => []]))
        ->toThrow(InvalidQuestion::class);
});

it('construye una respuesta verdadero o falso', function (): void {
    $response = TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]);
    expect($response->toArray())->toBe(['type' => 'true_false', 'correct' => true]);
});

it('rechaza una respuesta verdadero o falso con correct no booleano', function (): void {
    expect(fn () => TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => 'si']))
        ->toThrow(InvalidQuestion::class);
});

it('construye una respuesta de emparejamiento', function (): void {
    $response = MatchingResponse::fromArray([
        'type' => 'matching',
        'pairs' => [
            ['leftId' => 'l1', 'rightId' => 'r1'],
            ['leftId' => 'l2', 'rightId' => 'r2'],
        ],
    ]);

    expect($response->toArray())->toBe([
        'type' => 'matching',
        'pairs' => [
            ['leftId' => 'l1', 'rightId' => 'r1'],
            ['leftId' => 'l2', 'rightId' => 'r2'],
        ],
    ]);
});

it('rechaza pares de asociacion con lado izquierdo duplicado', function (): void {
    expect(fn () => MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
        ['leftId' => 'l1', 'rightId' => 'r1'],
        ['leftId' => 'l1', 'rightId' => 'r2'],
    ]]))->toThrow(InvalidQuestion::class);
});

it('rechaza pares de asociacion con lado derecho duplicado', function (): void {
    expect(fn () => MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
        ['leftId' => 'l1', 'rightId' => 'r1'],
        ['leftId' => 'l2', 'rightId' => 'r1'],
    ]]))->toThrow(InvalidQuestion::class);
});

it('rechaza pares de asociacion sin lado izquierdo', function (): void {
    expect(fn () => MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
        ['leftId' => '', 'rightId' => 'r1'],
    ]]))->toThrow(InvalidQuestion::class);
});

it('construye una respuesta de ordenamiento', function (): void {
    $response = OrderingResponse::fromArray([
        'type' => 'ordering',
        'itemIds' => ['a', 'b', 'c'],
    ]);

    expect($response->toArray())->toBe([
        'type' => 'ordering',
        'itemIds' => ['a', 'b', 'c'],
    ]);
});

it('rechaza respuestas de ordenamiento con menos de dos items o ids duplicados', function (): void {
    expect(fn () => OrderingResponse::fromArray(['type' => 'ordering', 'itemIds' => ['a']]))
        ->toThrow(InvalidQuestion::class);
    expect(fn () => OrderingResponse::fromArray(['type' => 'ordering', 'itemIds' => ['a', 'a']]))
        ->toThrow(InvalidQuestion::class);
});

it('rechaza respuestas con tipo incorrecto', function (): void {
    expect(fn () => SingleChoiceResponse::fromArray(['type' => 'multi_select', 'optionId' => '1']))
        ->toThrow(InvalidQuestion::class);
});