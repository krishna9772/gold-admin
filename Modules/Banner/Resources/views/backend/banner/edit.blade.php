@extends('backend.layouts.app')

@section('content')
<x-back-button-component route="backend.banners.index" />
{{ html()->form('PUT', route('backend.banners.update', $banner->id))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')
    ->attribute('id', 'form-submit')
    ->class('requires-validation')  // Add the requires-validation class
    ->attribute('novalidate', 'novalidate')  // Disable default browser validation
    ->open() }}
@csrf
<div class="card">
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-6 col-lg-3">
                <div class="position-relative">
                    {{ html()->label(__('banner.lbl_image'), 'file_url')->class('form-label') }}
                    <div class="input-group btn-file-upload">
                        {{ html()->button('<i class="ph ph-image"></i> ' . __('messages.lbl_choose_image'))
                            ->class('input-group-text form-control')
                            ->type('button')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainer1')
                            ->attribute('data-hidden-input', 'file_url1')
                            ->style('height:13.6rem')
                        }}

                        {{ html()->text('image_input1')
                            ->class('form-control')
                            ->placeholder('Select Image')
                            ->attribute('aria-label', 'Image Input 1')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainer1')
                            ->attribute('data-hidden-input', 'file_url1')
                            ->attribute('aria-describedby', 'basic-addon1')
                        }}
                    </div>
                    <div class="uploaded-image" id="selectedImageContainer1">
                        @if ($banner->file_url)
                            <img src="{{ $banner->file_url }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                            <span class="remove-media-icon"
                                  style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                  onclick="removeImage('selectedImageContainer1', 'file_url1', 'remove_image_flag1')">×</span>
                        @endif
                    </div>
                    {{ html()->hidden('file_url')->id('file_url1')->value($banner->file_url) }}
                    {{ html()->hidden('remove_image')->id('remove_image_flag1')->value(0) }}
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="position-relative">
                    {{ html()->label(__('movie.lbl_poster'), 'poster')->class('form-label') }}
                    <div class="input-group btn-file-upload">
                        {{ html()->button('<i class="ph ph-image"></i> ' . __('messages.lbl_choose_image'))
                            ->class('input-group-text form-control')
                            ->type('button')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainer2')
                            ->attribute('data-hidden-input', 'file_url2')
                            ->style('height:13.6rem')
                        }}

                        {{ html()->text('image_input2')
                            ->class('form-control')
                            ->placeholder('Select Image')
                            ->attribute('aria-label', 'Image Input 2')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainer2')
                            ->attribute('data-hidden-input', 'file_url2')
                            ->attribute('aria-describedby', 'basic-addon1')
                        }}
                    </div>
                    <div class="uploaded-image" id="selectedImageContainer2">
                        @if ($banner->poster_url)
                            <img src="{{ $banner->poster_url }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                            <span class="remove-media-icon"
                                  style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                  onclick="removeImage('selectedImageContainer2', 'file_url2', 'remove_image_flag2')">×</span>
                        @endif
                    </div>
                    {{ html()->hidden('poster_url')->id('file_url2')->value($banner->poster_url) }}
                    {{ html()->hidden('remove_image')->id('remove_image_flag2')->value(0) }}
                </div>
            </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        {{ html()->label(__('banner.lbl_type') . '<span class="text-danger">*</span>', 'type')->class('form-label') }}
                        {{ html()->select('type', $types, old('type', $banner->type))->class('form-control select2')->id('type')->attribute('required','required') }}
                        @error('type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Type field is required</div>
                    </div>
                    {{ html()->hidden('type_id', old('type_id', $banner->type_id))->id('type_id') }}
                    {{ html()->hidden('type_name', old('type_name', $banner->type_name))->id('type_name') }}
                    <div class="">
                        {{ html()->label(__('banner.lbl_name') . '<span class="text-danger">*</span>', 'name')->class('form-label') }}
                        {{ html()->select('name_id',$names[$banner->type], old('name_id', $banner->type_id))->class('form-control select2')->id('name_id')->attribute('required','required') }}
                        @error('name_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Name field is required</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="">
                        {{ html()->label(__('banner.lbl_status'), 'status')->class('form-label') }}
                        <div class="d-flex justify-content-between align-items-center form-control">
                           {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0 text-body') }}
                            <div class="form-check form-switch">
                                {{ html()->hidden('status', 0) }}
                                {{ html()->checkbox('status', old('status', $banner->status))->class('form-check-input')->id('status')->value(1) }}
                            </div>
                        </div>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid d-sm-flex justify-content-sm-end gap-3">
        {{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>
{{ html()->form()->close() }}

@include('components.media-modal')
@endsection

@push('after-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    async function getNames(type, selectedNameId = "") {
      var get_names_list = "{{ route('backend.banners.index_list', ['type' => ':type']) }}".replace(':type', type);

      const response = await fetch(get_names_list);
      const result = await response.json();

      var formattedResult = [{ id: '', text: "{{ __('messages.select_name') }}" }];

      var names = result.map(function(item) {
        return {
          id: item.id,
          text: item.name,
          thumbnail_url: item.thumbnail_url,
          poster_url: item.poster_url
        };
      });

      formattedResult = formattedResult.concat(names);

      $('#name_id').select2({
        width: '100%',
        data: formattedResult
      });

      if (selectedNameId) {
        $('#name_id').val(selectedNameId).trigger('change');
      }
    }

    $('#type').change(function() {
      var type = $(this).val();
      var typeName = $('#type option:selected').text();

      if (type) {
        $('#type_id').val(type);
        $('#type_name').val(typeName);

        $('#name_id').empty();
        getNames(type);
      } else {
        $('#name_id').empty();
      }
    });

    $('#name_id').change(function() {
      var selectedNameId = $(this).val();
      var selectedNameText = $('#name_id option:selected').text();

      if (selectedNameId) {
        $('#type_id').val(selectedNameId);
        $('#type_name').val(selectedNameText);
      } else {
        $('#type_id').val('');
        $('#type_name').val('');
      }
    });

    $('#name_id').change(function () {
      var selectedOption = $('#name_id').select2('data')[0];

      if (selectedOption) {
        var posterUrl = selectedOption.poster_url;
        var thumbnailUrl = selectedOption.thumbnail_url;

        if (posterUrl) {
          $('#file_url1').val(posterUrl);
          $('#selectedImageContainer1').html(`<img src="${posterUrl}" class="img-fluid avatar-150">`);
          $('#removeImageBtn1').removeClass('d-none');
        } else {
          $('#selectedImageContainer1').html('');
          $('#file_url1').val('');
          $('#removeImageBtn1').addClass('d-none');
        }

        if (thumbnailUrl) {
          $('#file_url2').val(thumbnailUrl);
          $('#selectedImageContainer2').html(`<img src="${thumbnailUrl}" class="img-fluid avatar-150">`);
          $('#removeImageBtn2').removeClass('d-none');
        } else {
          $('#selectedImageContainer2').html('');
          $('#file_url2').val('');
          $('#removeImageBtn2').addClass('d-none');
        }
      }
    });

    function removeImage(containerId, hiddenInputId, removedFlagId) {
      var container = document.getElementById(containerId);
      var hiddenInput = document.getElementById(hiddenInputId);
      var removedFlag = document.getElementById(removedFlagId);

      container.innerHTML = '';
      hiddenInput.value = '';
      removedFlag.value = 1;
    }

    window.removeImage = removeImage;

    $('#removeImageBtn1').click(function () {
      removeImage('selectedImageContainer1', 'file_url1', 'remove_image_flag1');
    });

    $('#removeImageBtn2').click(function () {
      removeImage('selectedImageContainer2', 'file_url2', 'remove_image_flag2');
    });
  });

  
</script>
@endpush
