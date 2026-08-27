@if (session('success') || session('error') || session('status'))
    <div class="mb-6 space-y-3" aria-live="polite" aria-atomic="true">
        @foreach (['success', 'error', 'status'] as $type)
            @if (session($type))
                <div
                    class="flash-message flex items-start justify-between gap-4 rounded-2xl border px-4 py-3.5 text-sm font-semibold shadow-sm {{ $type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : ($type === 'error' ? 'border-red-200 bg-red-50 text-red-900' : 'border-sdaya-200 bg-sdaya-50 text-sdaya-900') }}"
                    role="{{ $type === 'error' ? 'alert' : 'status' }}"
                >
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5"><x-icon :name="$type === 'success' ? 'check-circle' : ($type === 'error' ? 'alert-circle' : 'info')" size="19" /></span>
                        <p class="leading-6">{{ session($type) }}</p>
                    </div>
                    <button type="button" class="flash-dismiss focus-ring grid size-8 shrink-0 place-items-center rounded-lg transition-colors hover:bg-black/5" aria-label="Cerrar mensaje">
                        <x-icon name="x" size="17" />
                    </button>
                </div>
            @endif
        @endforeach
    </div>
@endif
