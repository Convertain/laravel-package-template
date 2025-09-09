<?php

namespace Convertain\Onboarding\Services;

use Convertain\Onboarding\Contracts\IntegrationDetectorInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class IntegrationDetector implements IntegrationDetectorInterface
{
    protected array $detectionCache = [];
    
    protected array $packageMap;
    
    protected array $gdprCountries;
    
    public function __construct()
    {
        $this->packageMap = config('onboarding.package_detection', []);
        $this->gdprCountries = config('onboarding.consent.gdpr_countries', []);
    }
    
    public function hasOrganizations(): bool
    {
        return $this->detectPackage('laravel-organizations');
    }
    
    public function hasWorldData(): bool
    {
        return $this->detectPackage('laravel-world-data');
    }
    
    public function hasAuthExtended(): bool
    {
        return $this->detectPackage('laravel-auth-extended');
    }
    
    public function hasGeolocation(): bool
    {
        return $this->detectPackage('laravel-geolocation');
    }
    
    public function hasMailcoach(): bool
    {
        return $this->detectPackage('laravel-mailcoach');
    }
    
    public function hasGDPR(): bool
    {
        return $this->detectPackage('laravel-gdpr');
    }
    
    public function hasIubenda(): bool
    {
        return $this->detectPackage('laravel-iubenda');
    }
    
    public function hasCheckout(): bool
    {
        return $this->detectPackage('laravel-checkout');
    }
    
    public function hasCashier(): bool
    {
        return $this->detectPackage('cashier-stripe') || $this->detectPackage('cashier-paddle');
    }
    
    public function hasSubscriptions(): bool
    {
        return $this->detectPackage('laravel-subscriptions') || $this->hasCashier();
    }
    
    public function hasFAQ(): bool
    {
        return $this->detectPackage('laravel-faq');
    }
    
    public function hasTemplate(): bool
    {
        return $this->detectPackage('laravel-template');
    }
    
    public function hasTwilio(): bool
    {
        return $this->detectPackage('laravel-twilio');
    }
    
    public function hasKYC(): bool
    {
        return $this->detectPackage('laravel-kyc');
    }
    
    public function hasXero(): bool
    {
        return $this->detectPackage('laravel-xero');
    }
    
    public function hasPackage(string $packageName): bool
    {
        return $this->detectPackage($packageName);
    }
    
    public function checkPackageConnection(string $packageName, $user): bool
    {
        // Check if a user has connected a specific third-party service
        switch ($packageName) {
            case 'laravel-xero':
                if ($this->hasXero() && method_exists($user, 'hasXeroConnection')) {
                    return $user->hasXeroConnection();
                }
                break;
                
            case 'socialite':
                if ($this->detectPackage('socialite') && method_exists($user, 'socialAccounts')) {
                    return $user->socialAccounts()->exists();
                }
                break;
                
            default:
                // Allow custom checks via event/hook
                $result = app('events')->dispatch('onboarding.check_package_connection', [
                    $packageName,
                    $user
                ]);
                
                if (!empty($result)) {
                    return $result[0] === true;
                }
        }
        
        return false;
    }
    
    public function isGDPRCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), $this->gdprCountries);
    }
    
    public function getEmailRegulations(string $countryCode, string $businessType): array
    {
        $isGDPR = $this->isGDPRCountry($countryCode);
        $isB2B = strtolower($businessType) === 'b2b';
        
        return [
            'requires_explicit_consent' => $isGDPR || !$isB2B,
            'allows_auto_subscribe' => !$isGDPR && $isB2B && in_array(
                strtoupper($countryCode),
                config('onboarding.consent.b2b_auto_subscribe_countries', [])
            ),
            'double_opt_in_required' => $isGDPR,
            'gdpr_applies' => $isGDPR,
            'consent_logging_required' => $isGDPR || !$isB2B,
            'unsubscribe_required' => true, // Always required globally
        ];
    }
    
    public function hasFreeTier(): bool
    {
        // Check if the application has a free tier
        $hasFreeTier = config('app.has_free_tier', true);
        
        // Also check if there's a free plan in subscriptions
        if ($this->hasSubscriptions()) {
            $freePlanExists = config('subscriptions.plans.free', false) !== false;
            return $hasFreeTier || $freePlanExists;
        }
        
        return $hasFreeTier;
    }
    
    public function requiresPayment(): bool
    {
        // Payment is required if there's no free tier
        return !$this->hasFreeTier();
    }
    
    public function requiresKYC($user): bool
    {
        // Check if KYC is required based on configuration and user attributes
        if (!$this->hasKYC()) {
            return false;
        }
        
        $kycRequired = config('onboarding.steps.kyc.required', false);
        
        // Check for user-specific KYC requirements
        if (method_exists($user, 'requiresKYC')) {
            return $user->requiresKYC();
        }
        
        // Check for business rules (e.g., high-value transactions)
        if ($kycRequired) {
            return true;
        }
        
        // Check for country-specific requirements
        if (method_exists($user, 'country')) {
            $regulatedCountries = config('kyc.regulated_countries', []);
            return in_array($user->country, $regulatedCountries);
        }
        
        return false;
    }
    
    public function getRequiredIntegrations(): array
    {
        $required = config('onboarding.required_integrations', []);
        
        // Add conditionally required integrations
        $integrations = [];
        
        foreach ($required as $integration) {
            // Parse integration requirements
            if (is_string($integration)) {
                $integrations[] = [
                    'name' => $integration,
                    'package' => $integration,
                    'required' => true,
                    'condition' => null,
                ];
            } elseif (is_array($integration)) {
                $integrations[] = array_merge([
                    'required' => true,
                    'condition' => null,
                ], $integration);
            }
        }
        
        // Add role-based integrations
        if ($this->hasXero() && config('onboarding.custom_steps.xero_setup.required', false)) {
            $integrations[] = [
                'name' => 'Xero',
                'package' => 'laravel-xero',
                'required' => true,
                'condition' => 'user.hasRole("admin")',
            ];
        }
        
        return $integrations;
    }
    
    protected function detectPackage(string $packageKey): bool
    {
        // Cache detection results per request
        if (isset($this->detectionCache[$packageKey])) {
            return $this->detectionCache[$packageKey];
        }
        
        // Get the class to check from config
        $className = $this->packageMap[$packageKey] ?? null;
        
        if (!$className) {
            $this->detectionCache[$packageKey] = false;
            return false;
        }
        
        // Use class_exists to detect if package is installed
        $exists = class_exists($className);
        
        // Cache the result
        $this->detectionCache[$packageKey] = $exists;
        
        return $exists;
    }
    
    public function getAllDetectedPackages(): array
    {
        $detected = [];
        
        foreach ($this->packageMap as $key => $className) {
            if ($this->detectPackage($key)) {
                $detected[] = $key;
            }
        }
        
        return $detected;
    }
    
    public function getFeatureAvailability(): array
    {
        return [
            'organizations' => $this->hasOrganizations(),
            'world_data' => $this->hasWorldData(),
            'geolocation' => $this->hasGeolocation(),
            'email_verification' => $this->hasAuthExtended(),
            'phone_verification' => $this->hasTwilio(),
            'newsletter' => $this->hasMailcoach(),
            'gdpr_compliance' => $this->hasGDPR() || $this->hasIubenda(),
            'billing' => $this->hasCheckout(),
            'subscriptions' => $this->hasSubscriptions(),
            'faq' => $this->hasFAQ(),
            'minimal_template' => $this->hasTemplate(),
            'kyc' => $this->hasKYC(),
            'accounting' => $this->hasXero(),
            'social_login' => $this->detectPackage('socialite'),
        ];
    }
}