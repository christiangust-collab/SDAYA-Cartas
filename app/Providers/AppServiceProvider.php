<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GeneradorArchivosDocumento;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use App\Policies\AreaPolicy;
use App\Policies\DocumentoPolicy;
use App\Policies\TipoPolicy;
use App\Services\GeneradorArchivosLaravel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GeneradorArchivosDocumento::class, GeneradorArchivosLaravel::class);
    }

    public function boot(): void
    {
        Gate::policy(Area::class, AreaPolicy::class);
        Gate::policy(Tipo::class, TipoPolicy::class);
        Gate::policy(Documento::class, DocumentoPolicy::class);

        Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('verificacion', static function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
