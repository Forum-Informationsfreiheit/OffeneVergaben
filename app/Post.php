<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $dates = ['published_at'];

    // RELATIONS -------------------------------------------------------------------------------------------------------
    public function author() {
        return $this->belongsTo('App\User','author_id');
    }
    public function tags() {
        return $this->belongsToMany('App\Tag');
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
