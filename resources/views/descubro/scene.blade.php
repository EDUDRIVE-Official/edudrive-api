@php
    $sceneDescription = $scene === 'bus'
        ? 'Un bus se aproxima al cruce. Tito y su acompañante esperan en la acera.'
        : ($scene === 'arrived'
            ? 'Tito y su acompañante llegaron juntos a la acera del parque.'
            : ($scene === 'ready'
                ? 'El acompañante ya comprobó el entorno y dio la indicación. Tito espera a su lado para cruzar juntos.'
                : 'El bus pasó. Tito y su acompañante siguen en la acera: falta comprobar antes de cruzar.'));
@endphp
<figure class="dc-scene">
    <svg viewBox="0 0 600 400" role="img" aria-label="Escena de práctica: dos aceras y una calzada. {{ $sceneDescription }}" xmlns="http://www.w3.org/2000/svg">
        <rect width="600" height="400" fill="#e7efda"/>
        <path d="M0 83Q90 40 184 84T376 73T600 81V139H0Z" fill="#d3e3bd"/>
        <path d="M410 132Q400 100 431 64L472 64Q440 111 464 132" fill="#f3deb1"/>
        <g fill="#6e9a61" stroke="#34614a" stroke-width="3">
            <path d="M70 109V48M178 106V36M543 115V55" fill="none" stroke-width="9" stroke-linecap="round"/>
            <circle cx="70" cy="48" r="29"/><circle cx="178" cy="38" r="32"/><circle cx="543" cy="53" r="32"/>
        </g>
        <g fill="#a7c18a"><circle cx="60" cy="37" r="11"/><circle cx="167" cy="27" r="12"/><circle cx="532" cy="42" r="13"/></g>
        <rect x="233" y="28" width="131" height="43" rx="17" fill="#fffdf6"/>
        <text x="298" y="55" text-anchor="middle" fill="#214e3b" font-size="21" font-family="sans-serif" font-weight="700">El parque</text>
        <rect y="135" width="600" height="45" fill="#f4dfb9"/>
        <path d="M0 177H600" stroke="#bdab8b" stroke-width="6"/>
        <rect y="180" width="600" height="110" fill="#697873"/>
        <path d="M0 233H395M479 233H600" stroke="#f7f2de" stroke-width="3" stroke-dasharray="22 20"/>
        <g fill="#fff9e8">
            @foreach ([188, 208, 228, 248, 268] as $stripeY)
                <rect x="418" y="{{ $stripeY }}" width="48" height="12" rx="2"/>
            @endforeach
        </g>
        <rect y="290" width="600" height="110" fill="#f4dfb9"/>
        <path d="M0 293H600" stroke="#bdab8b" stroke-width="6"/>
        <g stroke="#dac39c" stroke-width="2"><path d="M0 354H378M83 299V351M203 355V400M322 299V351M526 355V400"/></g>
        <text x="25" y="328" fill="#524933" font-size="20" font-family="sans-serif" font-weight="700">ACERA</text>
        @if ($scene === 'bus')
            <g stroke="#244637" stroke-width="3" stroke-linejoin="round">
                <rect x="74" y="198" width="163" height="65" rx="13" fill="#e9bb58"/>
                <path d="M91 207H215V231H91Z" fill="#e6f2ed"/>
                <path d="M121 208V231M153 208V231M185 208V231"/>
                <circle cx="105" cy="266" r="12" fill="#244637"/><circle cx="207" cy="266" r="12" fill="#244637"/>
                <circle cx="105" cy="266" r="4" fill="#e4ded0"/><circle cx="207" cy="266" r="4" fill="#e4ded0"/>
                <path d="M211 246H229M223 241L230 246L223 251" fill="none"/>
            </g>
            <text x="100" y="252" fill="#244637" font-size="15" font-family="sans-serif" font-weight="700">BUS</text>
            <g transform="translate(548 251)" stroke="#f9f4e5" stroke-width="2"><circle r="13" fill="#b98066"/><path d="M-12 0H12M0-12Q12 0 0 12M0-12Q-12 0 0 12" fill="none"/></g>
        @endif
        <g transform="translate(423 {{ $scene === 'arrived' ? 98 : 311 }})" stroke="#244637" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M-8 55L-10 75M8 55L10 75M32 65L30 76M44 65L46 76" fill="none" stroke-width="7"/>
            <path d="M-12 23Q0 16 12 23L14 54H-14Z" fill="#598876"/>
            <path d="M-12 26L-19 45M12 28L22 45L30 43" fill="none" stroke="#a96e49" stroke-width="7"/>
            <path d="M29 40Q38 34 47 40L49 63H27Z" fill="#e9bb58"/>
            <path d="M47 42L54 53" fill="none" stroke="#a96e49" stroke-width="6"/>
            <circle cy="4" r="13" fill="#c28b61"/><circle cx="38" cy="24" r="10" fill="#c28b61"/>
            <path d="M-13 1Q-10-16 3-10Q14-9 13 1M29 21Q33 10 44 17" fill="#433b32"/>
            <path d="M-3 8L3 8M35 27L40 27" stroke-width="2"/>
        </g>
    </svg>
    <figcaption data-dc-read>{{ $sceneDescription }}</figcaption>
</figure>
