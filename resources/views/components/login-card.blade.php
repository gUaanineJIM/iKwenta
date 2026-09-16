@props([
    'title',
    'description',
    'buttonText',
    'modalTarget',
    'image',
    'type'
])

<div class="login-card">

    <div class="login-card-image">
        <img src="{{ $image }}" alt="{{ $title }}">

        <div class="image-overlay"></div>

        <span class="login-badge">
            {{ ucfirst($type) }}
        </span>
    </div>

    <div class="login-card-content">

        <h2>{{ $title }}</h2>

        <p>
            {{ $description }}
        </p>

        <button type="button" class="btn btn-primary login-button" data-open-modal="{{ $modalTarget }}">
            {{ $buttonText }}

            <span class="arrow" aria-hidden="true">→</span>
        </button>

    </div>

</div>