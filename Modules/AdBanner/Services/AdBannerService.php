<?php
namespace Modules\AdBanner\Services;


use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\AdBanner\Repositories\AdBannerRepositoryInterface;

class AdBannerService
{
    protected $adbannerRepository;

    public function __construct(AdBannerRepositoryInterface $adbannerRepository)
    {
        $this->adbannerRepository = $adbannerRepository;
    }

    public function getAllAdBanners()
    {
        return $this->adbannerRepository->all();
    }

    public function getAdBannerById($id)
    {
        return $this->adbannerRepository->find($id);
    }

    public function createAdBanner(array $data)
    {
        $cacheKey = 'adbanner_';
        Cache::forget($cacheKey);

        $data['slug'] = Str::slug($data['name']);
        // $data['file_url'] = setDefaultImage($data['file_url']);
        return $this->adbannerRepository->create($data);
    }

    public function updateAdBanner($id, array $data)
    {
        $cacheKey = 'adbanner_';
        Cache::forget($cacheKey);
        return $this->adbannerRepository->update($id, $data);
    }

    public function deleteAdBanner($id)
    {
        $cacheKey = 'adbanner_';
        Cache::forget($cacheKey);
        return $this->adbannerRepository->delete($id);
    }

    public function restoreAdBanner($id)
    {
        $cacheKey = 'adbanner_';
        Cache::forget($cacheKey);
        return $this->adbannerRepository->restore($id);
    }

    public function forceDeleteAdBanner($id)
    {
        $cacheKey = 'adbanner_';
        Cache::forget($cacheKey);
        return $this->adbannerRepository->forceDelete($id);
    }

    public function getDataTable(Datatables $datatable, $filter)
    {
        $query = $this->getFilteredData($filter);
        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="genres" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('image', function ($data) {

                $imageUrl = setBaseUrlWithFileName($data->file_url);
                return view('components.image-name', ['image' => $imageUrl, 'name' => $data->name])->render();
            })
            ->addColumn('action', function ($data) {
                return view('adbanner::backend.adbanner.action', compact('data'));
            })
            ->editColumn('status', function ($row) {
                $checked = $row->status ? 'checked="checked"' : '';
                $disabled = $row->trashed() ? 'disabled' : '';
                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" data-url="' . route('backend.adbanner.update_status', $row->id) . '"
                            data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                            id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                    </div>
                ';
            })
            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);
                return $diff < 25 ? $data->updated_at->diffForHumans() : $data->updated_at->isoFormat('llll');
            })
            ->orderColumns(['id'], '-:column $1')
            ->rawColumns(['action', 'status', 'check', 'image'])
            ->toJson();
    }

    public function getFilteredData($filter)
    {
        $query = $this->adbannerRepository->query();

        if (isset($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }

        if (isset($filter['name'])) {
            $query->where('name', 'like', '%' . $filter['name'] . '%');
        }

        return $query;
    }

    public function getGenresList($perPage, $searchTerm = null)
    {
        return $this->adbannerRepository->list($perPage, $searchTerm);
    }

}
