<?php

namespace Modules\AdBanner\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Genres\Http\Requests\GenresRequest;
use Yajra\DataTables\DataTables;
use Modules\Genres\Models\Genres;
use App\Trait\ModuleTrait;
use Illuminate\Support\Facades\Cache;
use Modules\AdBanner\Http\Requests\AdBannerRequest;
use Modules\AdBanner\Services\AdBannerService;

class AdBannerController extends Controller
{
    protected string $exportClass = '\App\Exports\AdBannerExport';
    protected $adbannerService;

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(AdBannerService $adbannerService)
    {
        $this->adbannerService = $adbannerService;
        $this->traitInitializeModuleTrait(
            'adbanner.title',
            'adbanner',
            'fa-solid fa-clipboard-list'
        );
    }

    public function index()
    {
        $module_action = 'List';
        $export_import = true;
        $export_columns = [
            [
                'value' => 'name',
                'text' => __('messages.name'),
            ],
            [
                'value' => 'description',
                'text' => __('messages.description'),
            ],
            [
                'value' => 'status',
                'text' => __('plan.lbl_status'),
            ],
        ];
        $export_url = route('backend.adbanner.export');

        return view('adbanner::backend.adbanner.index', compact('module_action', 'export_import', 'export_columns', 'export_url'));
    }


    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = __('adbanner.title');
        Cache::flush();
        return $this->performBulkAction(Genres::class, $ids, $actionType, $moduleName);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $filter = $request->filter;
        return $this->adbannerService->getDataTable($datatable, $filter);
    }


    public function update_status(Request $request, $id)
    {
        $this->adbannerService->updateAdBanner($id, ['status' => $request->status]);
        return response()->json(['status' => true, 'message' => __('messages.status_updated')]);
    }

    public function create(Request $request)
    {
        $module_title = __('adbanner.add_title');

        $searchQuery = $request->get('query');
        $perPage = 21;
        $page = $request->get('page', 1);

        $result = getMediaUrls($searchQuery, $perPage, $page);
        $mediaUrls = $result['mediaUrls'];
        $hasMore = $result['hasMore'];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('filemanager::backend.filemanager.partial', compact('mediaUrls'))->render(),
                'hasMore' => $hasMore,
            ]);
        }

        return view('adbanner::backend.adbanner.create', compact('module_title', 'hasMore'));


       // return view('genres::backend.adbanner.create', compact('mediaUrls','module_title'));
    }

    public function store(AdBannerRequest  $request)
    {
        $data = $request->all();
        $data['file_url'] = extractFileNameFromUrl($data['file_url']);

        $this->adbannerService->createAdBanner($data);
        $message = __('messages.create_form', ['form' => 'Ad Banner']);
        return redirect()->route('backend.adbanner.index')->with('success', $message);
    }

    public function show($id)
    {
        return view('adbanner::show');
    }

    public function edit($id)
    {
        $adbanner = $this->adbannerService->getAdBannerById($id);
        $mediaUrls = getMediaUrls();
        $module_title = __('adbanner.edit_title');
        return view('adbanner::backend.adbanner.edit', compact('adbanner', 'mediaUrls','module_title'));
    }

    public function update(AdBannerRequest $request, $id)
    {
        $data = $request->all();
        $data['file_url'] = extractFileNameFromUrl($data['file_url']);

        $this->adbannerService->updateAdBanner($id, $data);
        $message = __('messages.update_form', ['form' => 'Genres']);
        return redirect()->route('backend.adbanner.index')->with('success', $message);
    }

    public function destroy($id)
    {
        $this->adbannerService->deleteAdBanner($id);
        $message = __('messages.delete_form', ['form' => 'Genres']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore($id)
    {
        $this->adbannerService->restoreAdBanner($id);
        $message = __('messages.restore_form', ['form' => 'Genres']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete($id)
    {
        $this->adbannerService->forceDeleteAdBanner($id);
        $message = __('messages.permanent_delete_form', ['form' => 'Genres']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }
}
