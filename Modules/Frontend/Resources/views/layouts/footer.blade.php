
@php
$footerData = getFooterData();
@endphp

<footer class="footer">
  <div class="footer-top">
    <div class="container-fluid">
      <div class="row">
        <div class="col-xxl-2 col-xl-2 col-sm-6">
          <div class="footer-logo mb-4">
              <!--Logo -->
               @include('frontend::components.partials.logo')
          </div>
          <span class="font-size-14">
            {{__('frontend.footer_content')}}
          </span>
          <div class="mt-5">
            <p class="mb-2 font-size-14">{{__('frontend.email_us')}}: <a href="mailto:customer@goldchannel.com" class="link-body-emphasis">customer@goldchannel.com</a></p>
            <p class="m-0 font-size-14">{{__('frontend.helpline_number')}}: <a href="tel:+480-555-0103" class="link-body-emphasis fw-medium">+ (480) 555-0103</a></p>
          </div>
        </div>
        @if(isenablemodule('tvshow')==1)
        <div class="col-xxl-2 col-xl-2 col-sm-6 mt-sm-0 mt-5">
            <h4 class="footer-title font-size-18 mb-5">{{__('frontend.premium_show')}}</h4>
            <ul class="list-unstyled footer-menu">
                @foreach($footerData['premiumShows'] as $show)
                <li class="mb-3">
                    <a href="{{ route('tvshow-details', $show->id) }}">{{ $show->name }}</a>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @if(isenablemodule('movie')==1)
        <div class="col-xxl-2 col-xl-2 col-sm-6 mt-xl-0 mt-5">
          <h4 class="footer-title font-size-18 mb-5">{{__('frontend.top_movie_to_watch')}}</h4>
          <ul class="list-unstyled footer-menu">
            @foreach($footerData['topMovies'] as $movie)
            <li class="mb-3">
              @if($movie->type=='movie')
              <a href="{{ route('movie-details', $movie->id) }}">{{ $movie->name }}</a>
              @else
              <a href="{{ route('tvshow-details', $movie->id) }}">{{ $movie->name }}</a>
              @endif
            </li>
            @endforeach
          </ul>
        </div>
        @endif
        <div class="col-xxl-3 col-xl-3 col-sm-6 mt-xl-0 mt-5">
          <h4 class="footer-title font-size-18 mb-5">{{__('frontend.usefull_links')}}</h4>
          <ul class="list-unstyled footer-menu column-count-2">
            @foreach($footerData['pages'] as $page)

            <li class="mb-3">
            <a href="{{ route('page.show', ['slug' => $page->slug]) }}">{{ $page->name }}</a>
            </li>
            @endforeach
            <li class="mb-3">
              <a href="{{route('faq')}}">{{__('frontend.faq')}}</a>
            </li>

          </ul>
        </div>
        <div class="col-xxl-3 col-xl-3 col-sm-6 mt-xl-0 mt-5">
          <h4 class="footer-title font-size-18 mb-5">{{__('frontend.download_app')}}</h4>
          <p class="mb-5">{{__('frontend.download_app_reason')}}</p>

          <ul class="app-icon list-inline m-0 p-0 d-flex align-items-center gap-3">
            <li>
              <a href="#" class="btn btn-link p-0">
              <img src="{{ asset('img/web-img/play_store.png') }}" alt="play store" class="img-fluid">
              </a>
            </li>
            <li>
            <a href="#" class="btn btn-link p-0">
              <img src="{{ asset('img/web-img/app_store.png') }}" alt="app store" class="img-fluid">
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container-fluid">
      <div class="text-center">
        © {{ now()->year }} <span class="text-primary">{{ env('APP_NAME') }}</span>. {{__('frontend.all_rights_reserved')}}.
      </div>
    </div>
  </div>
</footer>
<!-- sticky footer -->
  @include('frontend::components.partials.footer-sticky-menu')
