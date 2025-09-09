<?php

namespace Convertain\Onboarding\Contracts;

use Illuminate\Http\Request;

interface StepInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getView(): string;
    public function getPosition(): ?string;
    public function isRequired(): bool;
    public function isSkippable(): bool;
    public function shouldShow($user): bool;
    public function validate(Request $request): array;
    public function process(Request $request, $user): bool;
    public function getValidationRules(): array;
    public function getValidationMessages(): array;
    public function getData($user): array;
    public function getFAQGroup(): ?string;
}