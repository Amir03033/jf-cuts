<div class="card">
    <div class="revenue-header">
        <p class="muted">Omzet</p>

        <div class="revenue-tabs">
            <button
                type="button"
                wire:click="setPeriod('week')"
                class="{{ $period === 'week' ? 'active' : '' }}"
            >
                Week
            </button>

            <button
                type="button"
                wire:click="setPeriod('month')"
                class="{{ $period === 'month' ? 'active' : '' }}"
            >
                Maand
            </button>

            <button
                type="button"
                wire:click="setPeriod('year')"
                class="{{ $period === 'year' ? 'active' : '' }}"
            >
                Jaar
            </button>
        </div>
    </div>

    <div class="big">
        €{{ number_format($revenue, 2, ',', '.') }}
    </div>
</div>
