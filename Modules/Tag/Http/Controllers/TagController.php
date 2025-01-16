<?php

namespace Modules\Tag\Http\Controllers;

use App\Trait\ModuleTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Cache;
use Modules\Tag\Http\Requests\TagRequest;
use Modules\Tag\Models\Tag;
use Modules\Tag\Services\TagService;

class TagController extends Controller
{
    protected string $exportClass = '\App\Exports\TagsExport';
    protected $tagService;

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
        $this->traitInitializeModuleTrait(
            'tags.title',
            'tags',
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
        $export_url = route('backend.tags.export');

        return view('tag::backend.tag.index', compact('module_action', 'export_import', 'export_columns', 'export_url'));
    }


    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = __('tags.title');
        Cache::flush();
        return $this->performBulkAction(Tag::class, $ids, $actionType, $moduleName);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $filter = $request->filter;
        return $this->tagService->getDataTable($datatable, $filter);
    }


    public function update_status(Request $request, $id)
    {
        $this->tagService->updateGenre($id, ['status' => $request->status]);
        return response()->json(['status' => true, 'message' => __('messages.status_updated')]);
    }

    public function create(Request $request)
    {
        $module_title = __('tags.add_title');

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

        return view('tag::backend.tag.create', compact('module_title', 'hasMore'));


       // return view('genres::backend.genres.create', compact('mediaUrls','module_title'));
    }

    public function store(TagRequest $request)
    {
        $data = $request->all();
        $data['file_url'] = extractFileNameFromUrl($data['file_url']);

        $this->tagService->createGenre($data);
        $message = __('messages.create_form', ['form' => 'Genres']);
        return redirect()->route('backend.tags.index')->with('success', $message);
    }

    public function show($id)
    {
        return view('tag::show');
    }

    public function edit($id)
    {
        $tag = $this->tagService->getGenreById($id);
        $mediaUrls = getMediaUrls();
        $module_title = __('genres.edit_title');
        return view('tag::backend.tag.edit', compact('tag', 'mediaUrls','module_title'));
    }

    public function update(TagRequest $request, $id)
    {
        $data = $request->all();
        $data['file_url'] = extractFileNameFromUrl($data['file_url']);

        $genre = $this->tagService->getGenreById($id);

        $this->tagService->updateGenre($id, $data);
        $message = __('messages.update_form', ['form' => 'Genres']);
        return redirect()->route('backend.tags.index')->with('success', $message);
    }

    public function destroy($id)
    {
        $this->tagService->deleteGenre($id);
        $message = __('messages.delete_form', ['form' => 'Tags']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore($id)
    {
        $this->tagService->restoreGenre($id);
        $message = __('messages.restore_form', ['form' => 'Tags']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete($id)
    {
        $this->tagService->forceDeleteGenre($id);
        $message = __('messages.permanent_delete_form', ['form' => 'Tags']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }
}
