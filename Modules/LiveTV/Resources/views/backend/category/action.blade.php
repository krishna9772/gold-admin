<div class="d-flex gap-2 align-items-center justify-content-end">
    
  @if(!$data->trashed())
    @hasPermission('edit_tvcategory')
        <a  class="btn btn-warning-subtle btn-sm fs-4" data-bs-toggle="tooltip" title="{{__('messages.edit')}}" href="{{ route('backend.tv-category.edit', $data->id) }}"> <i class="ph ph-pencil-simple-line align-middle"></i></a>

    @endhasPermission
    @hasPermission('delete_tvcategory')
    <!-- Soft Delete (Trash) -->
    <a href="{{ route('backend.tv-category.destroy', $data->id) }}" id="delete-locations-{{ $data->id }}" class="btn btn-secondary-subtle btn-sm fs-4" data-type="ajax" data-method="DELETE" data-token="{{ csrf_token() }}" data-bs-toggle="tooltip" title="{{ __('messages.delete') }}" data-confirm="{{ __('messages.are_you_sure?') }}">
      <i class="ph ph-trash align-middle"></i>
    </a>
    @endhasPermission
  @else
    <!-- Restore link -->
    @hasPermission('restore_tvcategory')
    <a class="btn btn-success-subtle btn-sm fs-4 restore-tax" data-confirm-message="{{__('messages.are_you_sure_restore')}}" 
    data-success-message="{{__('messages.restore_form',  ['form' => 'Tv Category'])}}" href="{{ route('backend.tv-category.restore', $data->id) }}">
        <i class="ph ph-arrow-clockwise align-middle"></i>
      </a>
      @endhasPermission

      @haspermission('force_delete_tvcategory')
      <!-- Force Delete link -->
      <a href="{{ route('backend.tv-category.force_delete', $data->id) }}" id="delete-{{ $module_name }}-{{ $data->id }}" class="btn btn-danger-subtle btn-sm fs-4" data-type="ajax" data-method="DELETE" data-token="{{ csrf_token() }}" data-bs-toggle="tooltip" title="{{ __('messages.force_delete') }}" data-confirm="{{ __('messages.are_you_sure?') }}">
          <i class="ph ph-trash align-middle"></i>
      </a>
     
      @endhasPermission
    @endif


</div>

