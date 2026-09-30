@php($picture = [['road', 'sidewalk'], ['quick', 'wait', 'ball'], ['quick', 'look'], ['together', 'alone']][$run['question']][$choice])
<svg class="dc-choice-picture" viewBox="0 0 96 72" fill="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
    <rect width="96" height="72" rx="14" fill="#edf1e4"/>
    @if ($picture === 'road' || $picture === 'sidewalk')
        <path d="M0 17H96V54H0Z" fill="#75847b"/>
        <path d="M0 35H96" stroke="#fff9e8" stroke-width="2" stroke-dasharray="10 8"/>
        <path d="M0 54H96V72H0Z" fill="#f4dfb9"/>
        <g transform="translate(43 {{ $picture === 'road' ? 25 : 55 }})" fill="#244637"><ellipse cx="0" cy="3" rx="4" ry="6"/><ellipse cx="13" cy="8" rx="4" ry="6"/></g>
    @elseif ($picture === 'ball')
        <circle cx="48" cy="36" r="23" fill="#d6a179" stroke="#244637" stroke-width="2"/>
        <path d="M25 36H71M48 13Q20 36 48 59M48 13Q76 36 48 59" stroke="#fff9e8" stroke-width="3"/>
    @elseif ($picture === 'wait')
        <path d="M30 39V23Q30 17 36 20V35V15Q36 9 42 13V34V12Q43 7 49 12V34V17Q51 11 56 17V41L64 34Q71 30 71 37L62 53Q57 62 44 61Q34 61 28 50L22 38Q21 31 27 33Z" fill="#d6a179" stroke="#244637" stroke-width="2.5" stroke-linejoin="round"/>
    @elseif ($picture === 'look')
        <path d="M17 36Q48 5 79 36Q48 67 17 36Z" fill="#fffdf6" stroke="#244637" stroke-width="3"/>
        <circle cx="48" cy="36" r="11" fill="#598876"/><circle cx="48" cy="36" r="5" fill="#244637"/>
        <path d="M17 17H30M17 17L22 12M17 17L22 22M79 55H66M79 55L74 50M79 55L74 60" stroke="#244637" stroke-width="2.5" stroke-linecap="round"/>
    @elseif ($picture === 'quick')
        <g stroke="#244637" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><circle cx="56" cy="15" r="6" fill="#d6a179" stroke-width="2"/><path d="M49 27L41 41L56 48L59 61M41 41L31 57L19 57M48 29L59 36L69 30M46 27L34 27L28 36"/><path d="M14 16H34M12 26H22" stroke-width="2"/></g>
    @else
        <g stroke="#244637" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="30" cy="17" r="8" fill="#d6a179"/><path d="M23 29H37V47H23Z" fill="#598876"/><path d="M26 48L24 62M34 48L36 62M22 31L17 43"/>
            <g transform="translate({{ $picture === 'alone' ? 13 : 0 }} 0)"><circle cx="61" cy="30" r="6" fill="#d6a179"/><path d="M55 39H67V53H55Z" fill="#e9bb58"/><path d="M58 54L57 63M64 54L66 63M67 41L72 48"/></g>
            @if ($picture === 'together')<path d="M37 32L47 44L55 41"/>@else<path d="M37 32L42 44"/>@endif
        </g>
    @endif
</svg>
