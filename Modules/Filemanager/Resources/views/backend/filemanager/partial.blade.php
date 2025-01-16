@foreach ($mediaUrls as $mediaUrl)
    @php
        $isVideo = strpos($mediaUrl, '.mp4') !== false || strpos($mediaUrl, '.webm') !== false;
        $mediaType = $isVideo ? 'video' : 'image';
    @endphp
    <div id="media-images">

        @if(isset($is_media))
        <div class="iq-media-images position-relative">
            @if ($mediaType === 'video')
            <a href="{{ $mediaUrl }}" target="blank">
                <video class="img-fluid object-fit-cover" style="width: 10rem; height: 10rem;" preload="metadata"   controlsList="nodownload" controls>
                    <source src="{{ $mediaUrl }}" type="video/mp4">
                </video>
            </a>
            @else
                <a href="{{ $mediaUrl }}" target="blank"><img class="img-fluid object-fit-cover" src="{{ $mediaUrl }}" style="width: 10rem; height: 10rem;" loading="lazy" ></a>
            @endif
            <button class="btn btn-danger position-absolute top-0 start-0 m-2 py-2 px-2 iq-button-delete" onclick="deleteImage('{{ $mediaUrl }}')">
                <i class="ph ph-trash"></i>
            </button>
            <p class="media-title pt-2 mb-0" data-bs-toggle="tooltip" data-bs-title="{{ basename($mediaUrl) }}">{{ basename($mediaUrl) }}</p>
        </div>

        @else

        <div class="iq-media-images position-relative">
            @if ($mediaType === 'video')

                <video class="img-fluid object-fit-cover" style="width: 10rem; height: 10rem;" preload="metadata"   controlsList="nodownload" controls>
                    <source src="{{ $mediaUrl }}" type="video/mp4">
                </video>

            @else
               <img class="img-fluid object-fit-cover cursor-pointer" src="{{ $mediaUrl }}" style="width: 10rem; height: 10rem;" loading="lazy" >
            @endif
            <button class="btn btn-danger position-absolute top-0 start-0 m-2 py-1 px-2 iq-button-delete" onclick="deleteImage('{{ $mediaUrl }}')">
                <i class="ph ph-trash"></i>
            </button>
            <p class="media-title pt-2 mb-0" data-bs-toggle="tooltip" data-bs-title="{{ basename($mediaUrl) }}">{{ basename($mediaUrl) }}</p>
        </div>
        @endif
    </div>
@endforeach

