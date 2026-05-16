<x-filament-widgets::widget>
    <div class="getting-started">
        <div class="getting-started__header">
            <div>
                <p class="getting-started__eyebrow">Start here</p>
                <h2 class="getting-started__title">Launch a site in four steps</h2>
            </div>

            <a class="getting-started__catalog" href="{{ $catalogUrl }}">
                Modules
            </a>
        </div>

        <div class="getting-started__steps">
            @foreach ($steps as $step)
                <a class="getting-started__step" href="{{ $step['url'] }}">
                    <span>{{ $step['number'] }}</span>
                    <strong>{{ $step['title'] }}</strong>
                    <small>{{ $step['detail'] }}</small>
                    <em>{{ $step['button'] }}</em>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
