<?php

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Koramit\SiMEDPortalAPI\DTOs\PortalResultDto;
use Koramit\SiMEDPortalAPI\Facades\SiMEDPortalUtil;

/** extra.laravel from composer.json, as Laravel's package discovery reads it. */
function discoveredLaravelConfig(): array
{
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    return $composer['extra']['laravel'] ?? [];
}

it('declares the service provider and the facade alias for package discovery', function () {
    $config = discoveredLaravelConfig();

    expect($config['providers'])->each->toBeString()
        ->and(array_filter($config['providers'], 'class_exists'))->toHaveCount(count($config['providers']))
        ->and($config['aliases'])->toBe(['SiMEDPortalUtil' => SiMEDPortalUtil::class])
        ->and(is_subclass_of($config['aliases']['SiMEDPortalUtil'], Facade::class))->toBeTrue();
});

it('calls the Portal through the discovered alias', function () {
    AliasLoader::getInstance(discoveredLaravelConfig()['aliases'])->register();
    Http::fake(['*/w/adm' => Http::response(['ok' => true, 'found' => false])]);

    $result = \SiMEDPortalUtil::getActiveAdmissionWards();

    expect($result)->toBeInstanceOf(PortalResultDto::class)->ok->toBeTrue()->found->toBeFalse();
});
