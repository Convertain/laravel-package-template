<?php

namespace Convertain\Onboarding\Contracts;

interface IntegrationDetectorInterface
{
    public function hasOrganizations(): bool;
    public function hasWorldData(): bool;
    public function hasAuthExtended(): bool;
    public function hasGeolocation(): bool;
    public function hasMailcoach(): bool;
    public function hasGDPR(): bool;
    public function hasIubenda(): bool;
    public function hasCheckout(): bool;
    public function hasCashier(): bool;
    public function hasSubscriptions(): bool;
    public function hasFAQ(): bool;
    public function hasTemplate(): bool;
    public function hasTwilio(): bool;
    public function hasKYC(): bool;
    public function hasXero(): bool;
    public function hasPackage(string $packageName): bool;
    public function checkPackageConnection(string $packageName, $user): bool;
    public function isGDPRCountry(string $countryCode): bool;
    public function getEmailRegulations(string $countryCode, string $businessType): array;
    public function hasFreeTier(): bool;
    public function requiresPayment(): bool;
    public function requiresKYC($user): bool;
    public function getRequiredIntegrations(): array;
}