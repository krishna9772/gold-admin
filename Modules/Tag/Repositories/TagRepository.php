<?php

namespace Modules\Tag\Repositories;

use Modules\Tag\Models\Tag;
use Auth;

class TagRepository implements TagRepositoryInterface
{
    public function all()
    {
        $query = Tag::query();

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc')->get();

        return $query;
    }

    public function find($id)
    {
        $genreQuery = Tag::query();

        if (Auth::user()->hasRole('user')) {
            $genreQuery->whereNull('deleted_at'); // Only show non-trashed Tag
        }

        $genre = $genreQuery->withTrashed()->findOrFail($id);

        $genre->file_url = setBaseUrlWithFileName($genre->file_url);

        return $genre;
    }

    public function create(array $data)
    {
        return Tag::create($data);
    }

    public function update($id, array $data)
    {
        $genre = Tag::findOrFail($id);
        $genre->update($data);
        return $genre;
    }

    public function delete($id)
    {
        $genre = Tag::findOrFail($id);
        $genre->delete();
        return $genre;
    }

    public function restore($id)
    {
        $genre = Tag::withTrashed()->findOrFail($id);
        $genre->restore();
        return $genre;
    }

    public function forceDelete($id)
    {
        $genre = Tag::withTrashed()->findOrFail($id);
        $genre->forceDelete();
        return $genre;
    }

    public function query()
    {

        $genreQuery=Tag::query()->withTrashed();

        if(Auth::user()->hasRole('user') ) {
            $genreQuery->whereNull('deleted_at');
        }

        return $genreQuery;

    }

    public function list($perPage, $searchTerm = null)
    {
        $query = Tag::query();

        if ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc');

        return $query->paginate($perPage);
    }
}
