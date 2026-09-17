@props([
    'icon' => '₱',
    'title' => 'Nothing here yet',
    'message' => 'This section is empty.',
])

<div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"> {{ $icon }} </span>

    <div>
        <strong> {{ $title }} </strong>

        <p> {{ $message }} </p>
    </div>
</div>