@props(['url', 'size' => 180])

<div {{ $attributes->merge(['class' => 'inline-block rounded-xl bg-white p-3 shadow-novix-sm']) }}>
    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size($size)->margin(0)->generate($url) !!}
</div>
