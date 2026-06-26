@props([
    'title' => null,
])

@php
    // Self-contained letterhead: the emblem is base64-embedded so the markup
    // survives being stored to disk or exported to PDF (no server URL needed).
    $emblemPath = 'pngwing.com.png';
    $emblemDisk = 'public';
    $emblemDataUri = null;

    if (\Illuminate\Support\Facades\Storage::disk($emblemDisk)->exists($emblemPath)) {
        $emblemContents = \Illuminate\Support\Facades\Storage::disk($emblemDisk)->get($emblemPath);
        $emblemMime = \Illuminate\Support\Facades\Storage::disk($emblemDisk)->mimeType($emblemPath) ?: 'image/png';
        $emblemDataUri = 'data:'.$emblemMime.';base64,'.base64_encode($emblemContents);
    }
@endphp

<header style="text-align:center;">
    @if (!empty($emblemDataUri))
        <img src="{{ $emblemDataUri }}" alt="State Emblem of India" style="height:80px; width:auto; display:block; margin:0 auto 8px;">
    @endif
    <div style="font-size:16px; font-weight:bold;">Govt. Of Assam</div>
    <div style="font-size:14px; font-weight:bold;">Office of the Mission Director: Jal Jeevan Mission, Assam</div>
    <div style="font-size:13px;">Public Health Engineering Department</div>
    <div style="font-size:13px;">Hengrabari, Guwahati-36</div>

    @if (!empty($title))
        <div style="margin-top:14px; padding-top:10px; border-top:2px solid #0f172a; font-size:14px; font-weight:bold; letter-spacing:.04em; text-transform:uppercase;">
            {{ $title }}
        </div>
    @endif
</header>
