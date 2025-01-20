<?php

namespace Modules\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Entertainment\Database\factories\EntertainmentTagMappingFactory;
use Modules\Tag\Models\Tag;

class EntertainmentTagMapping extends Model
{
    use SoftDeletes;

    protected $table = 'entertainment_tag_mapping';

    protected $fillable = [

        'entertainment_id',
        'tag_id',

    ];


    public function tag()
    {
        return $this->belongsTo(Tag::class,'tag_id');
    }
}
