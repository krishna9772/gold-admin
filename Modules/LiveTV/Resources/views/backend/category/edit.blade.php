@extends('backend.layouts.app')

@section('content')
<x-back-button-component route="backend.tv-category.index" />
<p class="text-danger" id="error_message"></p>

{{ html()->form('POST' ,route('backend.tv-category.update',  $category->id))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')  // Add the id attribute here
    ->class('requires-validation')  // Add the requires-validation class
    ->attribute('novalidate', 'novalidate')  // Disable default browser validation
    ->open() }}
@csrf
    <div class="card">
        <div class="card-body">
            @method('PUT')
            <div class="row gy-3">
                <div class="col-md-6 position-relative">
                    <div class="input-group btn-file-upload">
                        {{ html()->button('<i class="ph ph-image"></i>'.__('messages.lbl_choose_image'))
                            ->class('input-group-text form-control')
                            ->type('button')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainer1')
                            ->attribute('data-hidden-input', 'file_url1')
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
                        @if ( $category->file_url)
                            <img src="{{  $category->file_url }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                            <span class="remove-media-icon" 
                                  style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;" 
                                  onclick="removeImage('file_url1', 'remove_image_flag')">×</span>
                
                        @endif  
                    </div>
                    {{ html()->hidden('file_url')->id('file_url1')->value( $category->file_url) }}
                    {{ html()->hidden('remove_image')->id('remove_image_flag')->value(0) }}
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        {{ html()->label(__('genres.lbl_name') . '<span class="text-danger">*</span>', 'name')->class('form-label')}}
                        {{
                        html()->text('name',  $category->name)
                            ->class('form-control')
                            ->id('name')
                            ->placeholder(__('placeholder.lbl_genre_name'))
                            ->attribute('required','required')
                        }}
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Name field is required</div>
                    </div>
                    <div>
                        {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                        <div class="d-flex justify-content-between align-items-center form-control">
                            {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0 text-body') }}
                            <div class="form-check form-switch">
                                {{ html()->hidden('status', 0) }}
                                {{
                                html()->checkbox('status', $category->status)
                                    ->class('form-check-input')
                                    ->id('status')
                            }}
                            </div>
                        </div>
                        @error('status')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-sm-12">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        {{ html()->label(__('movie.lbl_description'). '<span class="text-danger"> *</span>', 'description')->class('form-label mb-0') }}  
                        <span class="text-primary cursor-pointer" id="GenrateDescription" ><i class="ph ph-info" data-bs-toggle="tooltip" title="{{ __('messages.chatgpt_info') }}"></i> {{ __('messages.lbl_chatgpt') }}</span>
                    </div>
                    {{
                    html()->textarea('description',  $category->description)
                        ->class('form-control')
                        ->id('description')
                        ->placeholder(__('placeholder.lbl_genre_description'))
                        ->attribute('required','required')
                    }}
                    @error('description')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="desc-error">Description field is required</div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">
       
        {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>

{{ html()->form()->close() }}
@include('components.media-modal')
@endsection
@push('after-scripts')
<script>


tinymce.init({
    selector: '#description',
    plugins: 'link image code',
    toolbar: 'undo redo | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | removeformat | code | image',
    setup: function(editor) {
                // Setup TinyMCE to listen for changes
                editor.on('change', function(e) {
                    // Get the editor content
                    const content = editor.getContent().trim();
                    const $textarea = $('#description');
                    const $error = $('#desc-error');
                      
                    // Check if content is empty
                    if (content === '') {
                        $textarea.addClass('is-invalid'); // Add invalid class if empty
                        $error.show(); // Show validation message
                        
                    } else {
                        $textarea.removeClass('is-invalid'); // Remove invalid class if not empty
                        $error.hide(); // Hide validation message
                    }
                });
            }
});
$(document).on('click', '.variable_button', function() {
    const textarea = $(document).find('.tab-pane.active');
    const textareaID = textarea.find('textarea').attr('id');
    tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
});


$(document).ready(function() {

$('#GenrateDescription').on('click', function(e) {

    e.preventDefault();

    var description = $('#description').val();
    var name = $('#name').val();

    var generate_discription = "{{ route('backend.movies.generate-description') }}";
        generate_discription = generate_discription.replace('amp;', '');

    if (!description && !name) {   
         return; 
     }

tinymce.get('description').setContent('Loading...');

  $.ajax({

       url: generate_discription,
       type: 'POST',
       headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
       data: {
               description: description,
               name: name,
             },
       success: function(response) {

          tinymce.get('description').setContent('');

            if(response.success){
 
             var data = response.data;
 
             tinymce.get('description').setContent(data);
 
            } else {
                $('#error_message').text(response.message || 'Failed to get Description.');
            }
        },
       error: function(xhr) {
         $('#error_message').text('Failed to get Description.');
         tinymce.get('description').setContent('');
           $('#import_season_id').show();
           $('#loader').hide();
           $('#import_movie').show();
           if (xhr.responseJSON && xhr.responseJSON.message) {
               $('#error_message').text(xhr.responseJSON.message);
           } else {
               $('#error_message').text('An error occurred while fetching the movie details.');
           }
        }
    });
 });
});

function removeImage(hiddenInputId, removedFlagId) {
    var container = document.getElementById('selectedImageContainer1');
    var hiddenInput = document.getElementById(hiddenInputId);
    var removedFlag = document.getElementById(removedFlagId);

    container.innerHTML = '';
    hiddenInput.value = '';
    removedFlag.value = 1;
}

</script>
@endpush
