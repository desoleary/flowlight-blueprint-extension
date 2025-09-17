<?php

declare(strict_types=1);

use Flowlight\Generator\Utils\LangUtils;

describe('LangUtils::toClassName', function () {
    it('returns short class name from FQCN string of an existing class', function () {
        $fqcn = DateTime::class;
        $result = LangUtils::toClassName($fqcn);
        expect($result)->toBe('DateTime');
    });

    it('returns short class name from an object instance', function () {
        $obj = new SplFileInfo(__FILE__);
        $result = LangUtils::toClassName($obj);
        expect($result)->toBe('SplFileInfo');
    });

    it('returns short class name from an interface FQCN', function () {
        $fqcn = Iterator::class;
        $result = LangUtils::toClassName($fqcn);
        expect($result)->toBe('Iterator');
    });

    it('falls back to string splitting for nonexistent classes', function () {
        $fqcn = 'Nonexistent\\Foo\\BarBaz';
        $result = LangUtils::toClassName($fqcn);
        expect($result)->toBe('BarBaz');
    });

    it('returns input itself if no namespace and class does not exist', function () {
        $fqcn = 'PlainString';
        $result = LangUtils::toClassName($fqcn);
        expect($result)->toBe('PlainString');
    });

    it('returns empty string for empty input string', function () {
        $fqcn = '';
        $result = LangUtils::toClassName($fqcn);
        expect($result)->toBe('');
    });

    it('handles object of anonymous class', function () {
        $anon = new class {};
        $result = LangUtils::toClassName($anon);

        // Anonymous classes use a compiler-generated name
        expect($result)->toStartWith('class@anonymous');
    });

    it('handles string of anonymous class (via ::class)', function () {
        $fqcn = (new class {})::class;
        $result = LangUtils::toClassName($fqcn);

        expect($result)->toStartWith('class@anonymous');
    });
});
