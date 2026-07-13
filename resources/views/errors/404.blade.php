{{-- Nieznany adres zwraca status 404, ale nadal renderuje powłokę SPA: robot
     dostaje uczciwy kod, a człowiek widzi stronę „nie znaleziono” Vue Routera.
     Meta uzupełnia view composer z AppServiceProvider (`noindex`). --}}
@include('app')
