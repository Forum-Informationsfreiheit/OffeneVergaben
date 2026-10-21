<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * App\Models\Post
 *
 * @property int $id
 * @property string $title
 * @property string|null $slug
 * @property int $featured
 * @property string $summary
 * @property string $body
 * @property int|null $author_id
 * @property string|null $image_filepath
 * @property string|null $image_filename
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $author
 * @property-read mixed $image
 * @property-read mixed $is_public
 * @property-read mixed $thumb
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @method static \Illuminate\Database\Eloquent\Builder|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Post published()
 * @method static \Illuminate\Database\Eloquent\Builder|Post query()
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereImageFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereImageFilepath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Post extends Model
{
    protected $casts = ['published_at' => 'datetime'];

    // RELATIONS -------------------------------------------------------------------------------------------------------
    public function author() {
        return $this->belongsTo('App\Models\User','author_id');
    }
    public function tags() {
        return $this->belongsToMany('App\Models\Tag');
    }

    // SCOPES ----------------------------------------------------------------------------------------------------------
    public static function scopePublished($query) {
        return $query->whereNotNull('published_at');
    }

    /*
    public static function publishedPostsWithTag($tagId) {
        return self::publishedPosts()->whereHas('tags', function($query) use ($tagId){
            $query->where('tags.id', '=', $tagId);
        });
    }
    */

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
     * @param $slug
     * @return string
     */
    public static function uniqueSlug($slug) {
        $suffix = '';

        while (self::where('slug',$slug.$suffix)->first()) {
            $suffix = '-' . strtolower(Str::random(4));
        }

        return $slug.$suffix;
    }

    public static function newestPost() {
        return self::orderBy('created_at','desc')->first();
    }

    // ATTRIBUTES ------------------------------------------------------------------------------------------------------

    public function getImageAttribute() {
        if ($this->image_filepath && $this->image_filename) {
            return $this->image_filepath . '/' . $this->image_filename;
        }

        return null;
    }

    public function getThumbAttribute() {
        if ($this->image_filepath && $this->image_filename) {
            return $this->image_filepath . '/' . config('lfm.thumb_folder_name') . '/' . $this->image_filename;
        }

        return null;
    }

    /**
     * On publish set the slug according to the title of the post.
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
