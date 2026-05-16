<x-filament-widgets::widget>
    <div class="enterprise-command-center">
        <div class="enterprise-command-center__hero">
            <div>
                <p class="enterprise-command-center__eyebrow">Operations</p>
                <h2 class="enterprise-command-center__title">Command Center</h2>
            </div>

            <div class="enterprise-command-center__score">
                <span>{{ $healthScore }}</span>
                <small>health</small>
            </div>
        </div>

        <div class="enterprise-command-center__grid">
            <div class="enterprise-command-center__metric enterprise-command-center__metric--large">
                <span>Load</span>
                <strong>{{ $metrics['load'] }}</strong>
                <small>1 minute average</small>
            </div>

            <div class="enterprise-command-center__metric">
                <span>Memory</span>
                <strong>{{ $metrics['memory'] }}</strong>
                <small>used</small>
            </div>

            <div class="enterprise-command-center__metric">
                <span>Disk</span>
                <strong>{{ $metrics['disk'] }}</strong>
                <small>root filesystem</small>
            </div>

            @foreach ($cards as $card)
                <div class="enterprise-command-center__metric">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small>{{ $card['meta'] }}</small>
                </div>
            @endforeach
        </div>

        <div class="enterprise-command-center__incidents">
            @foreach ($incidents as $incident)
                <div class="enterprise-command-center__incident enterprise-command-center__incident--{{ $incident['tone'] }}">
                    <span>{{ $incident['label'] }}</span>
                    <strong>{{ $incident['detail'] }}</strong>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
