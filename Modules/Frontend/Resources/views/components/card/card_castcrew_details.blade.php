
<a href="{{ route('castcrew-detail', ['id' => $data['id']]) }}" class="text-center cast-card position-relative rounded overflow-hidden d-block">
    <img src="{{ $data['profile_image'] }}" alt="personality" class="img-fluid object-cover position-relative cast-image">
    <span class="h6 mb-0 cast-title"> {{  $data['name'] ?? '--' }}</span>
</a>