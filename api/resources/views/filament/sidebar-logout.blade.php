{{-- Logout as a regular item at the end of the sidebar navigation (the user menu keeps its own). --}}
<ul class="fi-sidebar-nav-groups">
    <li class="fi-sidebar-item">
        <form method="POST" action="{{ filament()->getLogoutUrl() }}">
            @csrf
            <button type="submit" class="fi-sidebar-item-btn" style="width: 100%; text-align: left">
                {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedArrowLeftStartOnRectangle, attributes: (new \Filament\Support\View\ComponentAttributeBag)->class(['fi-sidebar-item-icon']), size: \Filament\Support\Enums\IconSize::Large) }}
                <span class="fi-sidebar-item-label" style="text-align: left">Uitloggen</span>
            </button>
        </form>
    </li>
</ul>
