<x-layouts.app>
    <x-slot:title>Editar borrador</x-slot:title>
    <x-slot:heading>Editar borrador #{{ $documento->id }}</x-slot:heading>
    <x-slot:subheading>Los documentos emitidos permanecen inmutables.</x-slot:subheading>

    @include('documentos._form')
</x-layouts.app>
