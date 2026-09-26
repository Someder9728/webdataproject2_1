<?php

namespace App\Providers;


use App\Http\Middleware\EnsurePasswordIsChanged;
use Livewire\Livewire;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Http\Middleware\EnsureRole;
use App\Models\Tenant;
use App\Policies\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use App\Models\Rental;
use App\Policies\RentalPolicy;
use App\Models\Room;
use App\Policies\RoomPolicy;
use App\Models\Repair;
use App\Policies\RepairPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();


        Livewire::addPersistentMiddleware([
            EnsurePasswordIsChanged::class,
            EnsureRole::class,
        ]);

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Rental::class, RentalPolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(Repair::class, RepairPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
