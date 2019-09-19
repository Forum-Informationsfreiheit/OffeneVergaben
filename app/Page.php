<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'title',
        'body',
        'slug'
    ];

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
}
