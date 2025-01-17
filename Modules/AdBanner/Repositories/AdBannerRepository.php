<?php

namespace Modules\AdBanner\Repositories;

use Modules\AdBanner\Models\AdBanner;
use Auth;
class AdBannerRepository implements AdBannerRepositoryInterface
{
    public function all()
    {
        $query = AdBanner::query();

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc')->get();

        return $query;
    }

    public function find($id)
    {
        $genreQuery = AdBanner::query();

        if (Auth::user()->hasRole('user')) {
            $genreQuery->whereNull('deleted_at'); // Only show non-trashed AdBanner
        }

        $genre = $genreQuery->withTrashed()->findOrFail($id);

        $genre->file_url = setBaseUrlWithFileName($genre->file_url);

        return $genre;
    }

    public function create(array $data)
    {
        return AdBanner::create($data);
    }

    public function update($id, array $data)
    {
        $genre = AdBanner::findOrFail($id);
        $genre->update($data);
        return $genre;
    }

    public function delete($id)
    {
        $genre = AdBanner::findOrFail($id);
        $genre->delete();
        return $genre;
    }

    public function restore($id)
    {
        $genre = AdBanner::withTrashed()->findOrFail($id);
        $genre->restore();
        return $genre;
    }

    public function forceDelete($id)
    {
        $genre = AdBanner::withTrashed()->findOrFail($id);
        $genre->forceDelete();
        return $genre;
    }

    public function query()
    {

        $genreQuery=AdBanner::query()->withTrashed();

        if(Auth::user()->hasRole('user') ) {
            $genreQuery->whereNull('deleted_at');
        }

        return $genreQuery;

    }

    public function list($perPage, $searchTerm = null)
    {
        $query = AdBanner::query();

        if ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc');

        return $query->paginate($perPage);
    }
}
