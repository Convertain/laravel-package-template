<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

// Build paths array dynamically - only include directories that exist
$paths = [];
$possiblePaths = [
    __DIR__.'/src',
    __DIR__.'/tests',
    __DIR__.'/config',
    __DIR__.'/database',
    __DIR__.'/routes',
];

foreach ($possiblePaths as $path) {
    if (is_dir($path)) {
        $paths[] = $path;
    }
}

return RectorConfig::configure()
    ->withPaths($paths)
    ->withSkipPath(__DIR__.'/vendor')
    ->withPhpSets(php85: true)
    ->withSets([
        LaravelSetList::LARAVEL_120,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    // Skip rules that conflict with PHPStan Level 10
    ->withSkip([
        // These rules remove @var annotations that PHPStan needs for type inference
        \Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class,
        // FlipTypeControlToUseExclusiveTypeRector can cause issues with nullable types
        \Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class,
    ])
    ->withRules([
        // Laravel-specific improvements
        RectorLaravel\Rector\MethodCall\EloquentOrderByToLatestOrOldestRector::class,
        RectorLaravel\Rector\MethodCall\EloquentWhereTypeHintClosureParameterRector::class,
        RectorLaravel\Rector\MethodCall\EloquentWhereRelationTypeHintingParameterRector::class,
        RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector::class,
        RectorLaravel\Rector\Class_\AddExtendsAnnotationToModelFactoriesRector::class,
        RectorLaravel\Rector\ClassMethod\MigrateToSimplifiedAttributeRector::class,
        RectorLaravel\Rector\Class_\AnonymousMigrationsRector::class,
        RectorLaravel\Rector\If_\AbortIfRector::class,
        RectorLaravel\Rector\If_\ThrowIfRector::class,
        RectorLaravel\Rector\If_\ReportIfRector::class,
        RectorLaravel\Rector\MethodCall\RedirectBackToBackHelperRector::class,
        RectorLaravel\Rector\MethodCall\RedirectRouteToToRouteHelperRector::class,
        RectorLaravel\Rector\PropertyFetch\OptionalToNullsafeOperatorRector::class,
        RectorLaravel\Rector\FuncCall\NowFuncWithStartOfDayMethodCallToTodayFuncRector::class,
        RectorLaravel\Rector\MethodCall\ValidationRuleArrayStringValueToArrayRector::class,
        RectorLaravel\Rector\MethodCall\JsonCallToExplicitJsonCallRector::class,
        RectorLaravel\Rector\MethodCall\AssertStatusToAssertMethodRector::class,
        RectorLaravel\Rector\StaticCall\CarbonSetTestNowToTravelToRector::class,
        RectorLaravel\Rector\Class_\ModelCastsPropertyToCastsMethodRector::class,
        RectorLaravel\Rector\ClassMethod\AddParentBootToModelClassMethodRector::class,
        RectorLaravel\Rector\Empty_\EmptyToBlankAndFilledFuncRector::class,
        RectorLaravel\Rector\FuncCall\RemoveRedundantValueCallsRector::class,
        RectorLaravel\Rector\FuncCall\RemoveRedundantWithCallsRector::class,
        RectorLaravel\Rector\MethodCall\AvoidNegatedCollectionFilterOrRejectRector::class,
        RectorLaravel\Rector\BooleanNot\AvoidNegatedCollectionContainsOrDoesntContainRector::class,
        RectorLaravel\Rector\MethodCall\UnaliasCollectionMethodsRector::class,
    ]);
