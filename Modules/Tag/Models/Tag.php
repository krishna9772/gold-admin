<?php

namespace Modules\Tag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tag\Database\factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Entertainment\Models\EntertainmentGenerMapping;

class Tag extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'tags';

    protected $fillable = ['name','slug','file_url', 'description', 'status'];

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = slug_format(trim($value));

        if (empty($value)) {
            $this->attributes['slug'] = slug_format(trim($this->attributes['name']));
        }
    }


    public function entertainmentGenerMappings()
    {
        return $this->hasMany(EntertainmentGenerMapping::class,'genre_id','id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($tag) {

            if ($tag->isForceDeleting()) {

                $tag->entertainmentGenerMappings()->forcedelete();

            } else {
                $tag->entertainmentGenerMappings()->delete();
             }

        });

        static::restoring(function ($tag) {

            $tag->entertainmentGenerMappings()->withTrashed()->restore();

        });
    }
}
