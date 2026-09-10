<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Stringable;

final class Empresa extends Model implements Stringable
{
    use HasFactory;

    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'nombre_comercial',
        'nombre_aplicacion',
        'nit',
        'direccion',
        'telefono',
        'correo',
        'sitio_web',
        'color_principal',
        'color_secundario',
        'favicon',
        'logo',
        'logo_documentos',
        'activo',
        'es_predeterminada',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'es_predeterminada' => 'boolean',
        ];
    }

    public static function actual(): self
    {
        try {
            /** @var self|null $empresa */
            $empresa = self::query()->where('es_predeterminada', true)->first()
                ?? self::query()->where('activo', true)->first();

            if ($empresa !== null) {
                return $empresa;
            }
        } catch (\Throwable) {
            // Base de datos no migrada aún o no disponible
        }

        $neutral = new self;
        $neutral->forceFill([
            'id' => 0,
            'nombre' => 'Empresa Demo',
            'nombre_comercial' => 'Empresa Demo',
            'nombre_aplicacion' => 'Gestión de Cartas',
            'color_principal' => '#002b49',
            'color_secundario' => '#00487a',
            'nit' => '',
            'direccion' => '',
            'telefono' => '',
            'correo' => '',
            'sitio_web' => '',
            'logo' => null,
            'favicon' => null,
            'logo_documentos' => null,
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        return $neutral;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function firmantes(): HasMany
    {
        return $this->users()->whereIn('role', [RolUsuario::ADMIN, RolUsuario::EDITOR]);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'empresa_id');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function logoDataUri(): ?string
    {
        if (! filled($this->logo)) {
            return null;
        }

        $disco = Storage::disk(config('sdaya.documentos.disk', 'local'));
        if (! $disco->exists((string) $this->logo)) {
            return null;
        }

        $contenido = $disco->get((string) $this->logo);
        $mime = $disco->mimeType((string) $this->logo) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }

    public function logoDocumentosDataUri(): ?string
    {
        $ruta = $this->logo_documentos;
        if (! filled($ruta)) {
            return null;
        }

        $disco = Storage::disk(config('sdaya.documentos.disk', 'local'));
        if (! $disco->exists((string) $ruta)) {
            return null;
        }

        $contenido = $disco->get((string) $ruta);
        $mime = $disco->mimeType((string) $ruta) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }

    public function faviconDataUri(): ?string
    {
        if (! filled($this->favicon)) {
            return null;
        }

        $disco = Storage::disk(config('sdaya.documentos.disk', 'local'));
        if (! $disco->exists((string) $this->favicon)) {
            return null;
        }

        $contenido = $disco->get((string) $this->favicon);
        $mime = $disco->mimeType((string) $this->favicon) ?: 'image/x-icon';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }

    public function monograma(): string
    {
        $texto = trim((string) ($this->nombre_comercial ?: $this->nombre));
        if ($texto === '') {
            return 'GC';
        }

        $palabras = preg_split('/\s+/', $texto);
        if (empty($palabras) || empty($palabras[0])) {
            return 'GC';
        }

        $primera = mb_substr($palabras[0], 0, 1);
        $segunda = isset($palabras[1]) ? mb_substr($palabras[1], 0, 1) : mb_substr($palabras[0], 1, 1);

        return mb_strtoupper($primera.$segunda);
    }

    public function siglaCite(): string
    {
        $sigla = trim((string) ($this->nombre_comercial ?: ''));
        if ($sigla !== '') {
            $limpio = mb_strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $sigla));
            if ($limpio === 'SDAYA') {
                return 'SD';
            }
            if ($limpio !== '') {
                return $limpio;
            }
        }

        $nombre = mb_strtoupper(trim((string) $this->nombre));
        if ($nombre !== '') {
            if (str_contains($nombre, 'SDAYA')) {
                return 'SD';
            }

            $palabras = preg_split('/\s+/', $nombre);
            if (! empty($palabras[0])) {
                $primera = (string) preg_replace('/[^A-Za-z0-9]/', '', $palabras[0]);
                if ($primera === 'SDAYA') {
                    return 'SD';
                }
                if ($primera !== '') {
                    return $primera;
                }
            }
        }

        return 'SD';
    }

    public function __toString(): string
    {
        return (string) $this->nombre;
    }
}