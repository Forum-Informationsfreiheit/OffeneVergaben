<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * App\Models\Page
 *
 * @property int $id
 * @property string $title
 * @property string|null $slug
 * @property string $body
 * @property int|null $author_id
 * @property string|null $published_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $author
 * @property-read mixed $is_public
 * @method static \Illuminate\Database\Eloquent\Builder|Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Page published()
 * @method static \Illuminate\Database\Eloquent\Builder|Page query()
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Page extends Model
{
    protected $fillable = [
        'title',
        'body',
        'slug'
    ];

    // RELATIONS -------------------------------------------------------------------------------------------------------
    public function author() {
        return $this->belongsTo('App\Models\User','author_id');
    }

    // SCOPES ----------------------------------------------------------------------------------------------------------
    public static function scopePublished($query) {
        return $query->whereNotNull('published_at');
    }

    public static function findBySlugOrFail($slug) {
        $post = self::where('slug',$slug)->first();

        if (!$post) {
            throw new ModelNotFoundException();
        }

        return $post;
    }

    /**
     * Check if given slug is unique within Posts table.
     * If input is not unique, a random string of 4 chars will be appended.
     *
     * Duplicate of Post::uniqueSlug
     *
     * @param $slug
     * @return string
     */
    public static function uniqueSlug($slug) {
        $suffix = '';

        while (self::where('slug',$slug.$suffix)->first()) {
            $suffix = '-' . strtolower(Str::random(3));
        }

        return $slug.$suffix;
    }

    /**
     * On publish set the slug according to the title of the page.
     *
     * On un-publish clear the slug (set null).
     *
     * @param $published_at
     */
    public function setPublishedAtAttribute($published_at) {
        // back to default, which is null
        if (!$published_at) {
            $this->attributes['published_at'] = null;
            return;
        }

        $this->attributes['published_at'] = $published_at;
    }

    public function getIsPublicAttribute() {
        return $this->published_at != null;
    }
}
