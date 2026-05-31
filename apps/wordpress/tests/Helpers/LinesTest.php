<?php

use Wexoe\Core\Helpers\Lines;

/**
 * Spec för Lines — multi-line text ↔ array. WP-fri ren helper.
 */

it('splittar multi-line text till trimmade icke-tomma rader', function () {
    expect(Lines::to_array("rad 1\nrad 2\n\nrad 3"))
        ->toBe(['rad 1', 'rad 2', 'rad 3']);
});

it('hanterar alla radslut (\\n, \\r\\n, \\r)', function () {
    expect(Lines::to_array("a\r\nb\rc\nd"))->toBe(['a', 'b', 'c', 'd']);
});

it('trimmar whitespace och filtrerar bort blanka rader', function () {
    expect(Lines::to_array("  x  \n   \n\ty\t"))->toBe(['x', 'y']);
});

it('tom/ogiltig input → tom array', function () {
    expect(Lines::to_array(''))->toBe([]);
    expect(Lines::to_array(null))->toBe([]);
});

it('first/last returnerar rätt rad eller null', function () {
    expect(Lines::first("a\nb\nc"))->toBe('a');
    expect(Lines::last("a\nb\nc"))->toBe('c');
    expect(Lines::first(''))->toBeNull();
});

it('count_non_empty räknar bara icke-tomma rader', function () {
    expect(Lines::count_non_empty("a\n\n\nb\n"))->toBe(2);
});

it('from_array joinar med \\n och filtrerar tomma', function () {
    expect(Lines::from_array(['a', '', ' b ', null]))->toBe("a\nb");
});

it('to_array ∘ from_array är stabil (round-trip)', function () {
    $arr = ['Första', 'Andra', 'Tredje'];
    expect(Lines::to_array(Lines::from_array($arr)))->toBe($arr);
});
