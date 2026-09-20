<a href="{{ isset($href) && $href ? $href : url('/') }}" class="logo" aria-label="iKwenta — Home">
    <span class="logo-mark">{!! file_get_contents(public_path('images/iKwenta-logo.svg')) !!}</span>
    <span class="logo-text">iKwenta</span>
</a>